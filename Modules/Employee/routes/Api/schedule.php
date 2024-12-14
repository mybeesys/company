<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Api\EmployeeController;
use Modules\Employee\Http\Controllers\Api\TimeSheetRuleController;


Route::controller(TimeSheetRuleController::class)->middleware(['auth-central'])->group(function(){
    Route::get('/time-sheet-rules', 'index');
});