<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\AuthController;
use Modules\Employee\Http\Controllers\PasswordResetController;
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

        Route::get('/auth/tenant-switch', \Modules\Employee\Http\Controllers\TenantSwitchAuthController::class)
            ->name('auth.tenant-switch');
    });

    Route::middleware(['guest'])->controller(PasswordResetController::class)->group(function () {
        Route::get('/forgot-password', 'forgotPasswordForm')->name('password.request');
        Route::post('/forgot-password', 'sendResetLink')->name('password.email');
        Route::get('/reset-password/{token}', 'resetPasswordForm')->name('password.reset');
        Route::post('/reset-password', 'resetPassword')->name('password.update');
    });

    Route::middleware(['auth'])->group(function () {

        Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    });
});
