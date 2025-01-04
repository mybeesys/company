<?php

use Illuminate\Support\Facades\Route;
use Modules\Establishment\Http\Controllers\CompanyController;
use Modules\Establishment\Http\Controllers\EstablishmentController;
use Modules\Establishment\Models\Company;
use Modules\Establishment\Models\Establishment;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::controller(EstablishmentController::class)->prefix('establishment')->name('establishments.')->group(function () {
        Route::get('', 'index')->name('index')->can('viewAny', Establishment::class);
        Route::get('/{id}/edit', 'edit')->name('edit')->can('update', Establishment::class);
        Route::get('/create', 'create')->name('create')->can('create', Establishment::class);
        Route::post('/store', 'store')->name('store')->can('create', Establishment::class);
        Route::patch('/{establishment}', 'update')->name('update')->can('update', Establishment::class);
        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');

        Route::post('/restore/{establishment}', 'restore')->name('restore')->can('update', Establishment::class);
        Route::delete('/{establishment}', 'softDelete')->name('delete')->can('delete', Establishment::class);
        Route::delete('/force-delete/{establishment}', 'forceDelete')->name('forceDelete')->can('delete', Establishment::class);
    });

    Route::controller(CompanyController::class)->prefix('company')->name('companies.')->group(function () {
        Route::get('/setting', 'index')->name('settings')->can('viewAny', Company::class);
        Route::patch('/{id}', 'update')->name('update')->can('update', Company::class);
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
    });

});