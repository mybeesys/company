<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\AuthController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::controller(AuthController::class)->middleware(['guest'])->group(function () {

        Route::get('/login', 'index')->name('login');

        Route::post('/postlogin', 'login')->name('login.postLogin');
    });


    Route::middleware(['auth'])->group(function () {

        Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    });
});