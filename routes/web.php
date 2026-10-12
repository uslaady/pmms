<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectImageController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskIssueController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:10,1']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Every signed-in user can change their own password
Route::middleware('auth')->group(function () {
    Route::get('/profile/password', [App\Http\Controllers\ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::post('/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::redirect('/', '/dashboard');

/*
|--------------------------------------------------------------------------
| Help Center, FAQs, Cookies (public — linked from the global footer)
|--------------------------------------------------------------------------
*/
Route::get('/help', [App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
Route::get('/faqs', [App\Http\Controllers\HelpController::class, 'faqs'])->name('help.faqs');
Route::get('/cookies', [App\Http\Controllers\HelpController::class, 'cookies'])->name('help.cookies');

/*
|--------------------------------------------------------------------------
| Authenticated application
|--------------------------------------------------------------------------
| Fine-grained permission checks run both here (route middleware) and
| inside controllers. Public Viewer only ever uses the /public routes.
*/
Route::middleware(['auth', 'log.audit', 'directorate'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')->middleware('permission:dashboard.view');

    // District -> LGA -> Ward tree for cascading location selects
    Route::get('/locations/tree', [App\Http\Controllers\LocationController::class, 'tree'])
        ->name('locations.tree')->middleware('permission:projects.view');

    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('analytics')->middleware('permission:dashboard.executive');

    // GIS map (Module 11)
    Route::get('/map', [MapController::class, 'index'])->name('map')->middleware('permission:gis.view');
    Route::get('/map/geojson', [MapController::class, 'geojson'])->name('map.geojson')->middleware('permission:gis.view');

    // Projects
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index')->middleware('permission:projects.view');
        Route::post('/', [ProjectController::class, 'store'])->name('store')->middleware('permission:projects.create');

        // Bulk registration — must be declared BEFORE the /{project} wildcard
        Route::get('/bulk/template', [ProjectController::class, 'downloadTemplate'])->name('bulk.template')->middleware('permission:projects.create');
        Route::post('/bulk', [ProjectController::class, 'bulkStore'])->name('bulk.store')->middleware('permission:projects.create');

        Route::get('/{project}', [ProjectController::class, 'show'])->name('show')->middleware('permission:projects.view');
        // No permission middleware: locked projects may be edited by the executives only (checked in the controller)
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::post('/{project}/contractor', [ProjectController::class, 'assignContractor'])->name('contractor.assign')->middleware('permission:projects.assign_contractor');
        Route::post('/{project}/reject', [ProjectController::class, 'reject'])->name('reject')->middleware('permission:projects.approve');
        Route::post('/{project}/resubmit', [ProjectController::class, 'resubmit'])->name('resubmit');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy')->middleware('permission:projects.delete');
        Route::post('/{project}/status', [ProjectController::class, 'changeStatus'])->name('status')->middleware('permission:projects.approve');
        Route::post('/{project}/location', [MapController::class, 'updateLocation'])->name('location')->middleware('permission:gis.manage');

        Route::post('/{project}/progress', [ProjectController::class, 'recordProgress'])->name('progress.store')->middleware('permission:progress.update');
        Route::post('/{project}/milestones', [MilestoneController::class, 'store'])->name('milestones.store')->middleware('permission:milestones.update');
        Route::post('/{project}/inspections', [InspectionController::class, 'store'])->name('inspections.store')->middleware('permission:inspections.create');
        Route::post('/{project}/images', [ProjectImageController::class, 'store'])->name('images.store')->middleware('permission:images.upload');
        Route::post('/{project}/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('permission:documents.upload');
        Route::post('/{project}/contract', [ContractController::class, 'store'])->name('contract.store')->middleware('permission:contracts.manage');
        Route::post('/{project}/risks', [RiskIssueController::class, 'storeRisk'])->name('risks.store');
        Route::post('/{project}/issues', [RiskIssueController::class, 'storeIssue'])->name('issues.store');
    });

    Route::post('/updates/{update}/review', [ProjectController::class, 'reviewProgress'])
        ->name('updates.review')->middleware('permission:progress.approve');
    Route::post('/milestones/{milestone}', [MilestoneController::class, 'update'])
        ->name('milestones.update')->middleware('permission:milestones.update');
    Route::post('/risks/{risk}', [RiskIssueController::class, 'updateRisk'])->name('risks.update');
    Route::put('/inspections/{inspection}', [InspectionController::class, 'update'])->name('inspections.update')->middleware('permission:inspections.create');
    Route::delete('/inspections/{inspection}', [InspectionController::class, 'destroy'])->name('inspections.destroy')->middleware('permission:inspections.create');
    Route::delete('/images/{image}', [ProjectImageController::class, 'destroy'])->name('images.destroy')->middleware('permission:images.upload');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware('permission:documents.upload');
    Route::post('/issues/{issue}/resolve', [RiskIssueController::class, 'resolveIssue'])->name('issues.resolve');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Contractors (Module 3)
    Route::prefix('contractors')->name('contractors.')->middleware('permission:contractors.view')->group(function () {
        Route::get('/', [ContractorController::class, 'index'])->name('index');
        Route::post('/', [ContractorController::class, 'store'])->name('store')->middleware('permission:contractors.register');
        // Bulk registration — declared BEFORE the /{contractor} wildcard
        Route::get('/bulk/template', [ContractorController::class, 'downloadTemplate'])->name('bulk.template')->middleware('permission:contractors.register');
        Route::post('/bulk', [ContractorController::class, 'bulkStore'])->name('bulk.store')->middleware('permission:contractors.register');
        Route::get('/documents/{contractorDocument}/download', [ContractorController::class, 'downloadDocument'])->name('documents.download');
        Route::delete('/documents/{contractorDocument}', [ContractorController::class, 'destroyDocument'])->name('documents.destroy');

        Route::get('/{contractor}', [ContractorController::class, 'show'])->name('show');
        // Edit rights depend on the contractor's status (locked ones: executives only) — checked in the controller
        Route::put('/{contractor}', [ContractorController::class, 'update'])->name('update');
        Route::delete('/{contractor}', [ContractorController::class, 'destroy'])->name('destroy')->middleware('permission:contractors.delete');
        Route::post('/{contractor}/status', [ContractorController::class, 'setStatus'])->name('status')->middleware('permission:contractors.status');
        Route::post('/{contractor}/reject', [ContractorController::class, 'reject'])->name('reject')->middleware('permission:contractors.status');
        Route::post('/{contractor}/resubmit', [ContractorController::class, 'resubmit'])->name('resubmit')->middleware('permission:contractors.edit');
        Route::post('/{contractor}/documents', [ContractorController::class, 'storeDocument'])->name('documents.store')->middleware('permission:contractors.documents');
    });

    // Contracts & finance (Modules 4 & 10)
    Route::post('/contracts/{contract}/variations', [ContractController::class, 'storeVariation'])
        ->name('contracts.variations.store')->middleware('permission:contracts.manage');
    Route::post('/contracts/{contract}/payments', [PaymentController::class, 'store'])
        ->name('payments.store')->middleware('permission:payments.record');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])
        ->name('payments.approve')->middleware('permission:payments.approve');
    Route::post('/payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])
        ->name('payments.markpaid')->middleware('permission:payments.record');

    // Reports & audit (Modules 14 & 15)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports.view');
    Route::get('/reports/build', [ReportController::class, 'build'])->name('reports.build')->middleware('permission:reports.view');
    Route::get('/audit-log', [ReportController::class, 'auditLog'])->name('audit.index')->middleware('permission:audit.view');

    // Notifications (Module 13)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // User management (Module 1)
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('permission:users.create');
    Route::post('/users/{user}/status', [UserController::class, 'setStatus'])->name('users.status')->middleware('permission:users.status');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.password.reset')->middleware('permission:users.reset_password');
});

/*
|--------------------------------------------------------------------------
| Public transparency portal (optional, read-only, no login)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/projects', [PublicPortalController::class, 'index'])->name('projects');
    Route::get('/projects/{project}', [PublicPortalController::class, 'show'])->name('projects.show');
});
