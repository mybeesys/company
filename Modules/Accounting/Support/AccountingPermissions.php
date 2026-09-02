<?php

namespace Modules\Accounting\Support;

/**
 * Central Accounting EMS permission names (must match dashboard-permissions.php).
 */
final class AccountingPermissions
{
    public const ALL_SHOW = 'accounting.all.show';

    public const ALL_PRINT = 'accounting.all.print';

    public const ALL_CREATE = 'accounting.all.create';

    public const ALL_UPDATE = 'accounting.all.update';

    public const ALL_DELETE = 'accounting.all.delete';

    public const DASHBOARD_SHOW = 'accounting.Dashboard.show';

    public const TREE_SHOW = 'accounting.Accounts tree.show';

    public const TREE_CREATE = 'accounting.Accounts tree.create';

    public const TREE_UPDATE = 'accounting.Accounts tree.update';

    public const TREE_DELETE = 'accounting.Accounts tree.delete';

    public const LEDGER_SHOW = 'accounting.Ledger.show';

    public const TREE_ACTIVATE = 'accounting.Accounts tree active.create';

    public const TREE_DEACTIVATE = 'accounting.Accounts tree deactive.create';

    public const ROUTING_SHOW = 'accounting.Accounts routing.show';

    public const ROUTING_UPDATE = 'accounting.Accounts routing.update';

    public const JOURNAL_SHOW = 'accounting.Daily entries.show';

    public const JOURNAL_PRINT = 'accounting.Daily entries.print';

    public const JOURNAL_CREATE = 'accounting.Daily entries.create';

    public const JOURNAL_UPDATE = 'accounting.Daily entries.update';

    public const JOURNAL_DELETE = 'accounting.Daily entries.delete';

    public const JOURNAL_DUPLICATE = 'accounting.Daily entries duplication.create';

    public const COST_CENTER_SHOW = 'accounting.Cost center.show';

    public const COST_CENTER_PRINT = 'accounting.Cost center.print';

    public const COST_CENTER_CREATE = 'accounting.Cost center.create';

    public const COST_CENTER_UPDATE = 'accounting.Cost center.update';

    public const COST_CENTER_TRANSACTIONS = 'accounting.Cost center transactions.show';

    public const COST_CENTER_ACTIVATE = 'accounting.Cost center active.create';

    public const COST_CENTER_DEACTIVATE = 'accounting.Cost center deactive.create';

    public const RECEIPT_SHOW = 'accounting.Receipt vouchers.show';

    public const RECEIPT_PRINT = 'accounting.Receipt vouchers.print';

    public const RECEIPT_CREATE = 'accounting.Receipt vouchers.create';

    public const RECEIPT_UPDATE = 'accounting.Receipt vouchers.update';

    public const RECEIPT_DELETE = 'accounting.Receipt vouchers.delete';

    public const PAYMENT_SHOW = 'accounting.Payment vouchers.show';

    public const PAYMENT_PRINT = 'accounting.Payment vouchers.print';

    public const PAYMENT_CREATE = 'accounting.Payment vouchers.create';

    public const PAYMENT_UPDATE = 'accounting.Payment vouchers.update';

    public const PAYMENT_DELETE = 'accounting.Payment vouchers.delete';

    public const EXPENSES_SHOW = 'accounting.Expenses.show';

    public const EXPENSES_CREATE = 'accounting.Expenses.create';

    public const EXPENSES_UPDATE = 'accounting.Expenses.update';

    public const EXPENSES_DELETE = 'accounting.Expenses.delete';

    public const PERIODIC_SHOW = 'accounting.Periodic inventory.show';

    public const PERIODIC_PRINT = 'accounting.Periodic inventory.print';

    public const PERIODIC_CREATE = 'accounting.Periodic inventory.create';

    public const PERIODIC_UPDATE = 'accounting.Periodic inventory.update';

    public const SETTINGS_SHOW = 'accounting.Settings.show';

    public const SETTINGS_UPDATE = 'accounting.Settings.update';

    public const REPORTS_ALL_SHOW = 'accountingReports.all.show';

    public const REPORTS_ALL_PRINT = 'accountingReports.all.print';

    public const TRIAL_BALANCE_SHOW = 'accountingReports.Trial balance.show';

    public const TRIAL_BALANCE_PRINT = 'accountingReports.Trial balance.print';

    public const INCOME_STATEMENT_SHOW = 'accountingReports.Income statement.show';

    public const INCOME_STATEMENT_PRINT = 'accountingReports.Income statement.print';

    public const ACCOUNT_STATEMENT_SHOW = 'accountingReports.Account statement.show';

    public const ACCOUNT_STATEMENT_PRINT = 'accountingReports.Account statement.print';

    public const BALANCE_SHEET_SHOW = 'accountingReports.Balance sheet.show';

    public const BALANCE_SHEET_PRINT = 'accountingReports.Balance sheet.print';

    public const JOURNAL_LEDGER_SHOW = 'accountingReports.Journal ledger.show';

    public const JOURNAL_LEDGER_PRINT = 'accountingReports.Journal ledger.print';

    public const CASH_FLOW_SHOW = 'accountingReports.Cash flow.show';

    public const CASH_FLOW_PRINT = 'accountingReports.Cash flow.print';

    public const EXPENSE_REPORT_SHOW = 'accountingReports.Expense report.show';

    public const EXPENSE_REPORT_PRINT = 'accountingReports.Expense report.print';

    public const CUSTOMERS_SUPPLIERS_SHOW = 'accountingReports.Customers suppliers statement.show';

    public const CUSTOMERS_SUPPLIERS_PRINT = 'accountingReports.Customers suppliers statement.print';

    public const RECEIVABLES_AGING_SHOW = 'accountingReports.Receivables aging.show';

    public const RECEIVABLES_AGING_PRINT = 'accountingReports.Receivables aging.print';

    public const RECEIVABLES_AGE_REPORT_SHOW = 'accountingReports.Receivables age report.show';

    public const RECEIVABLES_AGE_REPORT_PRINT = 'accountingReports.Receivables age report.print';

    public const PAYABLES_AGING_SHOW = 'accountingReports.Payables aging.show';

    public const PAYABLES_AGING_PRINT = 'accountingReports.Payables aging.print';

    public const PAYABLES_AGE_REPORT_SHOW = 'accountingReports.Payables age report.show';

    public const PAYABLES_AGE_REPORT_PRINT = 'accountingReports.Payables age report.print';

    /**
     * @return array{show?: string, print?: string, create?: string, update?: string, delete?: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'tree' => [
                'show' => self::TREE_SHOW,
                'create' => self::TREE_CREATE,
                'update' => self::TREE_UPDATE,
                'delete' => self::TREE_DELETE,
            ],
            'journal' => [
                'show' => self::JOURNAL_SHOW,
                'print' => self::JOURNAL_PRINT,
                'create' => self::JOURNAL_CREATE,
                'update' => self::JOURNAL_UPDATE,
                'delete' => self::JOURNAL_DELETE,
            ],
            'receipt' => [
                'show' => self::RECEIPT_SHOW,
                'print' => self::RECEIPT_PRINT,
                'create' => self::RECEIPT_CREATE,
                'update' => self::RECEIPT_UPDATE,
                'delete' => self::RECEIPT_DELETE,
            ],
            'payment' => [
                'show' => self::PAYMENT_SHOW,
                'print' => self::PAYMENT_PRINT,
                'create' => self::PAYMENT_CREATE,
                'update' => self::PAYMENT_UPDATE,
                'delete' => self::PAYMENT_DELETE,
            ],
            'expenses' => [
                'show' => self::EXPENSES_SHOW,
                'create' => self::EXPENSES_CREATE,
                'update' => self::EXPENSES_UPDATE,
                'delete' => self::EXPENSES_DELETE,
            ],
            'costCenter' => [
                'show' => self::COST_CENTER_SHOW,
                'print' => self::COST_CENTER_PRINT,
                'create' => self::COST_CENTER_CREATE,
                'update' => self::COST_CENTER_UPDATE,
            ],
            'periodic' => [
                'show' => self::PERIODIC_SHOW,
                'print' => self::PERIODIC_PRINT,
                'create' => self::PERIODIC_CREATE,
                'update' => self::PERIODIC_UPDATE,
            ],
            'settings' => [
                'show' => self::SETTINGS_SHOW,
                'update' => self::SETTINGS_UPDATE,
            ],
            'routing' => [
                'show' => self::ROUTING_SHOW,
                'update' => self::ROUTING_UPDATE,
            ],
            default => throw new \InvalidArgumentException("Unknown accounting EMS entity [{$entity}]"),
        };
    }

    /**
     * Map accounting document types. Returns null for sales/purchases so those stay unconstrained here.
     */
    public static function forTransactionType(?string $type, string $action): ?string
    {
        $map = match ($type) {
            'journal_entry' => [
                'show' => self::JOURNAL_SHOW,
                'print' => self::JOURNAL_PRINT,
                'create' => self::JOURNAL_CREATE,
                'update' => self::JOURNAL_UPDATE,
                'delete' => self::JOURNAL_DELETE,
            ],
            'receipt_voucher' => [
                'show' => self::RECEIPT_SHOW,
                'print' => self::RECEIPT_PRINT,
                'create' => self::RECEIPT_CREATE,
                'update' => self::RECEIPT_UPDATE,
                'delete' => self::RECEIPT_DELETE,
            ],
            'payment_voucher' => [
                'show' => self::PAYMENT_SHOW,
                'print' => self::PAYMENT_PRINT,
                'create' => self::PAYMENT_CREATE,
                'update' => self::PAYMENT_UPDATE,
                'delete' => self::PAYMENT_DELETE,
            ],
            default => null,
        };

        if ($map === null) {
            return null;
        }

        return $map[$action] ?? null;
    }

    /**
     * @return array{show: string, print: string}
     */
    public static function report(string $key): array
    {
        return match ($key) {
            'trial-balance' => ['show' => self::TRIAL_BALANCE_SHOW, 'print' => self::TRIAL_BALANCE_PRINT],
            'income-statement' => ['show' => self::INCOME_STATEMENT_SHOW, 'print' => self::INCOME_STATEMENT_PRINT],
            'account-statement' => ['show' => self::ACCOUNT_STATEMENT_SHOW, 'print' => self::ACCOUNT_STATEMENT_PRINT],
            'balance-sheet' => ['show' => self::BALANCE_SHEET_SHOW, 'print' => self::BALANCE_SHEET_PRINT],
            'journal-ledger' => ['show' => self::JOURNAL_LEDGER_SHOW, 'print' => self::JOURNAL_LEDGER_PRINT],
            'cash-flow' => ['show' => self::CASH_FLOW_SHOW, 'print' => self::CASH_FLOW_PRINT],
            'expense-report' => ['show' => self::EXPENSE_REPORT_SHOW, 'print' => self::EXPENSE_REPORT_PRINT],
            'customers-suppliers' => ['show' => self::CUSTOMERS_SUPPLIERS_SHOW, 'print' => self::CUSTOMERS_SUPPLIERS_PRINT],
            'receivables-aging' => ['show' => self::RECEIVABLES_AGING_SHOW, 'print' => self::RECEIVABLES_AGING_PRINT],
            'receivables-age-report' => ['show' => self::RECEIVABLES_AGE_REPORT_SHOW, 'print' => self::RECEIVABLES_AGE_REPORT_PRINT],
            'payables-aging' => ['show' => self::PAYABLES_AGING_SHOW, 'print' => self::PAYABLES_AGING_PRINT],
            'payables-age-report' => ['show' => self::PAYABLES_AGE_REPORT_SHOW, 'print' => self::PAYABLES_AGE_REPORT_PRINT],
            default => throw new \InvalidArgumentException("Unknown accounting report [{$key}]"),
        };
    }

    /**
     * @return list<string>
     */
    public static function reportShowAny(): array
    {
        return [
            self::REPORTS_ALL_SHOW,
            self::TRIAL_BALANCE_SHOW,
            self::INCOME_STATEMENT_SHOW,
            self::ACCOUNT_STATEMENT_SHOW,
            self::BALANCE_SHEET_SHOW,
            self::JOURNAL_LEDGER_SHOW,
            self::CASH_FLOW_SHOW,
            self::EXPENSE_REPORT_SHOW,
            self::CUSTOMERS_SUPPLIERS_SHOW,
            self::RECEIVABLES_AGING_SHOW,
            self::RECEIVABLES_AGE_REPORT_SHOW,
            self::PAYABLES_AGING_SHOW,
            self::PAYABLES_AGE_REPORT_SHOW,
        ];
    }

    /**
     * Settings hub (routing tab OR financial-year tab).
     *
     * @return list<string>
     */
    public static function settingsHubShowAny(): array
    {
        return [
            self::SETTINGS_SHOW,
            self::ROUTING_SHOW,
        ];
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'accounting.')
                || str_starts_with((string) ($row['name'] ?? ''), 'accountingReports.')
        );

        $unique = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '' || isset($unique[$name])) {
                continue;
            }
            $unique[$name] = $row;
        }

        return array_values($unique);
    }
}
