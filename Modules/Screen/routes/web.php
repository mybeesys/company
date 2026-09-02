<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\DeviceController;
use Modules\Screen\Http\Controllers\MainController;
use Modules\Screen\Http\Controllers\PlaylistController;
use Modules\Screen\Http\Controllers\PromoController;
use Modules\Screen\Support\ScreenPermissions;
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

        Route::get('main', [MainController::class, 'index'])
            ->middleware($perm(...ScreenPermissions::action('show')))
            ->name('screens.main');

        Route::controller(PromoController::class)->prefix('promo')->name('promos.')->group(function () use ($perm) {
            Route::post('/store', 'store')
                ->middleware($perm(...ScreenPermissions::for('promos', 'create')))
                ->name('store');
            Route::get('', 'index')
                ->middleware($perm(...ScreenPermissions::for('promos', 'show')))
                ->name('index');
            Route::get('playlist-index', 'playlistIndex')
                ->middleware($perm(...ScreenPermissions::playlistComposeAny()))
                ->name('playlist-index');
            Route::delete('/{promo}', 'destroy')
                ->middleware($perm(...ScreenPermissions::for('promos', 'delete')))
                ->name('delete');
            Route::patch('/{promo}', 'update')
                ->middleware($perm(...ScreenPermissions::for('promos', 'update')))
                ->name('update');
        });

        Route::controller(PlaylistController::class)->prefix('playlist')->name('playlists.')->group(function () use ($perm) {
            Route::post('/store', 'store')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'create')))
                ->name('store');
            Route::get('/index', 'index')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'show')))
                ->name('index');
            Route::get('get-promos/{playlist}', 'getPlaylistPromos')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'show')))
                ->name('get-playlist-promos');
            Route::get('/{playlist}', 'show')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'show'), ...ScreenPermissions::for('playlists', 'update')))
                ->name('show');
            Route::patch('/{playlist}', 'update')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'update')))
                ->name('update');
            Route::delete('/{playlist}', 'destroy')
                ->middleware($perm(...ScreenPermissions::for('playlists', 'delete')))
                ->name('delete');
        });

        Route::controller(DeviceController::class)->prefix('device')->name('devices.')->group(function () use ($perm) {
            Route::post('/store', 'store')
                ->middleware($perm(...ScreenPermissions::for('devices', 'create')))
                ->name('store');
            Route::get('/index', 'index')
                ->middleware($perm(...ScreenPermissions::for('devices', 'show')))
                ->name('index');
            Route::get('/by-establishments', 'byEstablishments')
                ->middleware($perm(...ScreenPermissions::devicePickerAny()))
                ->name('by-establishments');
            Route::post('/{device}/regenerate-screen-pairing', 'regenerateScreenPairing')
                ->middleware($perm(...ScreenPermissions::for('devices', 'update')))
                ->name('regenerate-screen-pairing');
            Route::patch('/{device}', 'update')
                ->middleware($perm(...ScreenPermissions::for('devices', 'update')))
                ->name('update');
            Route::delete('/{device}', 'destroy')
                ->middleware($perm(...ScreenPermissions::for('devices', 'delete')))
                ->name('delete');
        });
    });
});
