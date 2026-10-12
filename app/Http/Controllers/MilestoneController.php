<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('milestones.update'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sequence' => 'nullable|integer|min:0',
            'planned_date' => 'nullable|date',
            'comments' => 'nullable|string',
        ]);

        $project->milestones()->create($data + ['status' => 'pending']);

        return back()->with('status', 'Milestone added.');
    }

    public function update(Request $request, ProjectMilestone $milestone)
    {
        abort_unless($request->user()->hasPermission('milestones.update'), 403);

        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,delayed',
            'actual_date' => 'nullable|date',
            'comments' => 'nullable|string',
        ]);

        $milestone->update($data);

        if ($data['status'] === 'completed') {
            app(NotificationService::class)->milestoneCompleted($milestone);
        }

        return back()->with('status', 'Milestone updated.');
    }
}
