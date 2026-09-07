<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CrimeController;
use App\Http\Controllers\CrimeMapController;
use App\Http\Controllers\CrimeTypeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\HotspotController;
use App\Http\Controllers\OffenseTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Breeze Auth routes
require __DIR__.'/auth.php';

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,police_officer')->group(function () {
        Route::get('/crimes/create', [CrimeController::class, 'create'])->name('crimes.create');
        Route::post('/crimes', [CrimeController::class, 'store'])->name('crimes.store');
        Route::post('/map/plot-incident', [CrimeMapController::class, 'plotIncident'])->name('map.plot-incident');
    });

    Route::middleware('role:admin,police_officer,investigator')->group(function () {
        // Crime Management
        Route::resource('crimes', CrimeController::class)->except(['create', 'store']);
        Route::get('/crimes/{crime}/print', [CrimeController::class, 'print'])->name('crimes.print');
        Route::post('/crimes/{crime}/update-status', [CrimeController::class, 'updateStatus'])->name('crimes.update-status');
        Route::post('/crimes/{crime}/approval', [CrimeController::class, 'approve'])->middleware('role:admin')->name('crimes.approval');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

        // Evidence
        Route::get('/evidence/{evidence}/file', [EvidenceController::class, 'show'])->name('evidence.show');
        Route::delete('/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');

        // Crime Map
        Route::get('/map', [CrimeMapController::class, 'index'])->name('map.index');
        Route::get('/map/data', [CrimeMapController::class, 'mapData'])->name('map.data');
        Route::get('/map/barangays', [CrimeMapController::class, 'barangayBoundaries'])->name('map.barangays');
        Route::get('/map/detect-barangay', [CrimeMapController::class, 'detectBarangay'])->name('map.detect-barangay');

        // Hotspots
        Route::get('/hotspots', [HotspotController::class, 'index'])->name('hotspots.index');
    });

    // Analytics (Admin and LGU statistics viewer)
    Route::middleware('role:admin,lgu')->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/trends', [AnalyticsController::class, 'getCrimeTrends'])->name('trends');
        Route::get('/by-type', [AnalyticsController::class, 'getCrimeByType'])->name('by-type');
        Route::get('/by-barangay', [AnalyticsController::class, 'getCrimeByBarangay'])->name('by-barangay');
        Route::get('/status', [AnalyticsController::class, 'getStatusBreakdown'])->name('status');
        Route::get('/monthly-report', [AnalyticsController::class, 'getMonthlyReport'])->name('monthly');
        Route::get('/summary-report', [AnalyticsController::class, 'summaryReport'])->name('summary-report');
    });

    // User Management (Admin only)
    Route::middleware('role:admin')->resource('users', UserController::class)->except('show');
    Route::middleware('role:admin')->resource('crime-types', CrimeTypeController::class)->except('show');
    Route::middleware('role:admin')->resource('offense-types', OffenseTypeController::class)->except('show');
    Route::middleware('role:admin')->get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::middleware('role:admin')->get('/crimes-import', [CrimeController::class, 'importForm'])->name('crimes.import-form');
    Route::middleware('role:admin')->post('/crimes-import', [CrimeController::class, 'import'])->name('crimes.import');
    Route::middleware('role:admin')->get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::middleware('role:admin')->get('/backups/download', [BackupController::class, 'download'])->name('backups.download');

    // Reports (Admin generator; LGU users are guided to analytics reports)
    Route::middleware('role:admin,lgu')->get('/reports', function () {
        if (auth()->user()->isLgu()) {
            return redirect()
                ->route('analytics.index')
                ->with('info', 'LGU accounts can view analytics and generate summary reports from this page.');
        }

        return app(ReportController::class)->index();
    })->name('reports.index');

    Route::middleware('role:admin')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/preview', [ReportController::class, 'generate'])->name('preview');
        Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export-pdf');
    });

    // Dashboard API data
    Route::get('/dashboard/api-data', [DashboardController::class, 'apiData'])->name('dashboard.api-data');
});
