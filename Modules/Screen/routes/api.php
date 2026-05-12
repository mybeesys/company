<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::prefix('v1/screen')->group(function () {
        Route::get('dashboard', [ScreenMainApiController::class, 'dashboard']);

        Route::apiResource('promos', ScreenPromoApiController::class);

        Route::get('playlists/{playlist}/promos', [ScreenPlaylistApiController::class, 'promos']);
        Route::apiResource('playlists', ScreenPlaylistApiController::class);

        Route::get('devices/by-establishments', [ScreenDeviceApiController::class, 'byEstablishments']);
        Route::apiResource('devices', ScreenDeviceApiController::class);
    });
});
