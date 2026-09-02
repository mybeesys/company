<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\Http\Controllers\AccountingDashboardController;
use Modules\Accounting\Http\Controllers\AccountingStagingResetController;
use Modules\Accounting\Http\Controllers\AccountingReportsController;
use Modules\Accounting\Http\Controllers\AccountingSettingsController;
use Modules\Accounting\Http\Controllers\FinancialYearPagesController;
use Modules\Accounting\Http\Controllers\FinancialYearSettingsController;
use Modules\Accounting\Http\Controllers\AccountsRoutingController;
use Modules\Accounting\Http\Controllers\CostCenterConrollerController;
use Modules\Accounting\Http\Controllers\JournalEntryController;
use Modules\Accounting\Http\Controllers\JournalEntryImportController;
use Modules\Accounting\Http\Controllers\OpeningBalanceImportController;
use Modules\Accounting\Http\Controllers\PaymentVouchersController;
use Modules\Accounting\Http\Controllers\PeriodicInventoryController;
use Modules\Accounting\Http\Controllers\ReceiptVouchersController;
use Modules\Accounting\Http\Controllers\TreeAccountsController;
use Modules\Accounting\Support\AccountingPermissions;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::middleware(['auth'])->group(function () {
        $perm = fn (string ...$names) => 'dashboard.perm:'.implode(',', $names);

        Route::get('accounting-dashboard', [AccountingDashboardController::class, 'index'])
            ->middleware($perm(AccountingPermissions::DASHBOARD_SHOW))
            ->name('accounting-dashboard');

        /**
         * Staging / demo: full wipe of tenant accounting module + default chart & routings.
         * POST body or JSON: confirm=RESET_ACCOUNTING_FULL
         * Enable: ACCOUNTING_ALLOW_FULL_RESET=true in .env, or APP_ENV=local|staging.
         */
        Route::post('accounting/staging-full-reset', AccountingStagingResetController::class)
            ->middleware([$perm(AccountingPermissions::SETTINGS_UPDATE), 'throttle:6,1'])
            ->name('accounting.staging-full-reset');

        Route::get('tree-of-accounts', [TreeAccountsController::class, 'index'])
            ->middleware($perm(AccountingPermissions::TREE_SHOW))
            ->name('tree-of-accounts');
        Route::get('tree-of-accounts/import', [TreeAccountsController::class, 'importPage'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('tree-of-accounts-import');
        Route::get('tree-of-accounts/import/template', [TreeAccountsController::class, 'downloadImportTemplate'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('tree-of-accounts-import-template');
        Route::post('tree-of-accounts/import', [TreeAccountsController::class, 'importFromExcel'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('tree-of-accounts-import-store');
        Route::post('tree-of-accounts/repair-gl-codes', [TreeAccountsController::class, 'repairGlCodes'])
            ->middleware([$perm(AccountingPermissions::TREE_UPDATE), 'throttle:6,1'])
            ->name('tree-of-accounts-repair-gl-codes');
        Route::get('create-account', [TreeAccountsController::class, 'create'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('create-account');
        Route::post('store-sub-account', [TreeAccountsController::class, 'storeSubAccount'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('store-sub-account');

        Route::get('create-default-accounts', [TreeAccountsController::class, 'createDefaultAccounts'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('create-default-accounts');
        Route::post('store-account', [TreeAccountsController::class, 'store'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('store-account');
        Route::post('update-account', [TreeAccountsController::class, 'update'])
            ->middleware($perm(AccountingPermissions::TREE_UPDATE))
            ->name('update-account');
        Route::get('ledger', [TreeAccountsController::class, 'ledger'])
            ->middleware($perm(AccountingPermissions::LEDGER_SHOW, AccountingPermissions::ACCOUNT_STATEMENT_SHOW))
            ->name('ledger');
        Route::get('print-ledger/{id}', [TreeAccountsController::class, 'ledgerPrint'])
            ->middleware($perm(AccountingPermissions::ACCOUNT_STATEMENT_PRINT))
            ->name('print-ledger');
        Route::get('ledger-export-pdf/{id}', [TreeAccountsController::class, 'ledgerExportPdf'])
            ->middleware($perm(AccountingPermissions::ACCOUNT_STATEMENT_PRINT))
            ->name('ledger-export-pdf');
        Route::get('ledger-export-excel/{id}', [TreeAccountsController::class, 'ledgerExportExcel'])
            ->middleware($perm(AccountingPermissions::ACCOUNT_STATEMENT_PRINT))
            ->name('ledger-export-excel');
        Route::post('change-status-account', [TreeAccountsController::class, 'activateDeactivate'])
            ->name('change-status-account');
        Route::get('next-gl-code', [TreeAccountsController::class, 'nextGlCode'])
            ->middleware($perm(AccountingPermissions::TREE_CREATE))
            ->name('next-gl-code');
        Route::post('delete-account', [TreeAccountsController::class, 'deleteAccount'])
            ->middleware($perm(AccountingPermissions::TREE_DELETE))
            ->name('delete-account');
        Route::get('accounts-dropdown', [TreeAccountsController::class, 'accountsDropdown'])->name('accounts-dropdown');

        Route::get('accounting-settings', [AccountingSettingsController::class, 'index'])
            ->middleware($perm(...AccountingPermissions::settingsHubShowAny()))
            ->name('accounting-settings');

        Route::prefix('accounting/financial-years')->name('accounting.financial-years.')->group(function () use ($perm) {
            Route::get('/', [FinancialYearSettingsController::class, 'index'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->name('index');
            Route::get('next-range', [FinancialYearSettingsController::class, 'nextRange'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW, AccountingPermissions::SETTINGS_UPDATE))
                ->name('next-range');
            Route::post('/', [FinancialYearSettingsController::class, 'store'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->name('store');
            Route::put('settings/locking', [FinancialYearSettingsController::class, 'updateLocking'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->name('locking');

            Route::get('periods/{periodId}/view', [FinancialYearPagesController::class, 'showPeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('periodId')->name('periods.view');
            Route::get('periods/{periodId}/report', [FinancialYearPagesController::class, 'reportPeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('periodId')->name('periods.report');
            Route::get('periods/{periodId}/report/print', [FinancialYearPagesController::class, 'reportPeriodPrint'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('periodId')->name('periods.report.print');
            Route::get('periods/{periodId}/report/pdf', [FinancialYearPagesController::class, 'exportPeriodPdf'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('periodId')->name('periods.report.pdf');
            Route::put('periods/{periodId}', [FinancialYearSettingsController::class, 'updatePeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('periodId')->name('periods.update');
            Route::delete('periods/{periodId}', [FinancialYearSettingsController::class, 'destroyPeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('periodId')->name('periods.destroy');
            Route::post('periods/{id}/close', [FinancialYearSettingsController::class, 'closePeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('periods.close');
            Route::post('periods/{id}/open', [FinancialYearSettingsController::class, 'openPeriod'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('periods.open');

            Route::get('{id}/view', [FinancialYearPagesController::class, 'showYear'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('view');
            Route::get('{id}/report', [FinancialYearPagesController::class, 'reportYear'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('report');
            Route::get('{id}/report/print', [FinancialYearPagesController::class, 'reportYearPrint'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('report.print');
            Route::get('{id}/report/pdf', [FinancialYearPagesController::class, 'exportYearPdf'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('report.pdf');

            Route::get('{id}/accounting-close', [FinancialYearPagesController::class, 'accountingClose'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('accounting-close.page');
            Route::get('{id}/accounting-close/readiness', [FinancialYearSettingsController::class, 'accountingCloseReadiness'])
                ->middleware($perm(AccountingPermissions::SETTINGS_SHOW))
                ->whereNumber('id')->name('accounting-close.readiness');
            Route::get('{id}/accounting-close/preview', [FinancialYearSettingsController::class, 'accountingClosePreview'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('accounting-close.preview');
            Route::post('{id}/accounting-close/execute', [FinancialYearSettingsController::class, 'accountingCloseExecute'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('accounting-close.execute');

            Route::post('{id}/close', [FinancialYearSettingsController::class, 'closeYear'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('close');
            Route::post('{id}/open', [FinancialYearSettingsController::class, 'openYear'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('open');

            Route::put('{id}', [FinancialYearSettingsController::class, 'update'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('update');
            Route::delete('{id}', [FinancialYearSettingsController::class, 'destroy'])
                ->middleware($perm(AccountingPermissions::SETTINGS_UPDATE))
                ->whereNumber('id')->name('destroy');
        });

        Route::get('accounts-routing', [AccountsRoutingController::class, 'index'])
            ->middleware($perm(AccountingPermissions::ROUTING_SHOW))
            ->name('accounts-routing');
        Route::post('accounts-routing-store', [AccountsRoutingController::class, 'store'])
            ->middleware($perm(AccountingPermissions::ROUTING_UPDATE))
            ->name('accounts-routing-store');

        Route::get('journal-entry-index', [JournalEntryController::class, 'index'])
            ->middleware($perm(AccountingPermissions::JOURNAL_SHOW))
            ->name('journal-entry-index');
        Route::get('journal-entry-create', [JournalEntryController::class, 'create'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-create');
        Route::post('journal-entry-store', [JournalEntryController::class, 'store'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-store');
        Route::get('/journal-entry-edit/{id}', [JournalEntryController::class, 'edit'])
            ->middleware($perm(AccountingPermissions::JOURNAL_UPDATE))
            ->name('journal-entry-edit');
        Route::get('/journal-entry-show/{id}', [JournalEntryController::class, 'show'])
            ->middleware($perm(AccountingPermissions::JOURNAL_SHOW))
            ->name('journal-entry-show');
        Route::get('/journal-entry-duplication/{id}', [JournalEntryController::class, 'duplication'])
            ->middleware($perm(AccountingPermissions::JOURNAL_DUPLICATE))
            ->name('journal-entry-duplication');
        Route::post('journal-entry-update/{id}', [JournalEntryController::class, 'update'])
            ->middleware($perm(AccountingPermissions::JOURNAL_UPDATE))
            ->name('journal-entry-update');
        Route::get('journal-entry-destroy/{id}', [JournalEntryController::class, 'destroy'])
            ->middleware($perm(AccountingPermissions::JOURNAL_DELETE))
            ->name('journal-entry-destroy');
        Route::get('journal-entry-print/{id}', [JournalEntryController::class, 'print'])
            ->middleware($perm(AccountingPermissions::JOURNAL_PRINT))
            ->name('journal-entry-print');
        Route::get('journal-entry-export-pdf/{id}', [JournalEntryController::class, 'exportPDF'])
            ->middleware($perm(AccountingPermissions::JOURNAL_PRINT))
            ->name('journal-entry-export-pdf');
        Route::get('journal-entry-export-excel/{id}', [JournalEntryController::class, 'exportExcel'])
            ->middleware($perm(AccountingPermissions::JOURNAL_PRINT))
            ->name('journal-entry-export-excel');
        Route::get('journal-entry-attachment/{id}', [JournalEntryController::class, 'downloadAttachment'])
            ->middleware($perm(AccountingPermissions::JOURNAL_SHOW))
            ->name('journal-entry-attachment');

        Route::get('journal-entry/import', [JournalEntryImportController::class, 'importPage'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-import');
        Route::post('journal-entry/import/preview', [JournalEntryImportController::class, 'preview'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-import-preview');
        Route::post('journal-entry/import/process', [JournalEntryImportController::class, 'process'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-import-process');
        Route::post('journal-entry/import/cancel', [JournalEntryImportController::class, 'cancel'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE))
            ->name('journal-entry-import-cancel');

        Route::get('opening-balance/import', [OpeningBalanceImportController::class, 'importPage'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE, AccountingPermissions::TREE_CREATE))
            ->name('opening-balance-import');
        Route::post('opening-balance/import/preview', [OpeningBalanceImportController::class, 'preview'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE, AccountingPermissions::TREE_CREATE))
            ->name('opening-balance-import-preview');
        Route::post('opening-balance/import/process', [OpeningBalanceImportController::class, 'process'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE, AccountingPermissions::TREE_CREATE))
            ->name('opening-balance-import-process');
        Route::post('opening-balance/import/cancel', [OpeningBalanceImportController::class, 'cancel'])
            ->middleware($perm(AccountingPermissions::JOURNAL_CREATE, AccountingPermissions::TREE_CREATE))
            ->name('opening-balance-import-cancel');

        Route::get('cost-center-index', [CostCenterConrollerController::class, 'index'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_SHOW))
            ->name('cost-center-index');
        Route::post('cost-center-store', [CostCenterConrollerController::class, 'store'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_CREATE))
            ->name('cost-center-store');
        Route::post('cost-center-update', [CostCenterConrollerController::class, 'update'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_UPDATE))
            ->name('cost-center-update');
        Route::get('cost-center-print', [CostCenterConrollerController::class, 'print'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT))
            ->name('cost-center-print');
        Route::get('cost-center-transactions/{id}', [CostCenterConrollerController::class, 'transactions'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_TRANSACTIONS))
            ->name('cost-center-transactions');
        Route::get('cost-center-transactions-print/{id}', [CostCenterConrollerController::class, 'transactionsPrint'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT, AccountingPermissions::COST_CENTER_TRANSACTIONS))
            ->name('cost-center-transactions-print');
        Route::get('cost-center-transactions-export-pdf/{id}', [CostCenterConrollerController::class, 'exportTransactionsPDF'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT))
            ->name('cost-center-transactions-export-pdf');
        Route::get('cost-center-transactions-export-excel/{id}', [CostCenterConrollerController::class, 'exportTransactionsExcel'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT))
            ->name('cost-center-transactions-export-excel');
        Route::post('change-status-cost-center', [CostCenterConrollerController::class, 'changeStatus'])
            ->name('change-status-cost-center');
        Route::get('cost-center-export-pdf', [CostCenterConrollerController::class, 'exportPDF'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT))
            ->name('cost-center-export-pdf');
        Route::get('cost-center-export-excel', [CostCenterConrollerController::class, 'exportExcel'])
            ->middleware($perm(AccountingPermissions::COST_CENTER_PRINT))
            ->name('cost-center-export-excel');

        Route::get('payment-vouchers', [PaymentVouchersController::class, 'index'])
            ->middleware($perm(AccountingPermissions::PAYMENT_SHOW))
            ->name('payment-vouchers');
        Route::get('payment-vouchers/form-data', [PaymentVouchersController::class, 'formData'])
            ->middleware($perm(AccountingPermissions::PAYMENT_UPDATE, AccountingPermissions::PAYMENT_CREATE))
            ->name('payment-vouchers-form-data');
        Route::get('payment-vouchers/{id}', [PaymentVouchersController::class, 'show'])
            ->middleware($perm(AccountingPermissions::PAYMENT_SHOW))
            ->whereNumber('id')->name('payment-vouchers-show');
        Route::get('payment-vouchers/{id}/modal', [PaymentVouchersController::class, 'modal'])
            ->middleware($perm(AccountingPermissions::PAYMENT_SHOW))
            ->whereNumber('id')->name('payment-vouchers-modal');
        Route::get('payment-vouchers-export-pdf/{id}', [PaymentVouchersController::class, 'exportPDF'])
            ->middleware($perm(AccountingPermissions::PAYMENT_PRINT))
            ->whereNumber('id')->name('payment-vouchers-export-pdf');
        Route::put('payment-vouchers/{id}', [PaymentVouchersController::class, 'update'])
            ->middleware($perm(AccountingPermissions::PAYMENT_UPDATE))
            ->name('payment-vouchers-update');
        Route::delete('payment-vouchers/{id}', [PaymentVouchersController::class, 'destroy'])
            ->middleware($perm(AccountingPermissions::PAYMENT_DELETE))
            ->whereNumber('id')->name('payment-vouchers-destroy');
        Route::post('payment-vouchers-store', [PaymentVouchersController::class, 'store'])
            ->middleware($perm(AccountingPermissions::PAYMENT_CREATE))
            ->name('payment-vouchers-store');

        Route::get('receipt-vouchers', [ReceiptVouchersController::class, 'index'])
            ->middleware($perm(AccountingPermissions::RECEIPT_SHOW))
            ->name('receipt-vouchers');
        Route::get('receipt-vouchers/form-data', [ReceiptVouchersController::class, 'formData'])
            ->middleware($perm(AccountingPermissions::RECEIPT_UPDATE, AccountingPermissions::RECEIPT_CREATE))
            ->name('receipt-vouchers-form-data');
        Route::get('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'show'])
            ->middleware($perm(AccountingPermissions::RECEIPT_SHOW))
            ->whereNumber('id')->name('receipt-vouchers-show');
        Route::get('receipt-vouchers/{id}/modal', [ReceiptVouchersController::class, 'modal'])
            ->middleware($perm(AccountingPermissions::RECEIPT_SHOW))
            ->whereNumber('id')->name('receipt-vouchers-modal');
        Route::get('receipt-vouchers-export-pdf/{id}', [ReceiptVouchersController::class, 'exportPDF'])
            ->middleware($perm(AccountingPermissions::RECEIPT_PRINT))
            ->whereNumber('id')->name('receipt-vouchers-export-pdf');
        Route::put('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'update'])
            ->middleware($perm(AccountingPermissions::RECEIPT_UPDATE))
            ->name('receipt-vouchers-update');
        Route::delete('receipt-vouchers/{id}', [ReceiptVouchersController::class, 'destroy'])
            ->middleware($perm(AccountingPermissions::RECEIPT_DELETE))
            ->whereNumber('id')->name('receipt-vouchers-destroy');
        Route::post('receipt-vouchers-store', [ReceiptVouchersController::class, 'store'])
            ->middleware($perm(AccountingPermissions::RECEIPT_CREATE))
            ->name('receipt-vouchers-store');

        $tb = AccountingPermissions::report('trial-balance');
        $is = AccountingPermissions::report('income-statement');
        $bs = AccountingPermissions::report('balance-sheet');
        $jl = AccountingPermissions::report('journal-ledger');
        $er = AccountingPermissions::report('expense-report');
        $cf = AccountingPermissions::report('cash-flow');
        $cs = AccountingPermissions::report('customers-suppliers');
        $ra = AccountingPermissions::report('receivables-aging');
        $rar = AccountingPermissions::report('receivables-age-report');
        $pa = AccountingPermissions::report('payables-aging');
        $par = AccountingPermissions::report('payables-age-report');

        Route::get('accounting-reports', [AccountingReportsController::class, 'index'])
            ->middleware($perm(...AccountingPermissions::reportShowAny()))
            ->name('accounting-reports');
        Route::get('income-statement', [AccountingReportsController::class, 'incomeStatement'])
            ->middleware($perm($is['show']))
            ->name('income-statement');
        Route::get('income-statement-export-pdf', [AccountingReportsController::class, 'incomeStatementExportPdf'])
            ->middleware($perm($is['print']))
            ->name('income-statement-export-pdf');
        Route::get('income-statement-export-excel', [AccountingReportsController::class, 'incomeStatementExportExcel'])
            ->middleware($perm($is['print']))
            ->name('income-statement-export-excel');
        Route::get('trial-balance', [AccountingReportsController::class, 'trialBalance'])
            ->middleware($perm($tb['show']))
            ->name('trial-balance');
        Route::get('trial-balance-export-pdf', [AccountingReportsController::class, 'trialBalanceExportPdf'])
            ->middleware($perm($tb['print']))
            ->name('trial-balance-export-pdf');
        Route::get('trial-balance-export-excel', [AccountingReportsController::class, 'trialBalanceExportExcel'])
            ->middleware($perm($tb['print']))
            ->name('trial-balance-export-excel');
        Route::get('balance-sheet', [AccountingReportsController::class, 'balanceSheet'])
            ->middleware($perm($bs['show']))
            ->name('balance-sheet');
        Route::get('balance-sheet-export-pdf', [AccountingReportsController::class, 'balanceSheetExportPdf'])
            ->middleware($perm($bs['print']))
            ->name('balance-sheet-export-pdf');
        Route::get('balance-sheet-export-excel', [AccountingReportsController::class, 'balanceSheetExportExcel'])
            ->middleware($perm($bs['print']))
            ->name('balance-sheet-export-excel');
        Route::get('journal-report', [AccountingReportsController::class, 'JournalReport'])
            ->middleware($perm($jl['show']))
            ->name('journal-report');
        Route::get('journal-report-export-pdf', [AccountingReportsController::class, 'journalReportExportPdf'])
            ->middleware($perm($jl['print']))
            ->name('journal-report-export-pdf');
        Route::get('journal-report-export-excel', [AccountingReportsController::class, 'journalReportExportExcel'])
            ->middleware($perm($jl['print']))
            ->name('journal-report-export-excel');

        Route::get('expense-report', [AccountingReportsController::class, 'expenseReport'])
            ->middleware($perm($er['show']))
            ->name('expense-report');
        Route::get('expense-report-export-pdf', [AccountingReportsController::class, 'expenseReportExportPdf'])
            ->middleware($perm($er['print']))
            ->name('expense-report-export-pdf');
        Route::get('expense-report-export-excel', [AccountingReportsController::class, 'expenseReportExportExcel'])
            ->middleware($perm($er['print']))
            ->name('expense-report-export-excel');

        Route::get('cash-flow', [AccountingReportsController::class, 'cash_flow'])
            ->middleware($perm($cf['show']))
            ->name('cash-flow');
        Route::get('cash-flow-export-pdf', [AccountingReportsController::class, 'cashFlowExportPdf'])
            ->middleware($perm($cf['print']))
            ->name('cash-flow-export-pdf');
        Route::get('cash-flow-export-excel', [AccountingReportsController::class, 'cashFlowExportExcel'])
            ->middleware($perm($cf['print']))
            ->name('cash-flow-export-excel');
        Route::get('customers-suppliers-statement', [AccountingReportsController::class, 'customersSuppliersStatement'])
            ->middleware($perm($cs['show']))
            ->name('customers-suppliers-statement');
        Route::get('customers-suppliers-statement-export-pdf', [AccountingReportsController::class, 'customersSuppliersStatementExportPdf'])
            ->middleware($perm($cs['print']))
            ->name('customers-suppliers-statement-export-pdf');
        Route::get('customers-suppliers-statement-export-excel', [AccountingReportsController::class, 'customersSuppliersStatementExportExcel'])
            ->middleware($perm($cs['print']))
            ->name('customers-suppliers-statement-export-excel');

        Route::get('account-receivable-ageing-report', [AccountingReportsController::class, 'accountReceivableAgeingReport'])
            ->middleware($perm($ra['show']))
            ->name('account-receivable-ageing-report');
        Route::get('account-receivable-ageing-details', [AccountingReportsController::class, 'accountReceivableAgeingDetails'])
            ->middleware($perm($rar['show']))
            ->name('account-receivable-ageing-details');
        Route::get('account-receivable-ageing-report-export-pdf', [AccountingReportsController::class, 'accountReceivableAgeingReportExportPdf'])
            ->middleware($perm($ra['print']))
            ->name('account-receivable-ageing-report-export-pdf');
        Route::get('account-receivable-ageing-report-export-excel', [AccountingReportsController::class, 'accountReceivableAgeingReportExportExcel'])
            ->middleware($perm($ra['print']))
            ->name('account-receivable-ageing-report-export-excel');
        Route::get('account-receivable-ageing-details-export-pdf', [AccountingReportsController::class, 'accountReceivableAgeingDetailsExportPdf'])
            ->middleware($perm($rar['print']))
            ->name('account-receivable-ageing-details-export-pdf');
        Route::get('account-receivable-ageing-details-export-excel', [AccountingReportsController::class, 'accountReceivableAgeingDetailsExportExcel'])
            ->middleware($perm($rar['print']))
            ->name('account-receivable-ageing-details-export-excel');

        Route::get('account-payable-ageing-report', [AccountingReportsController::class, 'accountPayableAgeingReport'])
            ->middleware($perm($pa['show']))
            ->name('account-payable-ageing-report');
        Route::get('account-payable-ageing-details', [AccountingReportsController::class, 'accountPayableAgeingDetails'])
            ->middleware($perm($par['show']))
            ->name('account-payable-ageing-details');
        Route::get('account-payable-ageing-report-export-pdf', [AccountingReportsController::class, 'accountPayableAgeingReportExportPdf'])
            ->middleware($perm($pa['print']))
            ->name('account-payable-ageing-report-export-pdf');
        Route::get('account-payable-ageing-report-export-excel', [AccountingReportsController::class, 'accountPayableAgeingReportExportExcel'])
            ->middleware($perm($pa['print']))
            ->name('account-payable-ageing-report-export-excel');
        Route::get('account-payable-ageing-details-export-pdf', [AccountingReportsController::class, 'accountPayableAgeingDetailsExportPdf'])
            ->middleware($perm($par['print']))
            ->name('account-payable-ageing-details-export-pdf');
        Route::get('account-payable-ageing-details-export-excel', [AccountingReportsController::class, 'accountPayableAgeingDetailsExportExcel'])
            ->middleware($perm($par['print']))
            ->name('account-payable-ageing-details-export-excel');

        Route::post('/track-action', [AccountingReportsController::class, 'track'])->name('track.action');

        Route::get('/get-products-by-establishment/{establishmentId}', [PeriodicInventoryController::class, 'getProductsByEstablishment'])
            ->middleware($perm(AccountingPermissions::PERIODIC_SHOW, AccountingPermissions::PERIODIC_CREATE, AccountingPermissions::PERIODIC_UPDATE));
        Route::prefix('inventory')->group(function () use ($perm) {
            Route::get('periodic-inventory/export/list-excel', [PeriodicInventoryController::class, 'exportListExcel'])
                ->middleware($perm(AccountingPermissions::PERIODIC_PRINT))
                ->name('periodic-inventory-list-export-excel');
            Route::get('periodic-inventory/{id}/export-excel', [PeriodicInventoryController::class, 'exportDetailExcel'])
                ->middleware($perm(AccountingPermissions::PERIODIC_PRINT))
                ->whereNumber('id')
                ->name('periodic-inventory-detail-export-excel');
            Route::get('periodic-inventory-export-pdf/{id}', [PeriodicInventoryController::class, 'exportPdf'])
                ->middleware($perm(AccountingPermissions::PERIODIC_PRINT))
                ->name('periodic-inventory-export-pdf');
            Route::post('periodic-inventory/{id}/approve', [PeriodicInventoryController::class, 'approve'])
                ->middleware($perm(AccountingPermissions::PERIODIC_UPDATE))
                ->whereNumber('id')
                ->name('periodic-inventory-approve');
            Route::get('periodic-inventory', [PeriodicInventoryController::class, 'index'])
                ->middleware($perm(AccountingPermissions::PERIODIC_SHOW))
                ->name('periodic-inventory.index');
            Route::get('periodic-inventory/create', [PeriodicInventoryController::class, 'create'])
                ->middleware($perm(AccountingPermissions::PERIODIC_CREATE))
                ->name('periodic-inventory.create');
            Route::post('periodic-inventory', [PeriodicInventoryController::class, 'store'])
                ->middleware($perm(AccountingPermissions::PERIODIC_CREATE))
                ->name('periodic-inventory.store');
            Route::get('periodic-inventory/{periodic_inventory}/edit', [PeriodicInventoryController::class, 'edit'])
                ->middleware($perm(AccountingPermissions::PERIODIC_UPDATE))
                ->name('periodic-inventory.edit');
            Route::put('periodic-inventory/{periodic_inventory}', [PeriodicInventoryController::class, 'update'])
                ->middleware($perm(AccountingPermissions::PERIODIC_UPDATE))
                ->name('periodic-inventory.update');
        });
    });
});
