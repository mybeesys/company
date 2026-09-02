<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchases\Http\Controllers\PurchasesController;
use Modules\Purchases\Http\Controllers\PurchasesOrderController;
use Modules\Purchases\Http\Controllers\PurchasesReturnController;
use Modules\Purchases\Http\Controllers\SuppliersReceiptsController;
use Modules\Purchases\Support\PurchasesPermissions;
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

        Route::get('purchase-dashbord', [PurchasesController::class, 'purchaseDashbord'])
            ->middleware($perm(PurchasesPermissions::DASHBOARD_SHOW))
            ->name('purchase-dashbord');
        Route::get('purchase-dashbord/export-csv', [PurchasesController::class, 'purchaseDashboardExportCsv'])
            ->middleware($perm(PurchasesPermissions::DASHBOARD_SHOW))
            ->name('purchase-dashbord-export-csv');
        Route::get('purchase-dashbord/export-pdf', [PurchasesController::class, 'purchaseDashboardExportPdf'])
            ->middleware($perm(PurchasesPermissions::DASHBOARD_SHOW))
            ->name('purchase-dashbord-export-pdf');

        Route::get('purchase-invoices', [PurchasesController::class, 'index'])
            ->middleware($perm(PurchasesPermissions::INVOICES_SHOW))
            ->name('purchase-invoices');
        Route::get('purchases-favorites', [PurchasesController::class, 'favorites'])
            ->middleware($perm(...PurchasesPermissions::documentShowAny()))
            ->name('purchases-favorites');
        Route::get('create-purchases-invoice', [PurchasesController::class, 'create'])
            ->middleware($perm(PurchasesPermissions::INVOICES_CREATE))
            ->name('create-purchases-invoice');
        Route::get('convert-po-to-invoice', [PurchasesController::class, 'create'])
            ->middleware([
                $perm(PurchasesPermissions::CONVERT_PO),
                $perm(PurchasesPermissions::INVOICES_CREATE),
            ])
            ->name('convert-po-to-invoice');
        Route::post('store-purchases-invoice', [PurchasesController::class, 'store'])
            ->middleware($perm(PurchasesPermissions::INVOICES_CREATE))
            ->name('store-purchases-invoice');
        Route::get('edit-purchases-invoice/{transaction}', [PurchasesController::class, 'edit'])
            ->middleware($perm(PurchasesPermissions::INVOICES_CREATE))
            ->name('edit-purchases-invoice');
        Route::put('update-purchases-invoice/{transaction}', [PurchasesController::class, 'update'])
            ->middleware($perm(PurchasesPermissions::INVOICES_CREATE))
            ->name('update-purchases-invoice');
        Route::delete('destroy-purchases-invoice/{transaction}', [PurchasesController::class, 'destroy'])
            ->middleware($perm(PurchasesPermissions::INVOICES_CREATE))
            ->name('destroy-purchases-invoice');

        Route::get('purchases-return', [PurchasesReturnController::class, 'index'])
            ->middleware($perm(PurchasesPermissions::RETURNS_SHOW))
            ->name('purchases-return');
        Route::get('create-purchases-return/{id}', [PurchasesReturnController::class, 'create'])
            ->middleware($perm(PurchasesPermissions::CREATE_INVOICE_RETURN, PurchasesPermissions::RETURNS_CREATE))
            ->name('create-purchases-return');
        Route::post('store-purchases-return', [PurchasesReturnController::class, 'store'])
            ->middleware($perm(PurchasesPermissions::CREATE_INVOICE_RETURN, PurchasesPermissions::RETURNS_CREATE))
            ->name('store-purchases-return');
        Route::get('create-purchases-return-invoice', [PurchasesReturnController::class, 'createReturnInvoice'])
            ->middleware($perm(PurchasesPermissions::RETURNS_CREATE))
            ->name('create-purchases-return-invoice');
        Route::post('store-purchases-return-invoice', [PurchasesReturnController::class, 'storeReturnInvoice'])
            ->middleware($perm(PurchasesPermissions::RETURNS_CREATE))
            ->name('store-purchases-return-invoice');

        Route::get('purchases-order', [PurchasesOrderController::class, 'index'])
            ->middleware($perm(PurchasesPermissions::ORDERS_SHOW))
            ->name('purchases-order');
        Route::get('create-purchase-order', [PurchasesOrderController::class, 'create'])
            ->middleware($perm(PurchasesPermissions::ORDERS_CREATE))
            ->name('create-purchase-order');
        Route::post('store-purchase-order', [PurchasesOrderController::class, 'store'])
            ->middleware($perm(PurchasesPermissions::ORDERS_CREATE))
            ->name('store-purchase-order');

        Route::get('suppliers-receipts', [SuppliersReceiptsController::class, 'index'])
            ->middleware($perm(PurchasesPermissions::VOUCHERS_SHOW))
            ->name('suppliers-receipts');
        Route::get('create-suppliers-receipts', [SuppliersReceiptsController::class, 'create'])
            ->middleware($perm(PurchasesPermissions::VOUCHERS_CREATE))
            ->name('create-suppliers-receipts');
    });
});
