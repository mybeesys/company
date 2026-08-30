<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Sales\Http\Controllers\CouponController;
use Modules\Sales\Http\Controllers\QuotationController;
use Modules\Sales\Http\Controllers\ReceiptsController;
use Modules\Sales\Http\Controllers\SellController;
use Modules\Sales\Http\Controllers\SellReturnController;
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

        Route::get('sales-dashbord', [SellController::class, 'salesDashbord'])->name('sales-dashbord');
        Route::get('sales-dashbord/export-csv', [SellController::class, 'salesDashboardExportCsv'])->name('sales-dashbord-export-csv');
        Route::get('sales-dashbord/export-pdf', [SellController::class, 'salesDashboardExportPdf'])->name('sales-dashbord-export-pdf');
        Route::get('invoices', [SellController::class, 'index'])->name('invoices');
        Route::get('sales-favorites', [SellController::class, 'favorites'])->name('sales-favorites');
        Route::get('create-invoice', [SellController::class, 'create'])->name('create-invoice');
        Route::get('convert-to-invoice', [SellController::class, 'create'])->name('convert-to-invoice');
        Route::post('store-invoice', [SellController::class, 'store'])->name('store-invoice');
        Route::get('edit-invoice/{transaction}', [SellController::class, 'edit'])->name('edit-invoice');
        Route::put('update-invoice/{transaction}', [SellController::class, 'update'])->name('update-invoice');
        Route::delete('destroy-invoice/{transaction}', [SellController::class, 'destroy'])->name('destroy-invoice');
        Route::get('products-for-sale', [ProductController::class, 'productsForSale'])->name('products-for-sale');
        Route::post('invoice-inventory-costs', [SellController::class, 'invoiceInventoryCosts'])->name('web.invoice-inventory-costs');
        Route::get('internal-consumption-types', [\Modules\General\Http\Controllers\InternalConsumptionTypesApiController::class, 'index'])->name('web.internal-consumption-types');
        Route::get('products-for-quotation', [ProductController::class, 'productsForQuotation'])->name('products-for-quotation');
        Route::get('products-for-client', [ProductController::class, 'productsForClient'])->name('products-for-client');
        Route::get('product-sell-extras/{id}', [SellController::class, 'productSellExtras'])->name('product-sell-extras');

        Route::get('sell-return', [SellReturnController::class, 'index'])->name('sell-return');
        Route::get('create-sell-return/{id}', [SellReturnController::class, 'create'])->name('create-sell-return');
        Route::get('create-sell-return-invoice', [SellReturnController::class, 'createSellReturn'])->name('create-sell-return-invoice');
        Route::post('store-sell-return', [SellReturnController::class, 'store'])->name('store-sell-return');
        Route::post('store-sell-return-invoice', [SellReturnController::class, 'storeSellReturn'])->name('store-sell-return-invoice');

        Route::get('quotations', [QuotationController::class, 'index'])->name('quotations');
        Route::get('create-quotation', [QuotationController::class, 'create'])->name('create-quotation');
        Route::post('store-quotation', [QuotationController::class, 'store'])->name('store-quotation');

        Route::get('receipts', [ReceiptsController::class, 'index'])->name('receipts');
        Route::get('create-receipts', [ReceiptsController::class, 'create'])->name('create-receipts');
        Route::post('store-receipts', [ReceiptsController::class, 'store'])->name('store-receipts');

        Route::get('receipts-payments/{payment}/edit', [ReceiptsController::class, 'editPayment'])->name('receipts-payments.edit');
        Route::put('receipts-payments/{payment}', [ReceiptsController::class, 'updatePayment'])->name('receipts-payments.update');
        Route::delete('receipts-payments/{payment}', [ReceiptsController::class, 'destroyPayment'])->name('receipts-payments.destroy');
        Route::get('receipts-payments/{payment}/duplicate', [ReceiptsController::class, 'duplicatePayment'])->name('receipts-payments.duplicate');

        Route::get('get-transactions/{clientId}', [ReceiptsController::class, 'getTransactions'])->name('get-transactions');

        Route::controller(CouponController::class)->prefix('coupon')->name('coupons.')->group(function () {
            Route::get('', 'index')->name('index');
            Route::post('/store', 'store')->name('store');
            Route::delete('/{coupon}', 'destroy')->name('delete');
            Route::delete('/force-delete/{coupon}', 'forceDelete')->name('force-delete');
            Route::post('/restore/{id}', 'restore')->name('restore');

            Route::get('get-details/{id}', 'getCouponDetails')->name('get-details');

            Route::get('/generate-code', 'generateCode')->name('generate-code');
        });
    });
});
