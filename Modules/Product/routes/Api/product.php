<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\ProductController;
use Modules\Product\Http\Controllers\Api\ServiceFeeController;
use Modules\Product\Http\Controllers\Api\DiscountController;

Route::controller(ProductController::class)->group(function(){
    Route::get('/products', 'products');
});
Route::controller(ProductController::class)->group(function(){
    Route::get('/categories', 'categories');
});
Route::controller(ProductController::class)->group(function(){
    Route::get('/products/{id?}', 'product');
});
Route::controller(ProductController::class)->group(function(){
    Route::get('/modifiers', 'modifiers');
});
Route::controller(ProductController::class)->group(function(){
    Route::get('/attributes', 'attributes');
});
Route::controller(ServiceFeeController::class)->group(function(){
    Route::get('/serviceFeeTypes', 'serviceFeeTypes');
});
Route::controller(ServiceFeeController::class)->group(function(){
    Route::get('/serviceFeeApplicationTypes', 'serviceFeeApplicationTypes');
});
Route::controller(ServiceFeeController::class)->group(function(){
    Route::get('/serviceFeeCalculationMethods', 'serviceFeeCalculationMethods');
});
Route::controller(ServiceFeeController::class)->group(function(){
    Route::get('/serviceFeeAutoApplyTypes', 'serviceFeeAutoApplyTypes');
});
Route::controller(ServiceFeeController::class)->group(function(){
    Route::get('/serviceFees', 'serviceFees');
});
Route::controller(DiscountController::class)->group(function(){
    Route::get('/discountFunctions', 'discountFunctions');
});
Route::controller(DiscountController::class)->group(function(){
    Route::get('/discountTypes', 'discountTypes');
});
Route::controller(DiscountController::class)->group(function(){
    Route::get('/discountQualifications', 'discountQualifications');
});
Route::controller(DiscountController::class)->group(function(){
    Route::get('/discountQualificationTypes', 'discountQualificationTypes');
});
Route::controller(DiscountController::class)->group(function(){
    Route::get('/discounts', 'discounts');
});
