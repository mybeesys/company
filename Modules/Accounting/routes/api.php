<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\Api\AccountingController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'auth-central',
])->group(function () {

    Route::get('accounts', [AccountingController::class, 'accounts'])->name('accounts');
    Route::get('cost-centers', [AccountingController::class, 'costCenters'])->name('cost-centers');

});
