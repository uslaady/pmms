<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\District;
use App\Models\Lga;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()->visibleTo($request->user())->with(['contractor', 'lga', 'district', 'category']);

        // Search across code, name, LGA, and contractor
        $query->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
            $term = $request->string('q');
            $q->where('project_code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhereHas('lga', fn ($l) => $l->where('name', 'like', "%{$term}%"))
              ->orWhereHas('contractor', fn ($c) => $c->where('name', 'like', "%{$term}%"));
        }));

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('directorate'), fn ($q) => $q->where('directorate', $request->string('directorate')));
        $query->when($request->filled('lga_id'), fn ($q) => $q->where('lga_id', $request->integer('lga_id')));
        $query->when($request->filled('contractor_id'), fn ($q) => $q->where('contractor_id', $request->integer('contractor_id')));
        $query->when($request->filled('year'), fn ($q) => $q->whereYear('award_date', $request->integer('year')));

        $projects = $query->latest()->paginate($this->perPage($request))->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'projects.create');

        $data = $this->validateProject($request);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'pending'; // every new project waits for the Permanent Secretary's approval

        $project = Project::create($data);
        app(NotificationService::class)->projectSubmitted($project);

        return redirect()->route('projects.show', $project)
            ->with('status', 'Project registered and sent to the Permanent Secretary for approval.');
    }

    public function show(Project $project)
    {
        $project->load([
            'contractor', 'consultant', 'supervisingEngineer', 'district', 'lga', 'ward', 'community',
            'contract.payments', 'contract.variations', 'milestones', 'updates', 'inspections',
            'images', 'documents', 'risks', 'issues',
        ]);

        return view('projects.show', compact('project'));
    }

    /**
     * Update an existing project's core details.
     */
    public function update(Request $request, Project $project)
    {
        // Locked (suspended/cancelled/deactivated) projects: only Admin, Commissioner, Perm Sec.
        abort_unless($project->canBeEditedBy($request->user()), 403, $project->isFrozen()
            ? "This project is {$project->status_label} and locked. Only the Super Admin, Commissioner or Permanent Secretary can edit it."
            : 'You do not have permission to edit projects.');

        $data = $this->validateProject($request, $project);

        $project->update($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    /**
     * Soft-delete a project (recoverable from the database; the code
     * remains reserved because unique checks include trashed rows).
     */
    public function destroy(Request $request, Project $project)
    {
        $this->authorizeAction($request, 'projects.delete');

        $code = $project->project_code;
        $project->delete();

        return redirect()->route('projects.index')->with('status', "Project {$code} deleted.");
    }

    /**
     * Field engineers / contractor reps submit a progress update (Module 5).
     */
    public function recordProgress(Request $request, Project $project)
    {
        $this->authorizeAction($request, 'progress.update');

        $data = $request->validate([
            'physical_progress_percent' => 'required|numeric|min:0|max:100',
            'financial_progress_percent' => 'nullable|numeric|min:0|max:100',
            'narrative' => 'nullable|string',
        ]);

        $data['project_id'] = $project->id;
        $data['submitted_by'] = $request->user()->id;

        $project->updates()->create($data);

        return back()->with('status', 'Progress update submitted for approval.');
    }

    /**
     * Director of Projects approves or rejects a submitted progress update.
     */
    public function reviewProgress(Request $request, ProjectUpdate $update)
    {
        $this->authorizeAction($request, 'progress.approve');

        $data = $request->validate(['decision' => 'required|in:approved,rejected']);

        $update->update([
            'approval_status' => $data['decision'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if ($data['decision'] === 'approved') {
            $update->project->update([
                'actual_progress_percent' => $update->physical_progress_percent,
            ]);

            if ($update->physical_progress_percent >= 100) {
                $update->project->update(['status' => 'completed']);
                app(NotificationService::class)->statusChanged($update->project);
            }
        }

        return back()->with('status', "Progress update {$data['decision']}.");
    }

    /**
     * Change project status (suspend, cancel, reactivate).
     */
    public function changeStatus(Request $request, Project $project)
    {
        $this->authorizeAction($request, 'projects.approve');

        // 'rejected' is set only through Reject (a reason is required)
        $data = $request->validate([
            'status' => ['required', Rule::in(array_diff(array_keys(Project::STATUS_LABELS), ['rejected']))],
        ]);

        $wasPending = $project->isPending() || $project->isRejected();
        $data['rejection_reason'] = null;
        $project->update($data);

        $notifier = app(NotificationService::class);
        if ($wasPending && $project->isActive()) {
            $notifier->projectApproved($project);
        } elseif (in_array($data['status'], ['suspended', 'completed', 'cancelled', 'deactivated'], true)) {
            $notifier->statusChanged($project);
        }

        return back()->with('status', "Project status is now {$project->status_label}.");
    }

    /**
     * Permanent Secretary (or Admin / Commissioner) declines a project awaiting
     * approval. It is NOT locked: the Director corrects it and resubmits it.
     */
    public function reject(Request $request, Project $project)
    {
        $this->authorizeAction($request, 'projects.approve');
        abort_unless($project->isPending(), 422, 'Only projects awaiting approval can be rejected.');

        $data = $request->validate(['rejection_reason' => 'required|string|max:2000'], [
            'rejection_reason.required' => 'Please give the reason for rejecting this project, so it can be corrected.',
        ]);

        $project->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
        app(NotificationService::class)->projectRejected($project);

        return back()->with('status', 'Project rejected. The Director has been notified to correct and resubmit it.');
    }

    /** After correcting a rejected project, whoever can edit it sends it back for approval. */
    public function resubmit(Request $request, Project $project)
    {
        abort_unless($project->isRejected(), 422, 'Only rejected projects can be resubmitted.');
        abort_unless($project->canBeEditedBy($request->user()), 403);

        $project->update(['status' => 'pending']);
        app(NotificationService::class)->projectSubmitted($project);

        return back()->with('status', 'Project resubmitted to the Permanent Secretary for approval.');
    }

    /**
     * Director Planning (or Admin / Commissioner) assigns an ACTIVE contractor
     * to an APPROVED project. Directors never choose their own contractor.
     * Re-assigning to another active contractor is allowed; the previous one is
     * recorded in the audit log.
     */
    public function assignContractor(Request $request, Project $project)
    {
        $this->authorizeAction($request, 'projects.assign_contractor');

        abort_unless($project->isActive(), 422,
            "A contractor can only be assigned to an approved, active project (this one is {$project->status_label}).");

        $data = $request->validate([
            'contractor_id' => ['required', Rule::exists('contractors', 'id')->where('status', 'active')->whereNull('deleted_at')],
        ], [
            'contractor_id.exists' => 'Projects can only be assigned to an Active contractor.',
        ]);

        $project->update($data);
        app(NotificationService::class)->contractorAssigned($project->fresh('contractor'));

        return back()->with('status', "{$project->contractor->name} assigned to this project.");
    }

    /**
     * CSV template for bulk registration, now including District/LGA/Ward.
     * Lat/long may be left blank — they auto-fill from the ward centroid.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $this->authorizeAction($request, 'projects.create');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'project_code', 'project_name', 'directorate', 'district', 'lga', 'ward',
                'latitude', 'longitude',
                'award_date', 'commencement_date', 'expected_completion_date', 'priority', 'funded_by', 'description',
            ]);
            fputcsv($out, [
                'MWH/2026/RD/100', 'Example Road Rehabilitation', 'civil', 'Borno Central', 'Jere', 'Mairi',
                '', '',
                '15/01/2026', '01/02/2026', '31/12/2026', 'medium', 'Borno State Govt.',
                'directorate must be one of: civil, public_building, electrical, mechanical (Directors: always your own directorate). funded_by is optional - blank defaults to Borno State Govt. Leave latitude/longitude blank to auto-fill from the ward centroid. New projects await Permanent Secretary approval; contractors are assigned afterwards by the Director Planning',
            ]);
            fclose($out);
        }, 'project-upload-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Bulk registration. Valid rows are created; invalid rows are skipped
     * and reported with row number and reasons. The District -> LGA -> Ward
     * hierarchy is verified; blank coordinates auto-fill from the ward.
     */
    public function bulkStore(Request $request)
    {
        $this->authorizeAction($request, 'projects.create');

        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            return back()->withErrors(['file' => 'The uploaded file is empty.']);
        }

        // "project_name" is the template's column; the older "name" heading is still accepted
        $header = array_map(fn ($h) => ($h = strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF"))) === 'project_name' ? 'name' : $h, $header);
        $user = $request->user();
        $required = ['project_code', 'name'];
        if ($user->seesAllDirectorates()) {
            $required[] = 'directorate'; // Planning / Admin / Commissioner must say which directorate each row belongs to
        }
        $missing = array_diff($required, $header);
        if ($missing) {
            fclose($handle);
            $missing = array_map(fn ($c) => $c === 'name' ? 'project_name' : $c, $missing);
            return back()->withErrors(['file' => 'Template columns missing: '.implode(', ', $missing).'. Download the template and keep the header row unchanged.']);
        }

        // Location lookups (all names matched case-insensitively)
        $districts = District::pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [strtolower($n) => $id]);
        $lgas = Lga::get(['id', 'name', 'district_id'])
            ->keyBy(fn ($l) => strtolower($l->name));
        $wardsByLga = \App\Models\Ward::get(['id', 'lga_id', 'name', 'latitude', 'longitude'])
            ->groupBy('lga_id')
            ->map(fn ($g) => $g->keyBy(fn ($w) => strtolower($w->name)));

        $created = 0;
        $errors = [];
        $seenCodes = [];
        $rowNum = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $col) {
                $row[$col] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
            }

            $rowErrors = [];

            if ($row['project_code'] === '') {
                $rowErrors[] = 'project_code is required';
            } elseif (isset($seenCodes[$row['project_code']])) {
                $rowErrors[] = "duplicate project_code '{$row['project_code']}' within this file (first used on row {$seenCodes[$row['project_code']]})";
            } elseif (Project::withTrashed()->where('project_code', $row['project_code'])->exists()) {
                $rowErrors[] = "project_code '{$row['project_code']}' already exists in the system";
            }

            if ($row['name'] === '') {
                $rowErrors[] = 'project_name is required';
            }

            // ---- Directorate ----
            if ($user->seesAllDirectorates()) {
                $directorate = Project::directorateKey($row['directorate'] ?? '');
                if (! $directorate) {
                    $rowErrors[] = "directorate '".($row['directorate'] ?? '')."' must be one of: civil, public_building, electrical, mechanical";
                }
            } else {
                $directorate = $user->directorate();
                $given = Project::directorateKey($row['directorate'] ?? '');
                if (($row['directorate'] ?? '') !== '' && $given !== $directorate) {
                    $rowErrors[] = "you can only register projects for your own directorate ({$directorate})";
                }
            }

            // ---- District -> LGA -> Ward hierarchy ----
            $districtId = null;
            $lgaModel = null;
            $wardModel = null;

            if (($row['district'] ?? '') !== '') {
                $districtId = $districts[strtolower($row['district'])] ?? null;
                if (! $districtId) {
                    $rowErrors[] = "unknown district '{$row['district']}' — must be one of the 3 Borno districts";
                }
            }

            if (($row['lga'] ?? '') !== '') {
                $lgaModel = $lgas[strtolower($row['lga'])] ?? null;
                if (! $lgaModel) {
                    $rowErrors[] = "unknown LGA '{$row['lga']}' — must match one of the 27 LGA names exactly";
                } elseif ($districtId && $lgaModel->district_id !== $districtId) {
                    $rowErrors[] = "LGA '{$row['lga']}' does not belong to district '{$row['district']}'";
                } elseif (! $districtId) {
                    $districtId = $lgaModel->district_id; // derive district from LGA
                }
            }

            if (($row['ward'] ?? '') !== '') {
                if (! $lgaModel) {
                    $rowErrors[] = "ward '{$row['ward']}' given without a valid LGA — a ward requires its LGA";
                } else {
                    $wardModel = $wardsByLga->get($lgaModel->id)?->get(strtolower($row['ward']));
                    if (! $wardModel) {
                        $rowErrors[] = "ward '{$row['ward']}' not found in LGA '{$row['lga']}'";
                    }
                }
            }

            // Contractors are never assigned at registration: the project must first be
            // approved by the Permanent Secretary, then the Director Planning assigns an
            // ACTIVE contractor from the project page.
            if (($row['contractor'] ?? '') !== '') {
                $rowErrors[] = 'leave the contractor column empty — a contractor is assigned by the Director Planning after the project is approved';
            }

            // Coordinates: blank -> auto-fill from ward centroid; provided -> validated
            $lat = ($row['latitude'] ?? '') !== '' ? $row['latitude'] : null;
            $lng = ($row['longitude'] ?? '') !== '' ? $row['longitude'] : null;
            foreach ([['latitude', $lat, 90], ['longitude', $lng, 180]] as [$label, $val, $limit]) {
                if ($val !== null && (! is_numeric($val) || abs((float) $val) > $limit)) {
                    $rowErrors[] = "{$label} '{$val}' is not a valid coordinate (±{$limit})";
                }
            }
            if ($lat === null && $wardModel?->latitude) {
                $lat = $wardModel->latitude;
            }
            if ($lng === null && $wardModel?->longitude) {
                $lng = $wardModel->longitude;
            }

            // Dates
            $dates = [];
            foreach (['award_date', 'commencement_date', 'expected_completion_date'] as $d) {
                $dates[$d] = null;
                if (($row[$d] ?? '') !== '') {
                    $dates[$d] = $this->parseBulkDate($row[$d]);
                    if (! $dates[$d]) {
                        $rowErrors[] = "{$d} '{$row[$d]}' is not a valid date — use DD/MM/YYYY";
                    }
                }
            }
            if ($dates['award_date'] && $dates['commencement_date'] && $dates['commencement_date']->lt($dates['award_date'])) {
                $rowErrors[] = 'commencement_date cannot be before award_date';
            }
            if ($dates['commencement_date'] && $dates['expected_completion_date'] && $dates['expected_completion_date']->lt($dates['commencement_date'])) {
                $rowErrors[] = 'expected_completion_date cannot be before commencement_date';
            }

            // Priority
            $priority = strtolower($row['priority'] ?? '') ?: 'medium';
            if (! in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
                $rowErrors[] = "priority '{$row['priority']}' must be one of: low, medium, high, critical";
            }

            if ($rowErrors) {
                $errors[] = ['row' => $rowNum, 'code' => $row['project_code'] ?: '—', 'reasons' => $rowErrors];
                continue;
            }

            Project::create([
                'project_code' => $row['project_code'],
                'name' => $row['name'],
                'directorate' => $directorate,
                'district_id' => $districtId,
                'lga_id' => $lgaModel?->id,
                'ward_id' => $wardModel?->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'award_date' => $dates['award_date'],
                'commencement_date' => $dates['commencement_date'],
                'expected_completion_date' => $dates['expected_completion_date'],
                'priority' => $priority,
                'funded_by' => trim($row['funded_by'] ?? '') !== '' ? trim($row['funded_by']) : 'Borno State Govt.',
                'description' => $row['description'] ?? null,
                'status' => 'pending',
                'created_by' => $request->user()->id,
            ]);

            $seenCodes[$row['project_code']] = $rowNum;
            $created++;
        }

        fclose($handle);

        if ($created > 0) {
            app(NotificationService::class)->projectsSubmitted($created, $user);
        }

        return redirect()->route('projects.index')->with('bulk_results', [
            'created' => $created,
            'errors' => $errors,
        ]);
    }

    /**
     * Shared validation for store() and update().
     */
    private function validateProject(Request $request, ?Project $project = null): array
    {
        $user = $request->user();

        // A Director / Resident Engineer can only work inside their own directorate:
        // whatever was submitted, their directorate is forced.
        if (! $user->seesAllDirectorates()) {
            $request->merge(['directorate' => $user->directorate()]);
        }

        // The contractor is never set through the project form: it is assigned
        // separately by the Director Planning once the project is approved, so an
        // edit can never remove or change it by accident.
        $request->request->remove('contractor_id');

        $data = $request->validate([
            'directorate' => ['required', Rule::in(array_keys(Project::DIRECTORATES))],
            'project_code' => 'required|string|unique:projects,project_code'.($project ? ','.$project->id : ''),
            'name' => 'required|string|max:255',
            'project_category_id' => 'nullable|exists:project_categories,id',
            'district_id' => 'nullable|exists:districts,id',
            'lga_id' => 'nullable|exists:lgas,id',
            'ward_id' => 'nullable|exists:wards,id',
            'community_id' => 'nullable|exists:communities,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'consultant_id' => 'nullable|exists:consultants,id',
            'supervising_engineer_id' => 'nullable|exists:users,id',
            'award_date' => 'nullable|date',
            'commencement_date' => 'nullable|date|after_or_equal:award_date',
            'expected_completion_date' => 'nullable|date|after_or_equal:commencement_date',
            'priority' => 'nullable|in:low,medium,high,critical',
            'funded_by' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        return $data;
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    /**
     * Bulk dates: DD/MM/YYYY (as typed in Excel). YYYY-MM-DD is still accepted.
     * Impossible dates such as 31/02/2026 are rejected rather than rolled over.
     */
    private function parseBulkDate(string $value): ?\Carbon\Carbon
    {
        $value = trim($value);
        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return \Carbon\Carbon::instance($date)->startOfDay();
            }
        }

        return null;
    }
}
