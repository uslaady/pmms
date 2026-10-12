<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Project;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('inspections.create'), 403);

        $data = $this->validateInspection($request);
        $data['project_id'] = $project->id;
        $data['officer_id'] = $request->user()->id;

        $project->inspections()->create($data);

        return back()->with('status', 'Inspection recorded.');
    }

    /**
     * Correct a recorded inspection (M&E officers / Super Admin).
     */
    public function update(Request $request, Inspection $inspection)
    {
        abort_unless($request->user()->hasPermission('inspections.create'), 403);

        $inspection->update($this->validateInspection($request));

        return back()->with('status', 'Inspection updated.');
    }

    public function destroy(Request $request, Inspection $inspection)
    {
        abort_unless($request->user()->hasPermission('inspections.create'), 403);

        $inspection->delete();

        return back()->with('status', 'Inspection deleted.');
    }

    private function validateInspection(Request $request): array
    {
        return $request->validate([
            'inspection_date' => 'required|date',
            'gps_lat' => 'nullable|numeric|between:-90,90',
            'gps_lng' => 'nullable|numeric|between:-180,180',
            'weather' => 'nullable|string|max:100',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'risk_level' => 'required|in:low,medium,high',
            'score' => 'nullable|numeric|min:0|max:100',
            'next_inspection_date' => 'nullable|date|after:inspection_date',
        ]);
    }
}
