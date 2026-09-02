<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Support\SettingPermissions;
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
| Here is where you can register web routes for the application. These
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
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::get('area', [AreaController::class, 'index'])
            ->middleware($perm(...SettingPermissions::areaReadAny()))
            ->name('area.index');
        Route::post('area', [AreaController::class, 'store'])
            ->middleware($perm(...SettingPermissions::areaMutateAny()))
            ->name('area.store');
        Route::get('areaList', [AreaController::class, 'getAreas'])
            ->middleware($perm(...SettingPermissions::areaReadAny()))
            ->name('areaList');
        Route::get('areaMiniList', [AreaController::class, 'getMiniAreas'])
            ->middleware($perm(...SettingPermissions::areaReadAny()))
            ->name('areaMiniList');
        Route::get('searchAreas', [AreaController::class, 'searchAreas'])
            ->middleware($perm(...SettingPermissions::areaReadAny(), ...SettingPermissions::tableMutateAny()))
            ->name('searchAreas');

        Route::get('table', [TableController::class, 'index'])
            ->middleware($perm(SettingPermissions::TABLES_SHOW))
            ->name('table.index');
        Route::post('table', [TableController::class, 'store'])
            ->middleware($perm(...SettingPermissions::tableMutateAny()))
            ->name('table.store');
        Route::get('tableList', [TableController::class, 'getTables'])
            ->middleware($perm(...SettingPermissions::tableReadAny()))
            ->name('tableList');
        Route::get('table-status-type-values', [TableStatusTypeController::class, 'getTableStatusTypeValues'])
            ->middleware($perm(...SettingPermissions::tableReadAny()))
            ->name('table-status-type-values');

        Route::get('/areaQR', [AreaController::class, 'areaQR'])
            ->middleware($perm(SettingPermissions::TABLES_QR_SHOW))
            ->name('reservation.areaQR');

        Route::get('/menuQR', [OrderController::class, 'menuQR'])
            ->middleware($perm(SettingPermissions::MENU_QR_SHOW))
            ->name('reservation.menuQR');
        Route::get('/menu-qr/custom-menus', [OrderController::class, 'customMenusForQr'])
            ->middleware($perm(SettingPermissions::MENU_QR_SHOW, SettingPermissions::MENU_QR_UPDATE, SettingPermissions::MENU_QR_CREATE))
            ->name('reservation.menuQr.customMenus');
        Route::get('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'customMenuSchedule'])
            ->middleware($perm(SettingPermissions::MENU_QR_SHOW, SettingPermissions::MENU_QR_UPDATE))
            ->name('reservation.menuQr.customMenus.schedule');
        Route::put('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'updateCustomMenuSchedule'])
            ->middleware($perm(SettingPermissions::MENU_QR_UPDATE))
            ->name('reservation.menuQr.customMenus.schedule.update');
        Route::post('/menu-qr/custom-menus/{id}/schedule', [OrderController::class, 'updateCustomMenuSchedule'])
            ->middleware($perm(SettingPermissions::MENU_QR_UPDATE));
        Route::get('/menu-qr/tokens', [OrderController::class, 'menuQrTokens'])
            ->middleware($perm(SettingPermissions::MENU_QR_SHOW, SettingPermissions::MENU_QR_CREATE, SettingPermissions::MENU_QR_UPDATE))
            ->name('reservation.menuQr.tokens');
        Route::put('/menu-qr/tokens/{id}', [OrderController::class, 'updateMenuQrToken'])
            ->middleware($perm(SettingPermissions::MENU_QR_UPDATE))
            ->name('reservation.menuQr.tokens.update');
        Route::delete('/menu-qr/tokens/{id}', [OrderController::class, 'deleteMenuQrToken'])
            ->middleware($perm(SettingPermissions::MENU_QR_DELETE))
            ->name('reservation.menuQr.tokens.delete');
        Route::post('/generate-menu-token', [OrderController::class, 'generateToken'])
            ->middleware($perm(SettingPermissions::MENU_QR_CREATE));
        Route::get('/menu-feedback', [OrderController::class, 'menuFeedbackIndex'])
            ->middleware($perm(...SettingPermissions::for('menu_feedback', 'show')))
            ->name('reservation.menuFeedback.index');
    });

    Route::get('/menu/{id}', [OrderController::class, 'menu'])->name('reservation.menu');
    Route::get('/menuSimple/{token}', [OrderController::class, 'menuSimple'])->name('reservation.menuSimple');
    Route::post('/menuSimple/{token}/feedback', [OrderController::class, 'storeMenuFeedback'])->name('reservation.menuSimple.feedback');
    Route::get('/order/products', [OrderController::class, 'products'])->name('order.products');
});
