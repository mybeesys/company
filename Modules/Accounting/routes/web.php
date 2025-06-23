<?php

use App\Http\Middleware\LocalizationMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingController;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\AccountingReportsController;
use Modules\Accounting\Http\Controllers\AccountsRoutingController;
use Modules\Accounting\Http\Controllers\CostCenterConrollerController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\PaymentVouchersController;
use Modules\Accounting\Http\Controllers\PeriodicInventoryController;
use Modules\Accounting\Http\Controllers\ReceiptVouchersController;
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


    Route::middleware(['auth'])->group(function () {
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


        Route::get('accounts-routing', [AccountsRoutingController::class, 'index'])->name('accounts-routing');
        Route::post('accounts-routing-store', [AccountsRoutingController::class, 'store'])->name('accounts-routing-store');

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


        Route::get('payment-vouchers', [PaymentVouchersController::class, 'index'])->name('payment-vouchers');
        Route::post('payment-vouchers-store', [PaymentVouchersController::class, 'store'])->name('payment-vouchers-store');

        Route::get('receipt-vouchers', [ReceiptVouchersController::class, 'index'])->name('receipt-vouchers');
        Route::post('receipt-vouchers-store', [ReceiptVouchersController::class, 'store'])->name('receipt-vouchers-store');


        Route::get('income-statement', [AccountingReportsController::class, 'incomeStatement'])->name('income-statement');
        Route::get('trial-balance', [AccountingReportsController::class, 'trialBalance'])->name('trial-balance');
        Route::get('balance-sheet', [AccountingReportsController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('journal-report', [AccountingReportsController::class, 'JournalReport'])->name('journal-report');

        Route::get('cash-flow', [AccountingReportsController::class, 'cash_flow'])->name('cash-flow');
        Route::get('customers-suppliers-statement', [AccountingReportsController::class, 'customersSuppliersStatement'])->name('customers-suppliers-statement');

        Route::get('account-receivable-ageing-report', [AccountingReportsController::class, 'accountReceivableAgeingReport'])->name('account-receivable-ageing-report');
        Route::get('account-receivable-ageing-details', [AccountingReportsController::class, 'accountReceivableAgeingDetails'])->name('account-receivable-ageing-details');

        Route::get('account-payable-ageing-report', [AccountingReportsController::class, 'accountPayableAgeingReport'])->name('account-payable-ageing-report');
        Route::get('account-payable-ageing-details', [AccountingReportsController::class, 'accountPayableAgeingDetails'])->name('account-payable-ageing-details');


        // routes/web.php
        Route::prefix('inventory')->group(function () {
            Route::resource('periodic-inventory', PeriodicInventoryController::class)
                ->except(['edit', 'update', 'destroy'])
                ->names([
                    'index' => 'periodic-inventory.index',
                    'create' => 'periodic-inventory.create',
                    'store' => 'periodic-inventory.store',
                    'show' => 'periodic-inventory.show'
                ]);
        });
        //

    });
});
