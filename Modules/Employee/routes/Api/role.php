<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\PosRoleController;


Route::controller(PosRoleController::class)->middleware(['auth-central'])->group(function(){
    Route::get('/roles', 'getAllRoles');
});

Route::controller(PosRoleController::class)->middleware(['auth-central'])->group(function(){
    Route::get('/permissions', 'getAllPermissions');
});