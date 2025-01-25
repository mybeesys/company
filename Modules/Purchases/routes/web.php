<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchases\Http\Controllers\PurchasesController;
use Modules\Purchases\Http\Controllers\PurchasesOrderController;
use Modules\Purchases\Http\Controllers\PurchasesReturnController;
use Modules\Purchases\Http\Controllers\SuppliersReceiptsController;
use Modules\Sales\Http\Controllers\ReceiptsController;
use Modules\Sales\Http\Controllers\SellController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

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
    PreventAccessFromCentralDomains::class,
])->group(function () {


    Route::middleware(['auth'])->group(function () {

        Route::get('purchase-invoices', [PurchasesController::class, 'index'])->name('purchase-invoices');
        Route::get('create-purchases-invoice', [PurchasesController::class, 'create'])->name('create-purchases-invoice');
        Route::post('store-purchases-invoice', [PurchasesController::class, 'store'])->name('store-purchases-invoice');




        Route::get('purchases-return', [PurchasesReturnController::class, 'index'])->name('purchases-return');
        Route::get('create-purchases-return/{id}', [PurchasesReturnController::class, 'create'])->name('create-purchases-return');
        Route::post('store-purchases-return', [PurchasesReturnController::class, 'store'])->name('store-purchases-return');

        Route::get('purchases-order', [PurchasesOrderController::class, 'index'])->name('purchases-order');
        Route::get('create-purchase-order', [PurchasesOrderController::class, 'create'])->name('create-purchase-order');
        Route::post('store-purchase-order', [PurchasesOrderController::class, 'store'])->name('store-purchase-order');
        Route::get('convert-po-to-invoice', [PurchasesOrderController::class, 'create'])->name('convert-po-to-invoice');


        Route::get('suppliers-receipts', [SuppliersReceiptsController::class, 'index'])->name('suppliers-receipts');
        Route::get('create-suppliers-receipts', [SuppliersReceiptsController::class, 'create'])->name('create-suppliers-receipts');
        Route::post('store-receipts', [ReceiptsController::class, 'store'])->name('store-receipts');
    });
});