<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportLibraryController;
use App\Http\Controllers\AsvReportController;
use App\Http\Controllers\ReportQueryController;
use App\Http\Controllers\MachineMaintenanceController;
use App\Http\Controllers\MaintenanceTicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MaintenanceTicketDocumentController;
use App\Http\Controllers\MarketingCategoryIssueReportController;
use App\Http\Controllers\DispatchRequirementReportController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/', function () {
    return redirect()->route('dashboard');
});


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

    Route::middleware('auth')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');


        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

       Route::prefix('reports')->name('reports.')->group(function () {

        Route::get(
            '/library',
            [ReportLibraryController::class, 'index']
        )->name('library');

        Route::get(
            '/library/create',
            [ReportLibraryController::class, 'create']
        )->name('library.create');

        Route::post(
            '/library',
            [ReportLibraryController::class, 'store']
        )->name('library.store');

        Route::get(
            '/library/{report}/edit',
            [ReportLibraryController::class, 'edit']
        )->name('library.edit');

        Route::put(
            '/library/{report}',
            [ReportLibraryController::class, 'update']
        )->name('library.update');


        /*
        |--------------------------------------------------------------------------
        | Query Builder
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/library/{report}/query-builder',
            [ReportQueryController::class, 'builder']
        )->name('library.query-builder');

        Route::post(
            '/library/{report}/query-builder/generate',
            [ReportQueryController::class, 'generate']
        )->name('library.query-builder.generate');

        Route::post(
            '/library/{report}/query-builder/save',
            [ReportQueryController::class, 'save']
        )->name('library.query-builder.save');

        Route::post(
            '/library/{report}/query-builder/execute',
            [ReportQueryController::class, 'execute']
        )->name('library.query-builder.execute');


        /*
        |--------------------------------------------------------------------------
        | Database Metadata
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/database/tables',
            [ReportQueryController::class, 'databaseTables']
        )->name('database.tables');

        Route::get(
            '/database/columns',
            [ReportQueryController::class, 'databaseColumns']
        )->name('database.columns');


        /*
        |--------------------------------------------------------------------------
        | Actual Reports
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/asv',
            [AsvReportController::class, 'index']
        )->name('asv');


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT: Show must remain LAST
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/library/{report}',
            [ReportLibraryController::class, 'show']
        )->name('library.show');

    });

     Route::prefix('report-queries')
        ->name('report-queries.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | All saved queries
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                [ReportQueryController::class, 'index']
            )->name('index');

            /*
            |--------------------------------------------------------------------------
            | View saved query as report
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{query}/report',
                [ReportQueryController::class, 'viewReport']
            )->name('report');

            Route::get(
                '/report-queries/{query}/report',
                [ReportQueryController::class, 'viewReport']
            )->name('report');
            
            Route::get(
                '/report-queries/{query}/filter-options',
                [ReportQueryController::class, 'filterOptions']
            )->name('filter-options');

        });
        


        /*
        |--------------------------------------------------------------------------
        | Future ERP Modules
        |--------------------------------------------------------------------------
        */

        Route::get('/inventory', function () {
            return view('inventory.index');
        })->name('inventory');

        Route::get('/masters', function () {
            return view('masters.index');
        })->name('masters');



        Route::prefix('machine-maintenance')
            ->name('machine-maintenance.')
            ->controller(MachineMaintenanceController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/', 'store')->name('store');
                Route::post('/bulk-update', 'update')->name('update');
                Route::delete('/{machineMaintenance}', 'destroy')->name('destroy');
                Route::get('/export', 'export')->name('export');
                Route::get('/exportPdf', 'exportPdf')->name('exportPdf');

                
        });

        // Add this group alongside your existing routes:
        Route::prefix('maintenance-tickets')
        ->name('maintenance-tickets.')
        ->controller(MaintenanceTicketController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/export','export')->name('export');
            Route::post('/', 'store')->name('store');

            Route::get('/{maintenanceTicket}', 'show')->name('show');

            Route::post('/{maintenanceTicket}/inspection', 'inspection')->name('inspection');
            Route::post('/{maintenanceTicket}/expert', 'expert')->name('expert');
            Route::post('/{maintenanceTicket}/approval', 'approval')->name('approval');
            Route::post('/{maintenanceTicket}/start-repair', 'startRepair')->name('start-repair');
            Route::post('/{maintenanceTicket}/complete-repair', 'completeRepair')->name('complete-repair');
            Route::post('/{maintenanceTicket}/verify', 'verify')->name('verify');
            
            // Route::get('/export','export')->name('export');



            
        });

        


        Route::post(
            '/maintenance-tickets/{maintenanceTicket}/documents',
            [MaintenanceTicketDocumentController::class, 'store']
        )->name('maintenance-tickets.documents.store');

        Route::get(
            '/maintenance-tickets/{maintenanceTicket}/documents/{document}/download',
            [MaintenanceTicketDocumentController::class, 'download']
        )->name('maintenance-tickets.documents.download');


        

        Route::get(
            '/reports/marketing-category-issue',
            [MarketingCategoryIssueReportController::class, 'index']
        )->name('marketing-issue-report.index');

        Route::post(
            '/reports/marketing-category-issue/remarks',
            [MarketingCategoryIssueReportController::class, 'saveRemark']
        )->name('marketing-issue-report.remarks.save');

        


        Route::get(
            '/reports/dispatch-requirement',
            [DispatchRequirementReportController::class, 'index']
        )->name('dispatch-requirement.index');

        Route::post(
            '/reports/dispatch-requirement/stock',
            [DispatchRequirementReportController::class, 'saveStock']
        )->name('dispatch-requirement.stock.save');

        Route::get(
            '/reports/dispatch-requirement/export',
            [DispatchRequirementReportController::class, 'exportExcel']
        )->name('dispatch-requirement.export');



    });


/*
|--------------------------------------------------------------------------
| Laravel Breeze Authentication Routes
|--------------------------------------------------------------------------
|
| This loads login, logout, registration, password reset, etc.
|
*/

require __DIR__ . '/auth.php';