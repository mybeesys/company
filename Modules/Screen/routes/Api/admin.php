<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\Admin\ScreenAdminAuthApiController;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;

/*
|--------------------------------------------------------------------------
| Screen Admin API (تطبيق الأدمن / POS — auth-central)
|--------------------------------------------------------------------------
|
| Base: /api/admin/v1/screen/...
|
*/

Route::prefix('admin/v1/screen')->name('admin.v1.screen.')->middleware(['auth-central'])->group(function () {
    Route::post('auth/token', [ScreenAdminAuthApiController::class, 'pair'])
        ->middleware('throttle:15,1')
        ->name('auth.token');

    Route::get('dashboard', [ScreenMainApiController::class, 'dashboard'])->name('dashboard');

    Route::apiResource('promos', ScreenPromoApiController::class);

    Route::get('playlists/{playlist}/promos', [ScreenPlaylistApiController::class, 'promos'])
        ->name('playlists.promos');
    Route::apiResource('playlists', ScreenPlaylistApiController::class);

    Route::get('devices/by-establishments', [ScreenDeviceApiController::class, 'byEstablishments'])
        ->name('devices.by-establishments');
    Route::apiResource('devices', ScreenDeviceApiController::class);
});
