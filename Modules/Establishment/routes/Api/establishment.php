<?php

use Illuminate\Support\Facades\Route;
use Modules\Establishment\Http\Controllers\Api\EstablishmentController;


Route::controller(EstablishmentController::class)->middleware(['auth-central'])->group(function(){
    Route::get('/establishments', 'index');
});