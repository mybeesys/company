<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Http\Controllers\Api\OrderController as ApiOrderController;
use Modules\Reservation\Http\Controllers\Api\TableConrtollerController;
use Modules\Reservation\Http\Controllers\OrderController;

Route::controller(OrderController::class)->group(function () {
    Route::post('/order', 'store');
});


Route::controller(TableConrtollerController::class)->group(function () {
    Route::get('/tables', 'index');
    Route::get('/get-tables', 'tables');
    Route::get('/tables/{id}', 'details');
});


Route::post('/new-order', [ApiOrderController::class, 'storeApi']);
