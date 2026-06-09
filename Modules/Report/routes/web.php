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
    Route::get('product-sales-export-excel', [SalesReportController::class, 'productSalesExportExcel'])->name('product-sales-export-excel');
    Route::get('product-sales-export-pdf', [SalesReportController::class, 'productSalesExportPdf'])->name('product-sales-export-pdf');
    Route::get('sales-comparison-report', [SalesReportController::class, 'salesComparisonReport'])->name('sales-comparison-report');
    Route::get('weekday-sales-report', [SalesReportController::class, 'weekdaySalesReport'])->name('weekday-sales-report');
    Route::get('weekday-sales-export-excel', [SalesReportController::class, 'weekdaySalesExportExcel'])->name('weekday-sales-export-excel');
    Route::get('weekday-sales-export-pdf', [SalesReportController::class, 'weekdaySalesExportPdf'])->name('weekday-sales-export-pdf');
    Route::get('sales-comparison-chart-data', [SalesReportController::class, 'salesComparisonChartData'])->name('sales-comparison-chart-data');
    Route::get('sales-comparison-export-excel', [SalesReportController::class, 'salesComparisonExportExcel'])->name('sales-comparison-export-excel');
    Route::get('sales-comparison-export-pdf', [SalesReportController::class, 'salesComparisonExportPdf'])->name('sales-comparison-export-pdf');
    Route::get('comparison-lookup/categories', [SalesReportController::class, 'getComparisonCategories'])->name('comparison-categories');
    Route::get('comparison-lookup/subcategories', [SalesReportController::class, 'getComparisonSubcategories'])->name('comparison-subcategories');
    Route::get('comparison-lookup/units', [SalesReportController::class, 'getComparisonUnits'])->name('comparison-units');
    Route::get('comparison-lookup/payment-methods', [SalesReportController::class, 'getComparisonPaymentMethods'])->name('comparison-payment-methods');
    Route::get('product-purchase-report', [SalesReportController::class, 'getproductPurchaseReport'])->name('product-purchase-report');
    Route::get('product-purchase-export-excel', [SalesReportController::class, 'productPurchasesExportExcel'])->name('product-purchase-export-excel');
    Route::get('product-purchase-export-pdf', [SalesReportController::class, 'productPurchasesExportPdf'])->name('product-purchase-export-pdf');
    Route::get('comparison-lookup/purchase-subcategories', [SalesReportController::class, 'getPurchaseReportSubcategories'])->name('purchase-report-subcategories');
    Route::get('comparison-lookup/purchase-units', [SalesReportController::class, 'getPurchaseReportUnits'])->name('purchase-report-units');
    Route::get('comparison-lookup/purchase-payment-methods', [SalesReportController::class, 'getPurchaseReportPaymentMethods'])->name('purchase-report-payment-methods');
    Route::get('purchase-payment-report', [SalesReportController::class, 'purchasePaymentReport'])->name('purchase-payment-report');
    Route::get('purchase-payment-export-excel', [SalesReportController::class, 'purchasePaymentExportExcel'])->name('purchase-payment-export-excel');
    Route::get('purchase-payment-export-pdf', [SalesReportController::class, 'purchasePaymentExportPdf'])->name('purchase-payment-export-pdf');
    Route::get('sell-payment-report', [SalesReportController::class, 'salesPaymentReport'])->name('sell-payment-report');
    Route::get('sell-payment-export-excel', [SalesReportController::class, 'sellPaymentExportExcel'])->name('sell-payment-export-excel');
    Route::get('sell-payment-export-pdf', [SalesReportController::class, 'sellPaymentExportPdf'])->name('sell-payment-export-pdf');
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
    Route::get('product-movement-report', [SalesReportController::class, 'productMovementReport'])->name('product-movement-report');
    Route::get('/inventory/record/{product_id}/{establishment_id}', [SalesReportController::class, 'productInventoryRecord'])->name('inventory.record');
    Route::get('Product-Stock-Report', [SalesReportController::class, 'productStockReport'])->name('Product-Stock-Report');
    Route::get('Register-Report', [SalesReportController::class, 'getRegisterReport'])->name('Register-Report');
    Route::get('Register-Report/{id}', [SalesReportController::class, 'show'])->name('Register-Report');

});
