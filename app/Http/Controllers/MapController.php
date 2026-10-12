<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * Module 11 — GIS map page (Leaflet + OpenStreetMap).
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('gis.view'), 403);
        return view('map.index', [
            'lgas' => \App\Models\Lga::orderBy('name')->get(),
            'contractors' => \App\Models\Contractor::orderBy('name')->get(),
        ]);
    }

    /**
     * GeoJSON feed consumed by the Leaflet map. Supports the same filters
     * as the project list: status, lga, contractor, year.
     */
    public function geojson(Request $request)
    {
        $query = Project::query()
            ->visibleTo($request->user())
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->with(['contractor:id,name', 'lga:id,name']);

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('lga_id'), fn ($q) => $q->where('lga_id', $request->integer('lga_id')));
        $query->when($request->filled('contractor_id'), fn ($q) => $q->where('contractor_id', $request->integer('contractor_id')));
        $query->when($request->filled('year'), fn ($q) => $q->whereYear('award_date', $request->integer('year')));

        $features = $query->get()->map(fn (Project $p) => [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [(float) $p->longitude, (float) $p->latitude]],
            'properties' => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'status' => $p->status,
                'color' => $p->status_color,
                'contractor' => $p->contractor?->name,
                'lga' => $p->lga?->name,
                'progress' => (float) $p->actual_progress_percent,
                'url' => route('projects.show', $p),
            ],
        ]);

        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    /**
     * GIS Officer corrects a project's coordinates.
     */
    public function updateLocation(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('gis.manage'), 403);

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $project->update($data);

        return back()->with('status', 'Location updated.');
    }
}
