<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;

/*
|--------------------------------------------------------------------------
| Screen Admin API (POS / central token)
|--------------------------------------------------------------------------
|
| نسخة منفصلة عن /api/v1/screen — نفس منطق الإدارة (promos, playlists, devices)
| لكن المصادقة عبر auth-central (نفس Bearer token المستخدم في APIs المنتجات والمبيعات).
|
| Base: /api/admin/...
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth-central'])->group(function () {
    Route::get('dashboard', [ScreenMainApiController::class, 'dashboard'])->name('dashboard');

    Route::apiResource('promos', ScreenPromoApiController::class);

    Route::get('playlists/{playlist}/promos', [ScreenPlaylistApiController::class, 'promos'])
        ->name('playlists.promos');
    Route::apiResource('playlists', ScreenPlaylistApiController::class);

    Route::get('devices/by-establishments', [ScreenDeviceApiController::class, 'byEstablishments'])
        ->name('devices.by-establishments');
    Route::apiResource('devices', ScreenDeviceApiController::class);
});
