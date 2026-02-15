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
    Route::post('/change-status/{id}', 'changeStatus');
});


Route::post('/new-order', [ApiOrderController::class, 'storeApi']);
Route::post('/cancel-order', [ApiOrderController::class, 'cancelOrder']);
Route::get('/establishment-orders/{id}', [ApiOrderController::class, 'establishmentOrders']);
Route::get('/orders', [ApiOrderController::class, 'orders']);
Route::post('/update-orders/{id}', [ApiOrderController::class, 'updateOrders']);
Route::post('/types-of-service', [ApiOrderController::class, 'typesOfService']);



