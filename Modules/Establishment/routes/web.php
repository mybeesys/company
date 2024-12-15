<?php

use Illuminate\Support\Facades\Route;
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
        Route::get('/create', 'create')->name('create');
        Route::get('/store', 'store')->name('store');
        Route::get('/create/validate', 'createLiveValidation')->name('create.validation');
        Route::get('/update/validate', 'updateLiveValidation')->name('update.validation');
    });

});