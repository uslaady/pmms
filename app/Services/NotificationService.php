<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Contractor;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\User;

/**
 * Module 13 — in-app notifications that walk each record through the workflow:
 *
 *   Director registers project  -> Perm Sec approves -> Director Planning assigns an Active contractor
 *   -> Finance records payments -> Perm Sec approves payments -> Resident Engineers / Director update the site.
 *   Director Planning registers contractor -> Perm Sec activates it -> Director Planning can assign it.
 *
 * Project events go to named roles AND to the Director / Resident Engineer of that
 * project's directorate only — never to other directorates.
 * Email/SMS delivery hooks in here: dispatch a queued Mailable / SMS job next to create().
 */
class NotificationService
{
    /* ----------------------------- Projects ----------------------------- */

    public function projectSubmitted(Project $project): void
    {
        $this->send([Role::PERMANENT_SECRETARY], $project, false, false, 'project_submitted',
            "Approval needed: {$project->project_code}",
            "{$project->name} ({$project->directorate_label}) was registered and is awaiting your approval.");
    }

    public function projectsSubmitted(int $count, User $by): void
    {
        $this->send([Role::PERMANENT_SECRETARY], null, false, false, 'project_submitted',
            "Approval needed: {$count} new project(s)",
            "{$by->name} registered {$count} project(s) by bulk upload. Filter Projects by \"Pending approval\" to review them.");
    }

    public function projectApproved(Project $project): void
    {
        $this->send([Role::DIRECTOR_PLANNING], $project, true, true, 'project_approved',
            "Project approved: {$project->project_code}",
            "{$project->name} was approved by the Permanent Secretary. The Director Planning can now assign an Active contractor.");
    }

    public function projectRejected(Project $project): void
    {
        $this->send([], $project, true, false, 'project_rejected',
            "Project rejected: {$project->project_code}",
            "{$project->name} was rejected by the Permanent Secretary: {$project->rejection_reason} — correct it and click Resubmit for approval.",
            extraUserIds: [$project->created_by]);
    }

    public function contractorAssigned(Project $project): void
    {
        $this->send([Role::DIRECTOR_FINANCE], $project, true, true, 'contractor_assigned',
            "Contractor assigned: {$project->project_code}",
            ($project->contractor?->name ?? 'A contractor')." was assigned to {$project->name}.");
    }

    public function projectOverdue(Project $project): void
    {
        $this->send([Role::PERMANENT_SECRETARY, Role::COMMISSIONER], $project, true, true, 'overdue',
            "Project overdue: {$project->project_code}",
            "{$project->name} passed its expected completion date ({$project->expected_completion_date?->format('d M Y')}) at {$project->actual_progress_percent}% completion.");
    }

    public function inspectionDue(Project $project): void
    {
        $this->send([], $project, true, true, 'inspection_due',
            "Inspection due: {$project->project_code}",
            "{$project->name} is due for its next scheduled inspection.");
    }

    public function milestoneCompleted(ProjectMilestone $milestone): void
    {
        $this->send([Role::PERMANENT_SECRETARY], $milestone->project, true, false, 'milestone_completed',
            "Milestone completed: {$milestone->name}",
            "{$milestone->project->name} ({$milestone->project->project_code}) completed milestone '{$milestone->name}'.");
    }

    public function statusChanged(Project $project): void
    {
        $this->send([Role::PERMANENT_SECRETARY, Role::COMMISSIONER], $project, true, true,
            $project->status === 'completed' ? 'project_completed' : 'project_'.$project->status,
            "Project {$project->status_label}: {$project->project_code}",
            $project->name.($project->isFrozen() ? ' — the project is now locked; no changes are allowed.' : ''));
    }

    /* ----------------------------- Payments ----------------------------- */

    public function paymentRecorded(Payment $payment): void
    {
        $this->send([Role::PERMANENT_SECRETARY], $payment->contract->project, false, false, 'payment_recorded',
            'Payment awaiting approval: ₦'.number_format((float) $payment->amount, 2),
            "Certificate {$payment->certificate_number} on contract {$payment->contract->contract_number} was recorded by Finance.");
    }

    public function paymentApproved(Payment $payment): void
    {
        $this->send([Role::DIRECTOR_FINANCE, Role::COMMISSIONER], $payment->contract->project, true, false, 'payment_approved',
            'Payment approved: ₦'.number_format((float) $payment->amount, 2),
            "Certificate {$payment->certificate_number} on contract {$payment->contract->contract_number}.");
    }

    /* ---------------------------- Contractors ---------------------------- */

    public function contractorsSubmitted(int $count, User $by): void
    {
        $this->send([Role::PERMANENT_SECRETARY], null, false, false, 'contractor_submitted',
            "Contractor approval needed: {$count} new contractor(s)",
            "{$by->name} registered {$count} contractor(s). Review their details and documents on the Contractors page, then make them Active.");
    }

    public function contractorRejected(Contractor $contractor): void
    {
        $this->send([Role::DIRECTOR_PLANNING], null, false, false, 'contractor_rejected',
            "Contractor rejected: {$contractor->name}",
            "Reason: {$contractor->rejection_reason} — correct the details/documents and click Resubmit for approval.");
    }

    public function contractorActivated(Contractor $contractor): void
    {
        $this->send([Role::DIRECTOR_PLANNING], null, false, false, 'contractor_activated',
            "Contractor activated: {$contractor->name}",
            "{$contractor->name} is now Active and can be assigned to approved projects.");
    }

    /* ------------------------------------------------------------------- */

    /**
     * @param string[] $roleNames  roles notified regardless of directorate
     * @param bool $directors      also the Director of the project's directorate
     * @param bool $residents      also the Resident Engineer(s) of the project's directorate
     */
    private function send(array $roleNames, ?Project $project, bool $directors, bool $residents, string $type, string $title, string $body, array $extraUserIds = []): void
    {
        $users = User::with('role')->where('status', 'active')->get()->filter(function (User $u) use ($project, $roleNames, $directors, $residents, $extraUserIds) {
            $role = $u->role?->name;
            if (in_array($role, $roleNames, true) || in_array($u->id, $extraUserIds, true)) {
                return true;
            }
            if (! $project || $u->directorate() === null || $u->directorate() !== $project->directorate) {
                return false;
            }

            return ($directors && in_array($role, Role::DIRECTORS, true))
                || ($residents && in_array($role, Role::RESIDENTS, true));
        });

        foreach ($users as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'project_id' => $project?->id,
            ]);
            // To add email: Mail::to($user)->queue(new GenericNotificationMail($title, $body));
            // To add SMS/WhatsApp: dispatch(new SendSmsJob($user->phone, $title));
        }
    }
}
