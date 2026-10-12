<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Executive dashboard. Every card, chart, and the bottom table respect
     * the active filters (status, district_id, lga_id, ward_id,
     * contractor_id). Chart clicks toggle filters; the bottom table can be
     * switched between "Top delayed projects" and "Projects" (all rows
     * matching the current filters).
     *
     * NOTE: all filter columns are qualified with `projects.` because the
     * aggregate queries join lgas/wards/districts/contractors, which have
     * columns of the same name — unqualified names throw
     * "Column 'district_id' in WHERE is ambiguous".
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->string('status')->toString() ?: null,
            'district_id' => $request->integer('district_id') ?: null,
            'lga_id' => $request->integer('lga_id') ?: null,
            'ward_id' => $request->integer('ward_id') ?: null,
            'contractor_id' => $request->integer('contractor_id') ?: null,
        ];

        $base = Project::query()
            ->visibleTo($request->user())
            ->when($filters['status'], fn ($q, $v) => $q->where('projects.status', $v))
            ->when($filters['district_id'], fn ($q, $v) => $q->where('projects.district_id', $v))
            ->when($filters['lga_id'], fn ($q, $v) => $q->where('projects.lga_id', $v))
            ->when($filters['ward_id'], fn ($q, $v) => $q->where('projects.ward_id', $v))
            ->when($filters['contractor_id'], fn ($q, $v) => $q->where('projects.contractor_id', $v));

        $projectIds = (clone $base)->pluck('projects.id');

        $cards = [
            'total_projects' => (clone $base)->count(),
            'pending' => (clone $base)->where('projects.status', 'pending')->count(),
            'planned' => (clone $base)->where('projects.status', 'planned')->count(),
            'completed' => (clone $base)->where('projects.status', 'completed')->count(),
            'ongoing' => (clone $base)->where('projects.status', 'in_progress')->count(),
            'suspended' => (clone $base)->where('projects.status', 'suspended')->count(),
            'delayed' => (clone $base)->where('projects.status', 'delayed')->count(),
            'cancelled' => (clone $base)->whereIn('projects.status', ['cancelled', 'deactivated'])->count(),
            'total_contract_value' => (float) DB::table('contracts')->whereIn('project_id', $projectIds)->sum('contract_value'),
            'total_paid' => (float) DB::table('payments')
                ->join('contracts', 'contracts.id', '=', 'payments.contract_id')
                ->whereIn('contracts.project_id', $projectIds)
                ->where('payments.status', 'paid')->sum('payments.amount'),
            'average_completion' => round((float) (clone $base)->avg('actual_progress_percent'), 1),
        ];
        $cards['outstanding'] = $cards['total_contract_value'] - $cards['total_paid'];

        $byStatus = (clone $base)->select('projects.status', DB::raw('count(*) as total'))
            ->groupBy('projects.status')->pluck('total', 'status');

        $byDistrict = (clone $base)
            ->leftJoin('districts', 'districts.id', '=', 'projects.district_id')
            ->selectRaw("projects.district_id as id, COALESCE(districts.name, 'Unassigned') as name, COUNT(*) as total")
            ->groupBy('projects.district_id', 'districts.name')
            ->orderByDesc('total')->get();

        $byLga = (clone $base)
            ->leftJoin('lgas', 'lgas.id', '=', 'projects.lga_id')
            ->selectRaw("projects.lga_id as id, COALESCE(lgas.name, 'Unassigned') as name, COUNT(*) as total")
            ->groupBy('projects.lga_id', 'lgas.name')
            ->orderByDesc('total')->get();

        $byWard = (clone $base)
            ->leftJoin('wards', 'wards.id', '=', 'projects.ward_id')
            ->selectRaw("projects.ward_id as id, COALESCE(wards.name, 'Unassigned') as name, COUNT(*) as total")
            ->groupBy('projects.ward_id', 'wards.name')
            ->orderByDesc('total')->get();

        $byContractor = (clone $base)
            ->leftJoin('contractors', 'contractors.id', '=', 'projects.contractor_id')
            ->selectRaw("projects.contractor_id as id, COALESCE(contractors.name, 'Unassigned') as name, COUNT(*) as total")
            ->groupBy('projects.contractor_id', 'contractors.name')
            ->orderByDesc('total')->get();

        // Bottom table: toggle between the delayed view and all filtered projects
        $tableView = $request->query('table') === 'projects' ? 'projects' : 'delayed';

        $topDelayed = collect();
        $tableProjects = null;

        if ($tableView === 'projects') {
            $tableProjects = (clone $base)
                ->with(['contractor:id,name', 'lga:id,name', 'district:id,name'])
                ->orderByDesc('projects.created_at')
                ->paginate($this->perPage($request, 25))->withQueryString();
        } else {
            $topDelayed = (clone $base)->whereIn('projects.status', ['delayed', 'suspended'])
                ->orderByRaw('(planned_progress_percent - actual_progress_percent) desc')
                ->take(10)->get();
        }

        $filtersActive = count(array_filter($filters)) > 0;

        return view('dashboard.index', compact(
            'cards', 'byStatus', 'byDistrict', 'byLga', 'byWard', 'byContractor',
            'topDelayed', 'tableProjects', 'tableView', 'filters', 'filtersActive',
        ));
    }
}
