<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\EmployeeController;


Route::controller(EmployeeController::class)->middleware(['auth-central'])->group(function(){
    Route::get('/employees', 'index');
});