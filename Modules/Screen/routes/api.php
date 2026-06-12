<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\Api\Player\ScreenPlayerAuthApiController;
use Modules\Screen\Http\Controllers\Api\Player\ScreenPlayerPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\Player\ScreenPlayerPromoApiController;
use Modules\Screen\Http\Controllers\Api\ScreenAuthApiController;
use Modules\Screen\Http\Controllers\Api\ScreenDeviceApiController;
use Modules\Screen\Http\Controllers\Api\ScreenMainApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPlaylistApiController;
use Modules\Screen\Http\Controllers\Api\ScreenPromoApiController;
use Modules\Screen\Http\Middleware\EnsureScreenPlayerToken;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::prefix('v1/screen')->group(function () {
        Route::post('auth/token', [ScreenAuthApiController::class, 'issueToken'])
            ->middleware('throttle:20,1')
            ->name('screen.auth.token');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('auth/revoke', [ScreenAuthApiController::class, 'revokeCurrent'])
                ->name('screen.auth.revoke');

            Route::get('dashboard', [ScreenMainApiController::class, 'dashboard']);

            Route::apiResource('promos', ScreenPromoApiController::class);

            Route::get('playlists/{playlist}/promos', [ScreenPlaylistApiController::class, 'promos']);
            Route::apiResource('playlists', ScreenPlaylistApiController::class);

            Route::get('devices/by-establishments', [ScreenDeviceApiController::class, 'byEstablishments']);
            Route::apiResource('devices', ScreenDeviceApiController::class);
        });

        Route::prefix('player')->name('screen.player.')->group(function () {
            Route::post('auth/token', [ScreenPlayerAuthApiController::class, 'issueToken'])
                ->middleware('throttle:20,1')
                ->name('auth.token');

            Route::middleware(['auth:sanctum', EnsureScreenPlayerToken::class])->group(function () {
                Route::post('auth/revoke', [ScreenPlayerAuthApiController::class, 'revokeCurrent'])
                    ->name('auth.revoke');
                Route::get('me', [ScreenPlayerAuthApiController::class, 'me'])
                    ->name('me');

                Route::get('playlists/{playlist}/promos', [ScreenPlayerPlaylistApiController::class, 'promos'])
                    ->name('playlists.promos');
                Route::get('playlists/{playlist}', [ScreenPlayerPlaylistApiController::class, 'show'])
                    ->name('playlists.show');
                Route::get('playlists', [ScreenPlayerPlaylistApiController::class, 'index'])
                    ->name('playlists.index');

                Route::get('promos/{promo}', [ScreenPlayerPromoApiController::class, 'show'])
                    ->name('promos.show');
                Route::get('promos', [ScreenPlayerPromoApiController::class, 'index'])
                    ->name('promos.index');
            });
        });
    });
});
