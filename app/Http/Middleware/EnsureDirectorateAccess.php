<?php

namespace App\Http\Middleware;

use App\Models\Contract;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectImage;
use App\Models\ProjectIssue;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\RiskRegisterEntry;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Directorate wall.
 *
 * Directors and Resident Engineers belong to ONE directorate and may only open
 * or change projects of that directorate. Every route that carries a project
 * (or something that belongs to a project: a contract, payment, milestone,
 * inspection, photo, document, risk, issue, progress update) is checked here,
 * so one rule protects every page, form and action.
 *
 * Admin, Commissioner, Permanent Secretary, Director Planning and Director
 * Finance are not restricted.
 *
 * It also enforces the project's state on every change (POST/PUT/DELETE):
 *  - Suspended / Cancelled / Deactivated projects are LOCKED: nothing can be
 *    added, changed or removed. Only the Super Admin, Commissioner and Permanent
 *    Secretary may edit the project, change its status, or delete it.
 *  - Projects pending approval only accept detail edits and documents until the
 *    Permanent Secretary approves them.
 */
class EnsureDirectorateAccess
{
    /** route parameter name => model class (fallback when bindings were not resolved yet) */
    private const PARAMS = [
        'project' => Project::class,
        'contract' => Contract::class,
        'payment' => Payment::class,
        'milestone' => ProjectMilestone::class,
        'inspection' => Inspection::class,
        'image' => ProjectImage::class,
        'document' => ProjectDocument::class,
        'risk' => RiskRegisterEntry::class,
        'issue' => ProjectIssue::class,
        'update' => ProjectUpdate::class,
    ];

    /** Changes still allowed on a LOCKED project (the controller further limits them to executives). */
    private const FROZEN_ALLOWED = ['projects.update', 'projects.destroy', 'projects.status'];

    /** Changes allowed while a project is still awaiting approval. */
    private const PENDING_ALLOWED = [
        'projects.update', 'projects.destroy', 'projects.status', 'projects.reject', 'projects.resubmit',
        'projects.documents.store', 'documents.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $isWrite = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $routeName = $request->route()?->getName();

        foreach ($request->route()?->parameters() ?? [] as $name => $value) {
            $project = $this->projectFor($name, $value);
            if (! $project) {
                continue;
            }

            if (! $user->seesAllDirectorates() && $project->directorate !== $user->directorate()) {
                abort(403, 'This project belongs to another directorate. You can only access '
                    .($user->directorate() ? (Project::DIRECTORATES[$user->directorate()] ?? 'your').' directorate' : 'your directorate').' projects.');
            }

            if ($isWrite && $project->isFrozen() && ! in_array($routeName, self::FROZEN_ALLOWED, true)) {
                abort(403, "This project is {$project->status_label} and locked: no changes are allowed. "
                    .'Only the Super Admin, Commissioner or Permanent Secretary can edit it, change its status or delete it.');
            }

            if ($isWrite && ($project->isPending() || $project->isRejected()) && ! in_array($routeName, self::PENDING_ALLOWED, true)) {
                abort(403, ($project->isRejected()
                        ? 'This project was rejected by the Permanent Secretary. Correct it and resubmit it for approval. '
                        : 'This project is awaiting approval by the Permanent Secretary. ')
                    .'Contracts, contractor assignment, payments and site updates are possible once it is approved.');
            }
        }

        return $next($request);
    }

    private function projectFor(string $name, mixed $value): ?Project
    {
        if (! $value instanceof Model) {
            $class = self::PARAMS[$name] ?? null;
            if (! $class || ! is_scalar($value)) {
                return null;
            }
            $value = $class === Project::class
                ? Project::withTrashed()->find($value)
                : $class::find($value);
        }

        return match (true) {
            $value instanceof Project => $value,
            $value instanceof Payment => $value->contract?->project()->withTrashed()->first(),
            isset($value->project_id) => Project::withTrashed()->find($value->project_id),
            default => null,
        };
    }
}
