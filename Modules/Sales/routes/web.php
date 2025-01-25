<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJWT;
use Modules\ClientsAndSuppliers\Http\Controllers\ClientController;
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

    // Route::middleware(AuthenticateJWT::class)->group(function () {
    Route::middleware(['auth'])->group(function () {

        Route::get('invoices', [SellController::class, 'index'])->name('invoices');
        Route::get('create-invoice', [SellController::class, 'create'])->name('create-invoice');
        Route::get('convert-to-invoice', [SellController::class, 'create'])->name('convert-to-invoice');
        Route::post('store-invoice', [SellController::class, 'store'])->name('store-invoice');

        Route::get('sell-return', [SellReturnController::class, 'index'])->name('sell-return');
        Route::get('create-sell-return/{id}', [SellReturnController::class, 'create'])->name('create-sell-return');
        Route::post('store-sell-return', [SellReturnController::class, 'store'])->name('store-sell-return');

        Route::get('quotations', [QuotationController::class, 'index'])->name('quotations');
        Route::get('create-quotation', [QuotationController::class, 'create'])->name('create-quotation');
        Route::post('store-quotation', [QuotationController::class, 'store'])->name('store-quotation');

        Route::get('receipts', [ReceiptsController::class, 'index'])->name('receipts');
        Route::get('create-receipts', [ReceiptsController::class, 'create'])->name('create-receipts');
        Route::post('store-receipts', [ReceiptsController::class, 'store'])->name('store-receipts');

        Route::get('get-transactions/{clientId}', [ReceiptsController::class, 'getTransactions'])->name('get-transactions');
    });
});