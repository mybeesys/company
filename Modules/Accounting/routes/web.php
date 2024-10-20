<?php

use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\TreeAccountsController;
use Modules\Employee\Models\Role;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Accounting Dashbord
    Route::get('accounting-dashboard', [AccountingDashboardController::class, 'index'])->name('accounting-dashboard');


    Route::middleware(AuthenticateJWT::class)->group(function () {
        // Tree of accounts
        Route::get('tree-of-accounts', [TreeAccountsController::class, 'index'])->name('tree-of-accounts');
        Route::get('create-account', [TreeAccountsController::class, 'create'])->name('create-account');
        Route::get('create-default-accounts', [TreeAccountsController::class, 'createDefaultAccounts'])->name('create-default-accounts');
        Route::post('store-account', [TreeAccountsController::class, 'store'])->name('store-account');
        Route::post('update-account', [TreeAccountsController::class, 'update'])->name('update-account');
        Route::get('ledger', [TreeAccountsController::class, 'ledger'])->name('ledger');
        Route::post('change-status-account', [TreeAccountsController::class, 'activateDeactivate'])->name('change-status-account');


        //
        Route::get('journal-entry-index', [JournalEntryController::class, 'index'])->name('journal-entry-index');
        Route::get('journal-entry-create', [JournalEntryController::class, 'create'])->name('journal-entry-create');
        Route::post('journal-entry-store', [JournalEntryController::class, 'store'])->name('journal-entry-store');
        Route::get('/journal-entry-edit/{id}', [JournalEntryController::class, 'edit'])->name('journal-entry-edit');
        Route::get('/journal-entry-duplication/{id}', [JournalEntryController::class, 'duplication'])->name('journal-entry-duplication');
        Route::post('journal-entry-update/{id}', [JournalEntryController::class, 'update'])->name('journal-entry-update');
        Route::get('journal-entry-destroy/{id}', [JournalEntryController::class, 'destroy'])->name('journal-entry-destroy');
        Route::get('journal-entry-print/{id}', [JournalEntryController::class, 'print'])->name('journal-entry-print');
        Route::get('journal-entry-export-pdf/{id}', [JournalEntryController::class, 'exportPDF'])->name('journal-entry-export-pdf');
    });
});