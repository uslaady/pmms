<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class PublicPortalController extends Controller
{
    /**
     * Module: Citizen transparency portal (no login).
     * Shows completed & ongoing projects with location, photos, and progress.
     */
    public function index(Request $request)
    {
        $projects = Project::whereIn('status', ['completed', 'in_progress'])
            ->with(['lga:id,name', 'contractor:id,name'])
            ->when($request->filled('lga_id'), fn ($q) => $q->where('lga_id', $request->integer('lga_id')))
            ->latest()->paginate(15)->withQueryString();

        return view('public.projects', [
            'projects' => $projects,
            'lgas' => \App\Models\Lga::orderBy('name')->get(),
        ]);
    }

    public function show(Project $project)
    {
        abort_unless(in_array($project->status, ['completed', 'in_progress'], true), 404);
        $project->load(['images' => fn ($q) => $q->latest()->take(12), 'lga', 'contractor:id,name', 'milestones']);
        return view('public.show', compact('project'));
    }
}
