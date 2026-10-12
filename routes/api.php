<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Sanctum token auth)
|--------------------------------------------------------------------------
| Consumed by the mobile field app (Module 18) and by other ministries or
| state dashboards integrating over REST.
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::post('/projects/{project}/progress', [ProjectController::class, 'recordProgress']);
    Route::post('/projects/{project}/images', [ProjectImageController::class, 'store']);
    Route::get('/map/geojson', [MapController::class, 'geojson']);
});
