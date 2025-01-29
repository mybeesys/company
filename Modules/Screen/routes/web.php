<?php

use Illuminate\Support\Facades\Route;
use Modules\Screen\Http\Controllers\MainController;
use Modules\Screen\Http\Controllers\PlaylistController;
use Modules\Screen\Http\Controllers\PromoController;
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
    Route::get('main', [MainController::class, 'index'])->name('screens.main');

    Route::controller(PromoController::class)->prefix('promo')->name('promos.')->group(function(){
        Route::post('/store', 'store')->name('store');

        Route::get('', 'index')->name('index');
        
        Route::get('playlist-index', 'playlistIndex')->name('playlist-index');

        Route::delete('/{promo}', 'destroy')->name('delete');

        route::patch('/{promo}', 'update')->name('update');
    });

    Route::controller(PlaylistController::class)->prefix('playlist')->name('playlists.')->group(function(){
        Route::post('/store', 'store')->name('store');
        Route::get('/index', 'index')->name('index');
    });
});
