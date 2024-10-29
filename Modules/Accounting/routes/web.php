<?php

use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\CostCenterConrollerController;
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
        Route::get('print-ledger/{id}', [TreeAccountsController::class, 'ledgerPrint'])->name('print-ledger');
        Route::get('ledger-export-pdf/{id}', [TreeAccountsController::class, 'ledgerExportPdf'])->name('ledger-export-pdf');
        Route::get('ledger-export-excel/{id}', [TreeAccountsController::class, 'ledgerExportExcel'])->name('ledger-export-excel');
        Route::post('change-status-account', [TreeAccountsController::class, 'activateDeactivate'])->name('change-status-account');
        Route::get('accounts-dropdown', [TreeAccountsController::class, 'accountsDropdown'])->name('accounts-dropdown');


        // Journal Enter
        Route::get('journal-entry-index', [JournalEntryController::class, 'index'])->name('journal-entry-index');
        Route::get('journal-entry-create', [JournalEntryController::class, 'create'])->name('journal-entry-create');
        Route::post('journal-entry-store', [JournalEntryController::class, 'store'])->name('journal-entry-store');
        Route::get('/journal-entry-edit/{id}', [JournalEntryController::class, 'edit'])->name('journal-entry-edit');
        Route::get('/journal-entry-duplication/{id}', [JournalEntryController::class, 'duplication'])->name('journal-entry-duplication');
        Route::post('journal-entry-update/{id}', [JournalEntryController::class, 'update'])->name('journal-entry-update');
        Route::get('journal-entry-destroy/{id}', [JournalEntryController::class, 'destroy'])->name('journal-entry-destroy');
        Route::get('journal-entry-print/{id}', [JournalEntryController::class, 'print'])->name('journal-entry-print');
        Route::get('journal-entry-export-pdf/{id}', [JournalEntryController::class, 'exportPDF'])->name('journal-entry-export-pdf');
        Route::get('journal-entry-export-excel/{id}', [JournalEntryController::class, 'exportExcel'])->name('journal-entry-export-excel');

        // Cost Center
        Route::get('cost-center-index', [CostCenterConrollerController::class, 'index'])->name('cost-center-index');
        Route::post('cost-center-store', [CostCenterConrollerController::class, 'store'])->name('cost-center-store');
        Route::post('cost-center-update', [CostCenterConrollerController::class, 'update'])->name('cost-center-update');
        Route::get('cost-center-print', [CostCenterConrollerController::class, 'print'])->name('cost-center-print');
        Route::get('cost-center-transactions/{id}', [CostCenterConrollerController::class, 'transactions'])->name('cost-center-transactions');
        Route::get('cost-center-transactions-print/{id}', [CostCenterConrollerController::class, 'transactionsPrint'])->name('cost-center-transactions-print');
        Route::get('cost-center-transactions-export-pdf/{id}', [CostCenterConrollerController::class, 'exportTransactionsPDF'])->name('cost-center-transactions-export-pdf');
        Route::get('cost-center-transactions-export-excel/{id}', [CostCenterConrollerController::class, 'exportTransactionsExcel'])->name('cost-center-transactions-export-excel');
        Route::post('change-status-cost-center', [CostCenterConrollerController::class, 'changeStatus'])->name('change-status-cost-center');
        Route::get('cost-center-export-pdf', [CostCenterConrollerController::class, 'exportPDF'])->name('cost-center-export-pdf');
        Route::get('cost-center-export-excel', [CostCenterConrollerController::class, 'exportExcel'])->name('cost-center-export-excel');


    });
});