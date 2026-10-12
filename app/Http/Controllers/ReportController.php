<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Contractor;
use App\Models\District;
use App\Models\Lga;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Ward;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Report builder page: pick any combination of criteria (contractors,
     * districts, LGAs, wards, statuses, date range), choose report type,
     * then view on screen or export to CSV / Excel / PDF.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        return view('reports.index', [
            'contractors' => Contractor::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'name']),
            'lgas' => Lga::orderBy('name')->get(['id', 'name']),
            'wards' => Ward::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Generate the report with the selected criteria and format.
     * format: html (on-screen) | csv | xls | pdf
     * report: projects | financial
     */
    public function build(Request $request)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);

        $format = in_array($request->query('format'), ['html', 'csv', 'xls', 'pdf'], true)
            ? $request->query('format') : 'html';

        if ($format !== 'html') {
            abort_unless($request->user()->hasPermission('reports.export'), 403);
        }

        $report = $request->query('report') === 'financial' ? 'financial' : 'projects';

        $criteria = $this->criteriaSummary($request);

        if ($report === 'financial') {
            $rows = $this->financialRows($request);
            $totals = [
                'count' => $rows->count(),
                'amount' => $rows->sum('amount'),
                'retention' => $rows->sum('retention_held'),
            ];
        } else {
            $rows = $this->projectRows($request);
            $totals = [
                'count' => $rows->count(),
                'contract_value' => $rows->sum(fn ($p) => (float) ($p->contract->contract_value ?? 0)),
                'paid' => $rows->sum(fn ($p) => (float) ($p->contract?->total_paid ?? 0)),
                'outstanding' => $rows->sum(fn ($p) => (float) ($p->contract?->outstanding_balance ?? 0)),
            ];
        }

        $title = ($report === 'financial' ? 'Financial Report' : 'Project Summary Report');
        $data = compact('rows', 'totals', 'report', 'title', 'criteria');

        return match ($format) {
            'csv' => $this->toCsv($data),
            'xls' => response()->view('reports.excel', $data)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="'.str_replace(' ', '-', strtolower($title)).'-'.now()->format('Y-m-d').'.xls"'),
            'pdf' => $this->toPdf($data),
            default => view('reports.results', $data),
        };
    }

    /**
     * Audit trail (Module 15) with the Details column.
     */
    public function auditLog(Request $request)
    {
        abort_unless($request->user()->hasPermission('audit.view'), 403);
        $logs = AuditLog::with('user')->latest()->paginate($this->perPage($request, 50))->withQueryString();
        return view('reports.audit', compact('logs'));
    }

    /* ------------------------------------------------------------------ */

    /**
     * Shared project query, filtered by every selected criterion.
     * Multi-selects arrive as arrays; blank = criterion not applied.
     */
    private function filteredProjects(Request $request)
    {
        $user = $request->user();

        return Project::query()
            ->visibleTo($user)
            // Directorate filter is only meaningful for roles that see all directorates
            ->when($user->seesAllDirectorates() && $request->filled('directorates'), fn ($q) => $q->whereIn('projects.directorate', (array) $request->input('directorates')))
            ->when($request->filled('contractor_ids'), fn ($q) => $q->whereIn('projects.contractor_id', (array) $request->input('contractor_ids')))
            ->when($request->filled('district_ids'), fn ($q) => $q->whereIn('projects.district_id', (array) $request->input('district_ids')))
            ->when($request->filled('lga_ids'), fn ($q) => $q->whereIn('projects.lga_id', (array) $request->input('lga_ids')))
            ->when($request->filled('ward_ids'), fn ($q) => $q->whereIn('projects.ward_id', (array) $request->input('ward_ids')))
            ->when($request->filled('statuses'), fn ($q) => $q->whereIn('projects.status', (array) $request->input('statuses')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('projects.award_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('projects.award_date', '<=', $request->date('date_to')));
    }

    private function projectRows(Request $request)
    {
        return $this->filteredProjects($request)
            ->with(['contractor:id,name', 'district:id,name', 'lga:id,name', 'ward:id,name', 'contract.payments'])
            ->orderBy('project_code')->get();
    }

    /**
     * Payments belonging to the filtered projects. The date range applies
     * to the payment date for financial reports.
     */
    private function financialRows(Request $request)
    {
        $projectIds = $this->filteredProjects($request)
            // date range means payment dates here, not award dates
            ->reorder()->pluck('projects.id');

        return Payment::query()
            ->whereHas('contract', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date('date_to')))
            ->with(['contract.project.district:id,name', 'contract.project.lga:id,name', 'contract.project.ward:id,name'])
            ->orderBy('payment_date')->get();
    }

    /**
     * Text summary of the active criteria, shown on every output format
     * so the reader knows exactly what the report covers.
     */
    private function criteriaSummary(Request $request): array
    {
        $summary = [];
        $user = $request->user();
        if (! $user->seesAllDirectorates()) {
            $summary['Directorate'] = Project::DIRECTORATES[$user->directorate()] ?? 'None';
        } elseif ($request->filled('directorates')) {
            $summary['Directorates'] = collect((array) $request->input('directorates'))
                ->map(fn ($d) => Project::DIRECTORATES[$d] ?? $d)->implode(', ');
        }
        if ($request->filled('contractor_ids')) {
            $summary['Contractors'] = Contractor::whereIn('id', (array) $request->input('contractor_ids'))->pluck('name')->implode(', ');
        }
        if ($request->filled('district_ids')) {
            $summary['Districts'] = District::whereIn('id', (array) $request->input('district_ids'))->pluck('name')->implode(', ');
        }
        if ($request->filled('lga_ids')) {
            $summary['LGAs'] = Lga::whereIn('id', (array) $request->input('lga_ids'))->pluck('name')->implode(', ');
        }
        if ($request->filled('ward_ids')) {
            $summary['Wards'] = Ward::whereIn('id', (array) $request->input('ward_ids'))->pluck('name')->implode(', ');
        }
        if ($request->filled('statuses')) {
            $summary['Statuses'] = implode(', ', array_map(fn ($s) => str_replace('_', ' ', $s), (array) $request->input('statuses')));
        }
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $summary['Period'] = ($request->input('date_from') ?: 'beginning').' to '.($request->input('date_to') ?: 'today');
        }

        return $summary ?: ['Scope' => 'All records (no criteria selected)'];
    }

    private function toCsv(array $data): StreamedResponse
    {
        $filename = str_replace(' ', '-', strtolower($data['title'])).'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            foreach ($data['criteria'] as $label => $value) {
                fputcsv($out, ["# {$label}: {$value}"]);
            }

            if ($data['report'] === 'financial') {
                fputcsv($out, ['Date', 'Project', 'Project Code', 'District', 'LGA', 'Ward', 'Contract No', 'Certificate', 'Amount', 'Retention', 'Status']);
                foreach ($data['rows'] as $p) {
                    $proj = $p->contract->project;
                    fputcsv($out, [
                        $p->payment_date->format('Y-m-d'), $proj->name ?? '', $proj->project_code ?? '',
                        $proj->district?->name, $proj->lga?->name, $proj->ward?->name,
                        $p->contract->contract_number ?? '', $p->certificate_number,
                        $p->amount, $p->retention_held, $p->status,
                    ]);
                }
            } else {
                fputcsv($out, ['Project Code', 'Name', 'District', 'LGA', 'Ward', 'Status', 'Contractor', 'Award Date', 'Commencement', 'Expected Completion', 'Contract Value', 'Total Paid', 'Outstanding', 'Planned %', 'Actual %', 'Deficit %']);
                foreach ($data['rows'] as $p) {
                    fputcsv($out, [
                        $p->project_code, $p->name,
                        $p->district?->name, $p->lga?->name, $p->ward?->name,
                        $p->status, $p->contractor?->name,
                        $p->award_date?->format('Y-m-d'), $p->commencement_date?->format('Y-m-d'), $p->expected_completion_date?->format('Y-m-d'),
                        $p->contract?->contract_value, $p->contract?->total_paid, $p->contract?->outstanding_balance,
                        $p->planned_progress_percent, $p->actual_progress_percent, $p->delay_days,
                    ]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * PDF export. Uses barryvdh/laravel-dompdf when installed (richer HTML
     * rendering); otherwise falls back to the built-in dependency-free
     * writer so PDF works out of the box.
     */
    private function toPdf(array $data)
    {
        $filename = str_replace(' ', '-', strtolower($data['title'])).'-'.now()->format('Y-m-d').'.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.excel', $data)
                ->setPaper('a4', 'landscape')
                ->download($filename);
        }

        return response($this->nativePdf($data), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Build the report with the built-in SimplePdf writer.
     */
    private function nativePdf(array $data): string
    {
        $pdf = new \App\Services\SimplePdf();
        $left = \App\Services\SimplePdf::margin();
        $width = \App\Services\SimplePdf::contentWidth();

        // Column layout (ratios sum to 1)
        if ($data['report'] === 'financial') {
            $headers = ['Date', 'Project', 'Code', 'District', 'LGA', 'Ward', 'Contract No', 'Certificate', 'Amount (NGN)', 'Retention', 'Status'];
            $ratios = [0.07, 0.20, 0.10, 0.08, 0.07, 0.08, 0.10, 0.08, 0.10, 0.07, 0.05];
            $rowFn = function ($p) {
                $proj = $p->contract->project;
                return [
                    $p->payment_date->format('d/m/Y'), $proj->name ?? '', $proj->project_code ?? '',
                    $proj->district?->name, $proj->lga?->name, $proj->ward?->name,
                    $p->contract->contract_number ?? '', $p->certificate_number,
                    number_format((float) $p->amount, 2), number_format((float) $p->retention_held, 2), $p->status,
                ];
            };
        } else {
            $headers = ['Code', 'Name', 'District', 'LGA', 'Ward', 'Status', 'Contractor', 'Award', 'Value (NGN)', 'Paid (NGN)', 'Outstanding', 'Plan %', 'Act %'];
            $ratios = [0.09, 0.14, 0.06, 0.06, 0.08, 0.06, 0.10, 0.06, 0.09, 0.09, 0.09, 0.04, 0.04];
            $rowFn = fn ($p) => [
                $p->project_code, $p->name, $p->district?->name, $p->lga?->name, $p->ward?->name,
                str_replace('_', ' ', $p->status), $p->contractor?->name, $p->award_date?->format('d/m/y'),
                number_format((float) ($p->contract->contract_value ?? 0), 2),
                number_format((float) ($p->contract?->total_paid ?? 0), 2),
                number_format((float) ($p->contract?->outstanding_balance ?? 0), 2),
                (string) $p->planned_progress_percent, (string) $p->actual_progress_percent,
            ];
        }

        $widths = array_map(fn ($r) => $r * $width, $ratios);
        $xs = [];
        $x = $left;
        foreach ($widths as $w) { $xs[] = $x; $x += $w; }

        $drawHeader = function () use ($pdf, $left, $width, $data, $headers, $xs, $widths) {
            $pdf->text($left, 13, 'Ministry of Works & Housing (BOMWH) — '.$data['title'], true);
            $pdf->advance(14);
            $criteria = collect($data['criteria'])->map(fn ($v, $k) => "$k: $v")->implode('  |  ');
            $pdf->text($left, 7.5, $criteria.'   |   Generated '.now()->format('d M Y H:i'), false, 0.35);
            $pdf->advance(14);
            $pdf->row($headers, $xs, $widths, 7.5, true);
            $pdf->advance(4);
            $pdf->hline($left, $left + $width);
            $pdf->advance(8);
        };

        $drawHeader();
        foreach ($data['rows'] as $row) {
            if ($pdf->remaining() < 42) {
                $pdf->newPage();
                $drawHeader();
            }
            // Wrapped rows: nothing gets truncated; tallest cell sets row height
            $lines = $pdf->rowWrap($rowFn($row), $xs, $widths, 7);
            $pdf->advance($lines * 10 + 3);
        }

        // Totals footer
        $pdf->advance(4);
        $pdf->hline($left, $left + $width);
        $pdf->advance(10);
        $totalsLine = $data['report'] === 'financial'
            ? sprintf('Payments: %d   Total amount: NGN %s   Retention held: NGN %s',
                $data['totals']['count'], number_format($data['totals']['amount'], 2), number_format($data['totals']['retention'], 2))
            : sprintf('Projects: %d   Contract value: NGN %s   Paid: NGN %s   Outstanding: NGN %s',
                $data['totals']['count'], number_format($data['totals']['contract_value'], 2),
                number_format($data['totals']['paid'], 2), number_format($data['totals']['outstanding'], 2));
        $pdf->text($left, 8.5, $totalsLine, true);

        return $pdf->output();
    }
}
