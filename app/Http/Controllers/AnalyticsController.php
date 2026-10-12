<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Module 16 — KPIs: SPI, CPI, contractor rankings, delay analysis.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('dashboard.executive'), 403);

        // Directors only analyse their own directorate; the others see everything.
        $visible = fn ($q) => $q->visibleTo($user);

        // Schedule Performance Index (portfolio-level): actual / planned progress
        $planned = (float) Project::visibleTo($user)->whereIn('status', ['in_progress', 'delayed', 'suspended'])->sum('planned_progress_percent');
        $actual = (float) Project::visibleTo($user)->whereIn('status', ['in_progress', 'delayed', 'suspended'])->sum('actual_progress_percent');
        $spi = $planned > 0 ? round($actual / $planned, 2) : null;

        // Cost Performance Index: physical progress value earned vs amount paid.
        // Earned value and paid cost are summed separately: joining payments onto
        // contracts repeated a contract's earned value once per payment (inflating CPI),
        // and deleted (soft-deleted) projects must not count.
        $scopeProjects = fn ($q) => $q->whereNull('projects.deleted_at')
            ->when(! $user->seesAllDirectorates(), fn ($q) => $q->where('projects.directorate', $user->directorate() ?? '__none__'));

        $earnedValue = (float) DB::table('contracts')
            ->join('projects', 'projects.id', '=', 'contracts.project_id')
            ->tap($scopeProjects)
            ->sum(DB::raw('COALESCE(contracts.revised_value, contracts.contract_value) * projects.actual_progress_percent / 100'));

        $actualCost = (float) DB::table('payments')
            ->join('contracts', 'contracts.id', '=', 'payments.contract_id')
            ->join('projects', 'projects.id', '=', 'contracts.project_id')
            ->where('payments.status', 'paid')
            ->tap($scopeProjects)
            ->sum('payments.amount');

        $cpi = $actualCost > 0 ? round($earnedValue / $actualCost, 2) : null;

        // Contractor rankings: on-time delivery and average progress deficit
        $contractorRankings = Contractor::withCount([
                'projects' => $visible,
                'projects as completed_count' => fn ($q) => $q->visibleTo($user)->where('status', 'completed'),
                'projects as delayed_count' => fn ($q) => $q->visibleTo($user)->where('status', 'delayed'),
            ])
            ->get()
            ->filter(fn ($c) => $c->projects_count > 0) // same as HAVING projects_count > 0, but portable to SQLite
            ->map(function ($c) {
                $c->on_time_rate = $c->projects_count > 0
                    ? round(100 * $c->completed_count / max(1, $c->projects_count), 1)
                    : 0;
                return $c;
            })
            ->sortByDesc('on_time_rate')->values();

        // Delay analysis by LGA
        $delayByLga = Project::visibleTo($user)->select('lga_id',
                DB::raw('AVG(planned_progress_percent - actual_progress_percent) as avg_deficit'),
                DB::raw('COUNT(*) as total'))
            ->with('lga:id,name')
            ->whereIn('status', ['in_progress', 'delayed'])
            ->groupBy('lga_id')->get();

        return view('analytics.index', compact('spi', 'cpi', 'contractorRankings', 'delayByLga'));
    }
}
