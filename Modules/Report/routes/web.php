<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Http\Controllers\SalesReportController;
use Modules\Report\Support\ReportPermissions;
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
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        $sellPayment = ReportPermissions::report('sell-payment-report');
        $productSales = ReportPermissions::report('product-sales-report');
        $salesComparison = ReportPermissions::report('sales-comparison-report');
        $weekdaySales = ReportPermissions::report('weekday-sales-report');
        $purchasePayment = ReportPermissions::report('purchase-payment-report');
        $productPurchase = ReportPermissions::report('product-purchase-report');
        $productInventory = ReportPermissions::report('product-inventory-report');
        $inventorySummary = ReportPermissions::report('product-inventory-summary');
        $productStock = ReportPermissions::report('Product-Stock-Report');
        $profitLoss = ReportPermissions::report('Profit-Loss');
        $purchaseSell = ReportPermissions::report('purchase-sell');
        $register = ReportPermissions::report('Register-Report');

        Route::controller(SalesReportController::class)->prefix('sales-report')->name('sales-reports.')->group(function () use ($perm, $sellPayment) {
            Route::get('', 'index')
                ->middleware($perm($sellPayment['show']))
                ->name('index');
            Route::get('sales-data', 'getSalesData')
                ->middleware($perm($sellPayment['show']))
                ->name('get-sales-data');
        });

        Route::get('product-sales-report', [SalesReportController::class, 'getproductSellReport'])
            ->middleware($perm($productSales['show']))
            ->name('product-sales-report');
        Route::get('product-sales-export-excel', [SalesReportController::class, 'productSalesExportExcel'])
            ->middleware($perm($productSales['print']))
            ->name('product-sales-export-excel');
        Route::get('product-sales-export-pdf', [SalesReportController::class, 'productSalesExportPdf'])
            ->middleware($perm($productSales['print']))
            ->name('product-sales-export-pdf');

        Route::get('sales-comparison-report', [SalesReportController::class, 'salesComparisonReport'])
            ->middleware($perm($salesComparison['show']))
            ->name('sales-comparison-report');
        Route::get('weekday-sales-report', [SalesReportController::class, 'weekdaySalesReport'])
            ->middleware($perm($weekdaySales['show']))
            ->name('weekday-sales-report');
        Route::get('weekday-sales-export-excel', [SalesReportController::class, 'weekdaySalesExportExcel'])
            ->middleware($perm($weekdaySales['print']))
            ->name('weekday-sales-export-excel');
        Route::get('weekday-sales-export-pdf', [SalesReportController::class, 'weekdaySalesExportPdf'])
            ->middleware($perm($weekdaySales['print']))
            ->name('weekday-sales-export-pdf');
        Route::get('sales-comparison-chart-data', [SalesReportController::class, 'salesComparisonChartData'])
            ->middleware($perm($salesComparison['show']))
            ->name('sales-comparison-chart-data');
        Route::get('sales-comparison-export-excel', [SalesReportController::class, 'salesComparisonExportExcel'])
            ->middleware($perm($salesComparison['print']))
            ->name('sales-comparison-export-excel');
        Route::get('sales-comparison-export-pdf', [SalesReportController::class, 'salesComparisonExportPdf'])
            ->middleware($perm($salesComparison['print']))
            ->name('sales-comparison-export-pdf');
        Route::get('comparison-lookup/categories', [SalesReportController::class, 'getComparisonCategories'])
            ->middleware($perm(...ReportPermissions::comparisonLookupShowAny()))
            ->name('comparison-categories');
        Route::get('comparison-lookup/subcategories', [SalesReportController::class, 'getComparisonSubcategories'])
            ->middleware($perm(...ReportPermissions::comparisonLookupShowAny()))
            ->name('comparison-subcategories');
        Route::get('comparison-lookup/units', [SalesReportController::class, 'getComparisonUnits'])
            ->middleware($perm(...ReportPermissions::comparisonLookupShowAny()))
            ->name('comparison-units');
        Route::get('comparison-lookup/payment-methods', [SalesReportController::class, 'getComparisonPaymentMethods'])
            ->middleware($perm(...ReportPermissions::comparisonLookupShowAny()))
            ->name('comparison-payment-methods');

        Route::get('product-purchase-report', [SalesReportController::class, 'getproductPurchaseReport'])
            ->middleware($perm($productPurchase['show']))
            ->name('product-purchase-report');
        Route::get('product-purchase-export-excel', [SalesReportController::class, 'productPurchasesExportExcel'])
            ->middleware($perm($productPurchase['print']))
            ->name('product-purchase-export-excel');
        Route::get('product-purchase-export-pdf', [SalesReportController::class, 'productPurchasesExportPdf'])
            ->middleware($perm($productPurchase['print']))
            ->name('product-purchase-export-pdf');
        Route::get('comparison-lookup/purchase-subcategories', [SalesReportController::class, 'getPurchaseReportSubcategories'])
            ->middleware($perm($productPurchase['show']))
            ->name('purchase-report-subcategories');
        Route::get('comparison-lookup/purchase-units', [SalesReportController::class, 'getPurchaseReportUnits'])
            ->middleware($perm($productPurchase['show']))
            ->name('purchase-report-units');
        Route::get('comparison-lookup/purchase-payment-methods', [SalesReportController::class, 'getPurchaseReportPaymentMethods'])
            ->middleware($perm($productPurchase['show']))
            ->name('purchase-report-payment-methods');

        Route::get('purchase-payment-report', [SalesReportController::class, 'purchasePaymentReport'])
            ->middleware($perm($purchasePayment['show']))
            ->name('purchase-payment-report');
        Route::get('purchase-payment-export-excel', [SalesReportController::class, 'purchasePaymentExportExcel'])
            ->middleware($perm($purchasePayment['print']))
            ->name('purchase-payment-export-excel');
        Route::get('purchase-payment-export-pdf', [SalesReportController::class, 'purchasePaymentExportPdf'])
            ->middleware($perm($purchasePayment['print']))
            ->name('purchase-payment-export-pdf');

        Route::get('sell-payment-report', [SalesReportController::class, 'salesPaymentReport'])
            ->middleware($perm($sellPayment['show']))
            ->name('sell-payment-report');
        Route::get('sell-payment-export-excel', [SalesReportController::class, 'sellPaymentExportExcel'])
            ->middleware($perm($sellPayment['print']))
            ->name('sell-payment-export-excel');
        Route::get('sell-payment-export-pdf', [SalesReportController::class, 'sellPaymentExportPdf'])
            ->middleware($perm($sellPayment['print']))
            ->name('sell-payment-export-pdf');

        Route::get('product-inventory-report', [SalesReportController::class, 'productInventoryReport'])
            ->middleware($perm($productInventory['show']))
            ->name('product-inventory-report');
        Route::get('Profit-Loss', [SalesReportController::class, 'getProfitLoss'])
            ->middleware($perm($profitLoss['show']))
            ->name('Profit-Loss');
        Route::get('purchase-sell', [SalesReportController::class, 'getPurchaseSell'])
            ->middleware($perm($purchaseSell['show']))
            ->name('purchase-sell');
        Route::get('/reports/get-profit/{by?}', [SalesReportController::class, 'getProfit'])
            ->middleware($perm($profitLoss['show']));

        Route::get('branches', [SalesReportController::class, 'getBranches'])
            ->middleware($perm(...ReportPermissions::lookupShowAny()))
            ->name('branches');
        Route::get('getSuppliers', [SalesReportController::class, 'getSupplier'])
            ->middleware($perm(...ReportPermissions::purchaseLookupShowAny()))
            ->name('getSuppliers');
        Route::get('getCustomers', [SalesReportController::class, 'getCustomers'])
            ->middleware($perm(...ReportPermissions::salesLookupShowAny()))
            ->name('getCustomers');
        Route::get('products', [SalesReportController::class, 'getProducts'])
            ->middleware($perm(...ReportPermissions::lookupShowAny()))
            ->name('retrieveProducts');
        Route::get('devices', [SalesReportController::class, 'getDevices'])
            ->middleware($perm(...ReportPermissions::lookupShowAny()))
            ->name('devices');

        Route::get('/payment-reports', [SalesReportController::class, 'combinedPaymentReport'])
            ->middleware($perm(...ReportPermissions::menuShowAny()))
            ->name('payment-reports.combined');

        Route::get('product-inventory-summary', [SalesReportController::class, 'productInventorySummary'])
            ->middleware($perm($inventorySummary['show']))
            ->name('product-inventory-summary');
        Route::get('product-movement-report', [SalesReportController::class, 'productMovementReport'])
            ->middleware($perm($productInventory['show']))
            ->name('product-movement-report');
        Route::get('/inventory/record/{product_id}/{establishment_id}', [SalesReportController::class, 'productInventoryRecord'])
            ->middleware($perm(...ReportPermissions::inventoryLookupShowAny()))
            ->name('inventory.record');
        Route::get('Product-Stock-Report', [SalesReportController::class, 'productStockReport'])
            ->middleware($perm($productStock['show']))
            ->name('Product-Stock-Report');

        Route::get('Register-Report', [SalesReportController::class, 'getRegisterReport'])
            ->middleware($perm($register['show']))
            ->name('Register-Report');
        Route::get('Register-Report/{id}/print', [SalesReportController::class, 'showPrint'])
            ->middleware($perm($register['print']))
            ->name('register-report.print');
        Route::get('Register-Report/{id}', [SalesReportController::class, 'show'])
            ->middleware($perm($register['show']));
    });
});
