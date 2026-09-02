<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Purchases\Support\PurchasesPermissions;
use Modules\Sales\Http\Controllers\CouponController;
use Modules\Sales\Http\Controllers\QuotationController;
use Modules\Sales\Http\Controllers\ReceiptsController;
use Modules\Sales\Http\Controllers\SellController;
use Modules\Sales\Http\Controllers\SellReturnController;
use Modules\Sales\Support\SalesPermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::middleware(['auth'])->group(function () {
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::get('sales-dashbord', [SellController::class, 'salesDashbord'])
            ->middleware($perm(SalesPermissions::DASHBOARD_SHOW))
            ->name('sales-dashbord');
        Route::get('sales-dashbord/export-csv', [SellController::class, 'salesDashboardExportCsv'])
            ->middleware($perm(SalesPermissions::DASHBOARD_SHOW))
            ->name('sales-dashbord-export-csv');
        Route::get('sales-dashbord/export-pdf', [SellController::class, 'salesDashboardExportPdf'])
            ->middleware($perm(SalesPermissions::DASHBOARD_SHOW))
            ->name('sales-dashbord-export-pdf');

        Route::get('invoices', [SellController::class, 'index'])
            ->middleware($perm(SalesPermissions::INVOICES_SHOW))
            ->name('invoices');
        Route::get('sales-favorites', [SellController::class, 'favorites'])
            ->middleware($perm(...SalesPermissions::documentShowAny()))
            ->name('sales-favorites');
        Route::get('create-invoice', [SellController::class, 'create'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('create-invoice');
        Route::get('convert-to-invoice', [SellController::class, 'create'])
            ->middleware([
                $perm(SalesPermissions::CONVERT_QUOTATION),
                $perm(SalesPermissions::INVOICES_CREATE),
            ])
            ->name('convert-to-invoice');
        Route::post('store-invoice', [SellController::class, 'store'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('store-invoice');
        Route::get('edit-invoice/{transaction}', [SellController::class, 'edit'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('edit-invoice');
        Route::put('update-invoice/{transaction}', [SellController::class, 'update'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('update-invoice');
        Route::delete('destroy-invoice/{transaction}', [SellController::class, 'destroy'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('destroy-invoice');

        Route::get('products-for-sale', [ProductController::class, 'productsForSale'])
            ->middleware($perm(...SalesPermissions::sellDocumentCreateAny(), ...PurchasesPermissions::documentCreateAny()))
            ->name('products-for-sale');
        Route::post('invoice-inventory-costs', [SellController::class, 'invoiceInventoryCosts'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE, SalesPermissions::RETURNS_CREATE))
            ->name('web.invoice-inventory-costs');
        Route::get('internal-consumption-types', [\Modules\General\Http\Controllers\InternalConsumptionTypesApiController::class, 'index'])
            ->middleware($perm(SalesPermissions::INVOICES_CREATE))
            ->name('web.internal-consumption-types');
        Route::get('products-for-quotation', [ProductController::class, 'productsForQuotation'])
            ->middleware($perm(SalesPermissions::QUOTATIONS_CREATE))
            ->name('products-for-quotation');
        Route::get('products-for-client', [ProductController::class, 'productsForClient'])
            ->middleware($perm(SalesPermissions::CUSTOMERS_SHOW, SalesPermissions::INVOICES_CREATE, SalesPermissions::QUOTATIONS_CREATE))
            ->name('products-for-client');
        Route::get('product-sell-extras/{id}', [SellController::class, 'productSellExtras'])
            ->middleware($perm(...SalesPermissions::sellDocumentCreateAny()))
            ->name('product-sell-extras');

        Route::get('sell-return', [SellReturnController::class, 'index'])
            ->middleware($perm(SalesPermissions::RETURNS_SHOW))
            ->name('sell-return');
        Route::get('create-sell-return/{id}', [SellReturnController::class, 'create'])
            ->middleware($perm(SalesPermissions::CREATE_INVOICE_RETURN, SalesPermissions::RETURNS_CREATE))
            ->name('create-sell-return');
        Route::get('create-sell-return-invoice', [SellReturnController::class, 'createSellReturn'])
            ->middleware($perm(SalesPermissions::RETURNS_CREATE))
            ->name('create-sell-return-invoice');
        Route::post('store-sell-return', [SellReturnController::class, 'store'])
            ->middleware($perm(SalesPermissions::CREATE_INVOICE_RETURN, SalesPermissions::RETURNS_CREATE))
            ->name('store-sell-return');
        Route::post('store-sell-return-invoice', [SellReturnController::class, 'storeSellReturn'])
            ->middleware($perm(SalesPermissions::RETURNS_CREATE))
            ->name('store-sell-return-invoice');

        Route::get('quotations', [QuotationController::class, 'index'])
            ->middleware($perm(SalesPermissions::QUOTATIONS_SHOW))
            ->name('quotations');
        Route::get('create-quotation', [QuotationController::class, 'create'])
            ->middleware($perm(SalesPermissions::QUOTATIONS_CREATE))
            ->name('create-quotation');
        Route::post('store-quotation', [QuotationController::class, 'store'])
            ->middleware($perm(SalesPermissions::QUOTATIONS_CREATE))
            ->name('store-quotation');

        Route::get('receipts', [ReceiptsController::class, 'index'])
            ->middleware($perm(SalesPermissions::RECEIPTS_SHOW))
            ->name('receipts');
        Route::get('create-receipts', [ReceiptsController::class, 'create'])
            ->middleware($perm(SalesPermissions::RECEIPTS_CREATE))
            ->name('create-receipts');
        Route::post('store-receipts', [ReceiptsController::class, 'store'])
            ->name('store-receipts');

        Route::get('receipts-payments/{payment}/edit', [ReceiptsController::class, 'editPayment'])
            ->name('receipts-payments.edit');
        Route::put('receipts-payments/{payment}', [ReceiptsController::class, 'updatePayment'])
            ->name('receipts-payments.update');
        Route::delete('receipts-payments/{payment}', [ReceiptsController::class, 'destroyPayment'])
            ->name('receipts-payments.destroy');
        Route::get('receipts-payments/{payment}/duplicate', [ReceiptsController::class, 'duplicatePayment'])
            ->name('receipts-payments.duplicate');

        Route::get('get-transactions/{clientId}', [ReceiptsController::class, 'getTransactions'])
            ->name('get-transactions');

        Route::controller(CouponController::class)->prefix('coupon')->name('coupons.')->group(function () use ($perm) {
            Route::get('', 'index')->middleware($perm(SalesPermissions::COUPONS_SHOW, SalesPermissions::COUPON_SHOW))->name('index');
            Route::post('/store', 'store')->name('store');
            Route::delete('/{coupon}', 'destroy')->middleware($perm(SalesPermissions::COUPON_DELETE))->name('delete');
            Route::delete('/force-delete/{coupon}', 'forceDelete')->middleware($perm(SalesPermissions::COUPON_DELETE))->name('force-delete');
            Route::post('/restore/{id}', 'restore')->middleware($perm(SalesPermissions::COUPON_UPDATE))->name('restore');

            Route::get('get-details/{id}', 'getCouponDetails')
                ->middleware($perm(SalesPermissions::COUPON_SHOW, SalesPermissions::COUPON_UPDATE))
                ->name('get-details');

            Route::get('/generate-code', 'generateCode')
                ->middleware($perm(SalesPermissions::COUPON_CREATE, SalesPermissions::COUPON_UPDATE))
                ->name('generate-code');
        });
    });
});
