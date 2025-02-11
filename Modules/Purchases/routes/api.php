<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchases\Http\Controllers\PurchasesReturnApiController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;



Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'auth-central',
])->group(function () {

    Route::post('stor-purchases-return', [PurchasesReturnApiController::class, 'store'])->name('stor-purchases-return');

});