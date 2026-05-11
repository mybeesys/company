<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
 *--------------------------------------------------------------------------
 * API Routes — شاشات (مواد إعلانية، قوائم تشغيل، أجهزة)
 *--------------------------------------------------------------------------
 *
 * المسار الكامل على المستأجر: /api/v1/screen/...
 * مع Sanctum والنطاق الفرعي للمستأجر (مثل test1.my-bee.info).
 */

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware('auth:sanctum')->prefix('v1/screen')->group(function () {
        Route::get('dashboard', [ScreenMainApiController::class, 'dashboard']);

        Route::apiResource('promos', ScreenPromoApiController::class);

        Route::get('playlists/{playlist}/promos', [ScreenPlaylistApiController::class, 'promos']);
        Route::apiResource('playlists', ScreenPlaylistApiController::class);

        Route::get('devices/by-establishments', [ScreenDeviceApiController::class, 'byEstablishments']);
        Route::apiResource('devices', ScreenDeviceApiController::class);
    });
});
