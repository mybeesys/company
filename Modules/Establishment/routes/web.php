<?php

use Illuminate\Support\Facades\Route;
use Modules\Establishment\Http\Controllers\CompanyController;
use Modules\Establishment\Http\Controllers\EstablishmentController;
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
        Route::get('', 'index')->name('index');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::patch('/{establishment}', 'update')->name('update');
        Route::post('/create/validate', 'createLiveValidation')->name('create.validation');

        Route::post('/restore/{establishment}', 'restore')->name('restore');
        Route::delete('/{establishment}', 'softDelete')->name('delete');
        Route::delete('/force-delete/{establishment}', 'forceDelete')->name('forceDelete');
    });

    Route::controller(CompanyController::class)->prefix('company')->name('companies.')->group(function () {
        Route::get('/setting', 'index')->name('settings');
        Route::patch('/{id}', 'update')->name('update');
        Route::post('/update/validate', 'updateLiveValidation')->name('update.validation');
    });

});