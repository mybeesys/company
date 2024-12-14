<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchases\Http\Controllers\PurchasesController;
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
    });
});