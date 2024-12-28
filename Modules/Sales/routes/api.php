<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\SellApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    // 'api',
    // InitializeTenancyByDomain::class,
    // PreventAccessFromCentralDomains::class,
    // 'auth-central',
])->group(function () {
    Route::get('sales-invoices', [SellApiController::class, 'index'])->name('sales-invoices');
    Route::post('stor-sales-invoice', [SellApiController::class, 'store'])->name('stor-sales-invoice');

});