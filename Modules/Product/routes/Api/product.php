<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\ProductController;

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