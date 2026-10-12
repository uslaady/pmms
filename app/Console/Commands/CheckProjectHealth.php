<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see routes/console.php). Flags overdue projects,
 * marks lagging ones as delayed, and raises inspection-due notices.
 */
class CheckProjectHealth extends Command
{
    protected $signature = 'pmms:check-project-health';
    protected $description = 'Flag overdue/delayed projects and inspection-due notices';

    public function handle(NotificationService $notifier): int
    {
        // 1. Overdue: past expected completion, still not completed
        Project::whereIn('status', ['in_progress', 'delayed'])
            ->whereDate('expected_completion_date', '<', now())
            ->each(function (Project $p) use ($notifier) {
                if ($p->status !== 'delayed') {
                    $p->update(['status' => 'delayed']);
                }
                $notifier->projectOverdue($p);
            });

        // 2. Slipping: actual lags planned by more than 15 points
        Project::where('status', 'in_progress')
            ->whereRaw('planned_progress_percent - actual_progress_percent > 15')
            ->update(['status' => 'delayed']);

        // 3. Inspection due
        Project::whereIn('status', ['in_progress', 'delayed'])
            ->whereHas('inspections', fn ($q) => $q->whereDate('next_inspection_date', '<=', now()))
            ->each(fn (Project $p) => $notifier->inspectionDue($p));

        $this->info('Project health check complete.');
        return self::SUCCESS;
    }
}
