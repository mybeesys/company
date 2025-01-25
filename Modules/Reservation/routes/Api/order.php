<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\OrderController;

Route::controller(OrderController::class)->group(function(){
    Route::post('/order', 'store');
});