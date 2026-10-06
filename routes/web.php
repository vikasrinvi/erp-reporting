<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportLibraryController;
use App\Http\Controllers\AsvReportController;
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

        /*
        |--------------------------------------------------------------------------
        | Report Library
        |--------------------------------------------------------------------------
        */

        Route::get('/library', [ReportLibraryController::class, 'index'])
            ->name('library');

        Route::get('/library/create', [ReportLibraryController::class, 'create'])
            ->name('library.create');

        Route::post('/library', [ReportLibraryController::class, 'store'])
            ->name('library.store');

        /*
         * IMPORTANT:
         * /edit must come BEFORE /{report}
         */

        Route::get('/library/{report}/edit', [ReportLibraryController::class, 'edit'])
            ->name('library.edit');

        Route::put('/library/{report}', [ReportLibraryController::class, 'update'])
            ->name('library.update');

        Route::get('/library/{report}', [ReportLibraryController::class, 'show'])
            ->name('library.show');


        /*
        |--------------------------------------------------------------------------
        | ASV Item Issue Report
        |--------------------------------------------------------------------------
        */

        Route::get('/asv', [AsvReportController::class, 'index'])
            ->name('asv');
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