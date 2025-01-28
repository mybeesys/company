<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\AreaController;
use Modules\Reservation\Http\Controllers\OrderController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Reservation\Http\Controllers\TableController;
use Modules\Reservation\Http\Controllers\TableStatusTypeController;

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
])->group( function () {

    Route::middleware(['auth'])->group(function () {
        Route::resource('area', AreaController::class)->names('area');
        Route::get('areaList', [AreaController::class, 'getAreas'])->name('areaList');
        Route::get('areaMiniList', [AreaController::class, 'getMiniAreas'])->name('areaMiniList');
        Route::resource('table', TableController::class)->names('table');
        Route::get('tableList', [TableController::class, 'getTables'])->name('tableList');
        Route::get('table-status-type-values', [TableStatusTypeController::class, 'getTableStatusTypeValues'])->name('table-status-type-values');;
        Route::get('/areaQR', [AreaController::class, 'areaQR'])->name('reservation.areaQR');
        Route::get('searchAreas', [AreaController::class, 'searchAreas'])->name('searchAreas');
        Route::get('/menuQR', [OrderController::class, 'menuQR'])->name('reservation.menuQR');
    });
    Route::get('/menu/{id}', [OrderController::class, 'menu'])->name('reservation.menu');
    Route::get('/menuSimple', [OrderController::class, 'menuSimple'])->name('reservation.menuSimple');
    Route::get('/order/products', [OrderController::class, 'products'])->name('order.products');
    
});
