<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectIssue;
use App\Models\RiskRegisterEntry;
use Illuminate\Http\Request;

class RiskIssueController extends Controller
{
    public function storeRisk(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('projects.edit') || $request->user()->hasPermission('inspections.create'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:low,medium,high,critical',
            'mitigation' => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $project->risks()->create($data + ['status' => 'open']);

        return back()->with('status', 'Risk recorded.');
    }

    public function updateRisk(Request $request, RiskRegisterEntry $risk)
    {
        abort_unless($request->user()->hasPermission('projects.edit'), 403);
        $risk->update($request->validate(['status' => 'required|in:open,mitigated,closed']));
        return back()->with('status', 'Risk status updated.');
    }

    public function storeIssue(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('progress.update') || $request->user()->hasPermission('projects.edit'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->issues()->create($data + ['raised_by' => $request->user()->id, 'status' => 'open']);

        return back()->with('status', 'Issue logged.');
    }

    public function resolveIssue(Request $request, ProjectIssue $issue)
    {
        abort_unless($request->user()->hasPermission('projects.edit'), 403);
        $issue->update(['status' => 'resolved']);
        return back()->with('status', 'Issue resolved.');
    }
}
