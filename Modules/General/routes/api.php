<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\Api\GeneralController;
use Modules\General\Http\Controllers\CashRegisterApiController;
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

    Route::get('/company-details', [GeneralController::class, 'companyDetails'])->middleware(['auth-central']);


    Route::post('/shift-open', [CashRegisterApiController::class, 'store'])->name('shift-open');
    Route::post('/shift-close', [CashRegisterApiController::class, 'postCloseRegister'])->name('shift-close');





});