<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\AreaController;
use Modules\Reservation\Http\Controllers\OrderController;
use Modules\Reservation\Http\Controllers\TableController;
use Modules\Reservation\Http\Controllers\TableStatusTypeController;
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

    Route::middleware(['auth'])->group(function () {
        Route::resource('area', AreaController::class)->names('area');
        Route::get('areaList', [AreaController::class, 'getAreas'])->name('areaList');
        Route::get('areaMiniList', [AreaController::class, 'getMiniAreas'])->name('areaMiniList');
        Route::resource('table', TableController::class)->names('table');
        Route::get('tableList', [TableController::class, 'getTables'])->name('tableList');
        Route::get('table-status-type-values', [TableStatusTypeController::class, 'getTableStatusTypeValues'])->name('table-status-type-values');
        Route::get('/areaQR', [AreaController::class, 'areaQR'])->name('reservation.areaQR');
        Route::get('searchAreas', [AreaController::class, 'searchAreas'])->name('searchAreas');
        Route::get('/menuQR', [OrderController::class, 'menuQR'])->name('reservation.menuQR');
        Route::get('/menu-qr/custom-menus', [OrderController::class, 'customMenusForQr'])->name('reservation.menuQr.customMenus');
        Route::get('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'customMenuSchedule'])->name('reservation.menuQr.customMenus.schedule');
        Route::put('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'updateCustomMenuSchedule'])->name('reservation.menuQr.customMenus.schedule.update');
        Route::post('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'updateCustomMenuSchedule']);
        Route::get('/menu-qr/tokens', [OrderController::class, 'menuQrTokens'])->name('reservation.menuQr.tokens');
        Route::put('/menu-qr/tokens/{id}', [OrderController::class, 'updateMenuQrToken'])->name('reservation.menuQr.tokens.update');
        Route::delete('/menu-qr/tokens/{id}', [OrderController::class, 'deleteMenuQrToken'])->name('reservation.menuQr.tokens.delete');
        Route::get('/menu-feedback', [OrderController::class, 'menuFeedbackIndex'])->name('reservation.menuFeedback.index');
    });
    Route::get('/menu/{id}', [OrderController::class, 'menu'])->name('reservation.menu');
    Route::get('/menuSimple/{token}', [OrderController::class, 'menuSimple'])->name('reservation.menuSimple');
    Route::post('/menuSimple/{token}/feedback', [OrderController::class, 'storeMenuFeedback'])->name('reservation.menuSimple.feedback');
    Route::get('/order/products', [OrderController::class, 'products'])->name('order.products');

    Route::post('/generate-menu-token', [OrderController::class, 'generateToken']);
});
