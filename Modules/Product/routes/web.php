<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Modules\Product\Http\Controllers\CategoryController;
use Modules\Product\Http\Controllers\SubCategoryController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/    
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class
])->group( function () {
    Route::resource('product', ProductController::class)->names('product');
    Route::resource('category', CategoryController::class)->names('category');
    Route::resource('subcategory', SubCategoryController::class)->names('subcategory');
    Route::get('categories', [CategoryController::class, 'getCategories'])->name('categoryList');
    Route::get('localization', [ProductController::class, 'localization'])->name('localization');
    Route::get('categories/{id}/subcategories', [CategoryController::class, 'getsubCategories'])->name('subcategoryList');
});
