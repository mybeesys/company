<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\PaymentMethodsApiController;
use Modules\General\Http\Controllers\SellApiController;
use Modules\General\Http\Controllers\TaxApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'auth-central',
])->group(function () {
    Route::get('taxes', [TaxApiController::class, 'taxes'])->name('taxes');
    Route::get('payment-methods', [PaymentMethodsApiController::class, 'index'])->name('payment-methods');

});