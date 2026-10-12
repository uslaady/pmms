<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Rows-per-page for data tables: reads ?per_page=, falls back to the
     * value remembered in the session, defaults to 25. Shared by every
     * module so the user's choice follows them across tables.
     */
    protected function perPage(Request $request, int $default = 25): int
    {
        $allowed = [10, 25, 50, 100, 250];
        $perPage = (int) $request->input('per_page', session('pmms_per_page', $default));
        if (! in_array($perPage, $allowed, true)) {
            $perPage = $default;
        }
        session(['pmms_per_page' => $perPage]);

        return $perPage;
    }
}
