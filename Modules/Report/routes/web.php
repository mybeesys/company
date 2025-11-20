<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\SalesReportController;
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

    Route::controller(SalesReportController::class)->prefix('sales-report')->name('sales-reports.')->group(function () {
        Route::get('', 'index')->name('index');

        Route::get('sales-data', 'getSalesData')->name('get-sales-data');
    });
    Route::get('product-sales-report', [SalesReportController::class, 'getproductSellReport'])->name('product-sales-report');
    Route::get('product-purchase-report', [SalesReportController::class, 'getproductPurchaseReport'])->name('product-purchase-report');
    Route::get('purchase-payment-report', [SalesReportController::class, 'purchasePaymentReport'])->name('purchase-payment-report');
    Route::get('sell-payment-report', [SalesReportController::class, 'salesPaymentReport'])->name('sell-payment-report');
    Route::get('product-inventory-report', [SalesReportController::class, 'productInventoryReport'])->name('product-inventory-report');
    Route::get('Profit-Loss', [SalesReportController::class, 'getProfitLoss'])->name('Profit-Loss');
    Route::get('purchase-sell', [SalesReportController::class, 'getPurchaseSell'])->name('purchase-sell');
    Route::get('/reports/get-profit/{by?}', [SalesReportController::class, 'getProfit']);
    Route::get('branches', [SalesReportController::class, 'getBranches'])->name('branches');
    Route::get('getSuppliers', [SalesReportController::class, 'getSupplier'])->name('getSuppliers');
    Route::get('getCustomers', [SalesReportController::class, 'getCustomers'])->name('getCustomers');
    Route::get('products', [SalesReportController::class, 'getProducts'])->name('retrieveProducts');
    Route::get('devices', [SalesReportController::class, 'getDevices'])->name('devices');
    Route::get('/payment-reports', [SalesReportController::class, 'combinedPaymentReport'])->name('payment-reports.combined');
    Route::get('product-inventory-summary', [SalesReportController::class, 'productInventorySummary'])->name('product-inventory-summary');
    Route::get('/inventory/record/{product_id}/{establishment_id}', [SalesReportController::class, 'productInventoryRecord'])->name('inventory.record');
    Route::get('Product-Stock-Report', [SalesReportController::class, 'productStockReport'])->name('Product-Stock-Report');

});
