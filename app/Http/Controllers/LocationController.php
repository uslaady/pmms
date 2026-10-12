<?php

namespace App\Http\Controllers;

use App\Models\District;

class LocationController extends Controller
{
    /**
     * Full District → LGA → Ward tree (with ward centroid coordinates)
     * consumed by the cascading, type-to-search selects on project forms.
     * ~310 wards, so one payload is cheap and cacheable.
     */
    public function tree()
    {
        $tree = District::orderBy('name')
            ->with(['lgas' => fn ($q) => $q->orderBy('name')->select('id', 'district_id', 'name'),
                    'lgas.wards' => fn ($q) => $q->orderBy('name')->select('id', 'lga_id', 'name', 'latitude', 'longitude')])
            ->get(['id', 'name']);

        return response()->json($tree)
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
