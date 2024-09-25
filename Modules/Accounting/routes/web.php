<?php

use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\TreeAccountsController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Accounting Dashbord
    Route::get('accounting-dashboard', [AccountingDashboardController::class, 'index'])->name('accounting-dashboard');



    // Tree of accounts
    Route::get('tree-of-accounts', [TreeAccountsController::class, 'index'])->name('tree-of-accounts');
    Route::get('create-account', [TreeAccountsController::class, 'create'])->name('create-account');
    Route::get('create-default-accounts', [TreeAccountsController::class, 'createDefaultAccounts'])->name('create-default-accounts');
    Route::post('store-account', [TreeAccountsController::class, 'store'])->name('store-account');
    Route::post('update-account', [TreeAccountsController::class, 'update'])->name('update-account');
    Route::get('ledger', [TreeAccountsController::class, 'ledger'])->name('ledger');
    Route::post('change-status-account', [TreeAccountsController::class, 'activateDeactivate'])->name('change-status-account');
});