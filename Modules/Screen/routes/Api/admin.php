<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\Admin\ScreenAdminAuthApiController;
use Modules\Screen\Http\Controllers\Api\Admin\ScreenAdminDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;



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
    Route::post('devices/{device}/unlink', [ScreenAdminDeviceApiController::class, 'unlink'])
        ->middleware('throttle:30,1')
        ->name('devices.unlink');
    Route::apiResource('devices', ScreenDeviceApiController::class);
});