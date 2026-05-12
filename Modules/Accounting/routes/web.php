<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\AccountingStagingResetController;
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
        /**
         * Staging / demo: full wipe of tenant accounting module + default chart & routings.
         * POST body or JSON: confirm=RESET_ACCOUNTING_FULL
         * Enable: ACCOUNTING_ALLOW_FULL_RESET=true in .env, or APP_ENV=local|staging.
         */
        Route::post('accounting/staging-full-reset', AccountingStagingResetController::class)
            ->middleware('throttle:6,1')
            ->name('accounting.staging-full-reset');

        // Tree of accounts
        Route::get('tree-of-accounts', [TreeAccountsController::class, 'index'])->name('tree-of-accounts');
        Route::get('tree-of-accounts/import', [TreeAccountsController::class, 'importPage'])->name('tree-of-accounts-import');
        Route::get('tree-of-accounts/import/template', [TreeAccountsController::class, 'downloadImportTemplate'])->name('tree-of-accounts-import-template');
        Route::post('tree-of-accounts/import', [TreeAccountsController::class, 'importFromExcel'])->name('tree-of-accounts-import-store');
        Route::get('create-account', [TreeAccountsController::class, 'create'])->name('create-account');
        Route::post('store-sub-account', [TreeAccountsController::class, 'storeSubAccount'])->name('store-sub-account');

        Route::get('create-default-accounts', [TreeAccountsController::class, 'createDefaultAccounts'])->name('create-default-accounts');
        Route::post('store-account', [TreeAccountsController::class, 'store'])->name('store-account');
        Route::post('update-account', [TreeAccountsController::class, 'update'])->name('update-account');
        Route::get('ledger', [TreeAccountsController::class, 'ledger'])->name('ledger');
        Route::get('print-ledger/{id}', [TreeAccountsController::class, 'ledgerPrint'])->name('print-ledger');
        Route::get('ledger-export-pdf/{id}', [TreeAccountsController::class, 'ledgerExportPdf'])->name('ledger-export-pdf');
        Route::get('ledger-export-excel/{id}', [TreeAccountsController::class, 'ledgerExportExcel'])->name('ledger-export-excel');
        Route::post('change-status-account', [TreeAccountsController::class, 'activateDeactivate'])->name('change-status-account');
        Route::get('next-gl-code', [TreeAccountsController::class, 'nextGlCode'])->name('next-gl-code');
        Route::post('delete-account', [TreeAccountsController::class, 'deleteAccount'])->name('delete-account');
        Route::get('accounts-dropdown', [TreeAccountsController::class, 'accountsDropdown'])->name('accounts-dropdown');

        Route::get('accounts-routing', [AccountsRoutingController::class, 'index'])->name('accounts-routing');
        Route::post('accounts-routing-store', [AccountsRoutingController::class, 'store'])->name('accounts-routing-store');

        // Journal Enter
        Route::get('journal-entry-index', [JournalEntryController::class, 'index'])->name('journal-entry-index');
        Route::get('journal-entry-create', [JournalEntryController::class, 'create'])->name('journal-entry-create');
        Route::post('journal-entry-store', [JournalEntryController::class, 'store'])->name('journal-entry-store');
        Route::get('/journal-entry-edit/{id}', [JournalEntryController::class, 'edit'])->name('journal-entry-edit');
        Route::get('/journal-entry-show/{id}', [JournalEntryController::class, 'show'])->name('journal-entry-show');
        Route::get('/journal-entry-duplication/{id}', [JournalEntryController::class, 'duplication'])->name('journal-entry-duplication');
        Route::post('journal-entry-update/{id}', [JournalEntryController::class, 'update'])->name('journal-entry-update');
        Route::get('journal-entry-destroy/{id}', [JournalEntryController::class, 'destroy'])->name('journal-entry-destroy');
        Route::get('journal-entry-print/{id}', [JournalEntryController::class, 'print'])->name('journal-entry-print');
        Route::get('journal-entry-export-pdf/{id}', [JournalEntryController::class, 'exportPDF'])->name('journal-entry-export-pdf');
        Route::get('journal-entry-export-excel/{id}', [JournalEntryController::class, 'exportExcel'])->name('journal-entry-export-excel');
        Route::get('journal-entry-attachment/{id}', [JournalEntryController::class, 'downloadAttachment'])->name('journal-entry-attachment');

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
        Route::get('payment-vouchers/form-data', [PaymentVouchersController::class, 'formData'])->name('payment-vouchers-form-data');
        Route::get('payment-vouchers/{id}', [PaymentVouchersController::class, 'show'])->whereNumber('id')->name('payment-vouchers-show');
        Route::get('payment-vouchers/{id}/modal', [PaymentVouchersController::class, 'modal'])->whereNumber('id')->name('payment-vouchers-modal');
        Route::get('payment-vouchers-export-pdf/{id}', [PaymentVouchersController::class, 'exportPDF'])->whereNumber('id')->name('payment-vouchers-export-pdf');
        Route::put('payment-vouchers/{id}', [PaymentVouchersController::class, 'update'])->name('payment-vouchers-update');
        Route::delete('payment-vouchers/{id}', [PaymentVouchersController::class, 'destroy'])->whereNumber('id')->name('payment-vouchers-destroy');
        Route::post('payment-vouchers-store', [PaymentVouchersController::class, 'store'])->name('payment-vouchers-store');

        Route::get('receipt-vouchers', [ReceiptVouchersController::class, 'index'])->name('receipt-vouchers');
        Route::get('receipt-vouchers/form-data', [ReceiptVouchersController::class, 'formData'])->name('receipt-vouchers-form-data');
        Route::get('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'show'])->whereNumber('id')->name('receipt-vouchers-show');
        Route::get('receipt-vouchers/{id}/modal', [ReceiptVouchersController::class, 'modal'])->whereNumber('id')->name('receipt-vouchers-modal');
        Route::get('receipt-vouchers-export-pdf/{id}', [ReceiptVouchersController::class, 'exportPDF'])->whereNumber('id')->name('receipt-vouchers-export-pdf');
        Route::put('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'update'])->name('receipt-vouchers-update');
        Route::delete('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'destroy'])->whereNumber('id')->name('receipt-vouchers-destroy');
        Route::post('receipt-vouchers-store', [ReceiptVouchersController::class, 'store'])->name('receipt-vouchers-store');

        Route::get('accounting-reports', [AccountingReportsController::class, 'index'])->name('accounting-reports');
        Route::get('income-statement', [AccountingReportsController::class, 'incomeStatement'])->name('income-statement');
        Route::get('income-statement-export-pdf', [AccountingReportsController::class, 'incomeStatementExportPdf'])->name('income-statement-export-pdf');
        Route::get('income-statement-export-excel', [AccountingReportsController::class, 'incomeStatementExportExcel'])->name('income-statement-export-excel');
        Route::get('trial-balance', [AccountingReportsController::class, 'trialBalance'])->name('trial-balance');
        Route::get('trial-balance-export-pdf', [AccountingReportsController::class, 'trialBalanceExportPdf'])->name('trial-balance-export-pdf');
        Route::get('trial-balance-export-excel', [AccountingReportsController::class, 'trialBalanceExportExcel'])->name('trial-balance-export-excel');
        Route::get('balance-sheet', [AccountingReportsController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('balance-sheet-export-pdf', [AccountingReportsController::class, 'balanceSheetExportPdf'])->name('balance-sheet-export-pdf');
        Route::get('balance-sheet-export-excel', [AccountingReportsController::class, 'balanceSheetExportExcel'])->name('balance-sheet-export-excel');
        Route::get('journal-report', [AccountingReportsController::class, 'JournalReport'])->name('journal-report');
        Route::get('journal-report-export-pdf', [AccountingReportsController::class, 'journalReportExportPdf'])->name('journal-report-export-pdf');
        Route::get('journal-report-export-excel', [AccountingReportsController::class, 'journalReportExportExcel'])->name('journal-report-export-excel');

        Route::get('cash-flow', [AccountingReportsController::class, 'cash_flow'])->name('cash-flow');
        Route::get('cash-flow-export-pdf', [AccountingReportsController::class, 'cashFlowExportPdf'])->name('cash-flow-export-pdf');
        Route::get('cash-flow-export-excel', [AccountingReportsController::class, 'cashFlowExportExcel'])->name('cash-flow-export-excel');
        Route::get('customers-suppliers-statement', [AccountingReportsController::class, 'customersSuppliersStatement'])->name('customers-suppliers-statement');
        Route::get('customers-suppliers-statement-export-pdf', [AccountingReportsController::class, 'customersSuppliersStatementExportPdf'])->name('customers-suppliers-statement-export-pdf');
        Route::get('customers-suppliers-statement-export-excel', [AccountingReportsController::class, 'customersSuppliersStatementExportExcel'])->name('customers-suppliers-statement-export-excel');

        Route::get('account-receivable-ageing-report', [AccountingReportsController::class, 'accountReceivableAgeingReport'])->name('account-receivable-ageing-report');
        Route::get('account-receivable-ageing-details', [AccountingReportsController::class, 'accountReceivableAgeingDetails'])->name('account-receivable-ageing-details');
        Route::get('account-receivable-ageing-report-export-pdf', [AccountingReportsController::class, 'accountReceivableAgeingReportExportPdf'])->name('account-receivable-ageing-report-export-pdf');
        Route::get('account-receivable-ageing-report-export-excel', [AccountingReportsController::class, 'accountReceivableAgeingReportExportExcel'])->name('account-receivable-ageing-report-export-excel');
        Route::get('account-receivable-ageing-details-export-pdf', [AccountingReportsController::class, 'accountReceivableAgeingDetailsExportPdf'])->name('account-receivable-ageing-details-export-pdf');
        Route::get('account-receivable-ageing-details-export-excel', [AccountingReportsController::class, 'accountReceivableAgeingDetailsExportExcel'])->name('account-receivable-ageing-details-export-excel');

        Route::get('account-payable-ageing-report', [AccountingReportsController::class, 'accountPayableAgeingReport'])->name('account-payable-ageing-report');
        Route::get('account-payable-ageing-details', [AccountingReportsController::class, 'accountPayableAgeingDetails'])->name('account-payable-ageing-details');
        Route::get('account-payable-ageing-report-export-pdf', [AccountingReportsController::class, 'accountPayableAgeingReportExportPdf'])->name('account-payable-ageing-report-export-pdf');
        Route::get('account-payable-ageing-report-export-excel', [AccountingReportsController::class, 'accountPayableAgeingReportExportExcel'])->name('account-payable-ageing-report-export-excel');
        Route::get('account-payable-ageing-details-export-pdf', [AccountingReportsController::class, 'accountPayableAgeingDetailsExportPdf'])->name('account-payable-ageing-details-export-pdf');
        Route::get('account-payable-ageing-details-export-excel', [AccountingReportsController::class, 'accountPayableAgeingDetailsExportExcel'])->name('account-payable-ageing-details-export-excel');

        Route::post('/track-action', [AccountingReportsController::class, 'track'])->name('track.action');

        // routes/web.php
        Route::get('/get-products-by-establishment/{establishmentId}', [PeriodicInventoryController::class, 'getProductsByEstablishment']);
        Route::prefix('inventory')->group(function () {
            Route::get('periodic-inventory/export/list-excel', [PeriodicInventoryController::class, 'exportListExcel'])
                ->name('periodic-inventory-list-export-excel');
            Route::get('periodic-inventory/{id}/export-excel', [PeriodicInventoryController::class, 'exportDetailExcel'])
                ->whereNumber('id')
                ->name('periodic-inventory-detail-export-excel');
            Route::get('periodic-inventory-export-pdf/{id}', [PeriodicInventoryController::class, 'exportPdf'])
                ->name('periodic-inventory-export-pdf');
            Route::post('periodic-inventory/{id}/approve', [PeriodicInventoryController::class, 'approve'])
                ->whereNumber('id')
                ->name('periodic-inventory-approve');
            Route::resource('periodic-inventory', PeriodicInventoryController::class)
                ->except(['destroy'])
                ->names([
                    'index' => 'periodic-inventory.index',
                    'create' => 'periodic-inventory.create',
                    'store' => 'periodic-inventory.store',
                    'edit' => 'periodic-inventory.edit',
                    'update' => 'periodic-inventory.update',
                    'show' => 'periodic-inventory.show',
                ]);
        });
        //

    });
});
