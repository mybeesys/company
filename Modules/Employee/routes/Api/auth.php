<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\AuthController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/employee-logout', [AuthController::class, 'destroy'])->name('employee-logout');
});

Route::middleware(['auth-central'])->group(function(){
    Route::post('employee-login', [AuthController::class, 'store'])->name('employee-login');
});

