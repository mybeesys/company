<?php

namespace Tests\Unit;

use Modules\Accounting\Support\AccountingPermissions;
use PHPUnit\Framework\TestCase;

class AccountingPermissionsTest extends TestCase
{
    public function test_maps_sidebar_entities_to_existing_ems_names(): void
    {
        $this->assertSame('accounting.Accounts tree.show', AccountingPermissions::crud('tree')['show']);
        $this->assertSame('accounting.Daily entries.create', AccountingPermissions::crud('journal')['create']);
        $this->assertSame('accounting.Receipt vouchers.update', AccountingPermissions::crud('receipt')['update']);
        $this->assertSame('accounting.Payment vouchers.delete', AccountingPermissions::crud('payment')['delete']);
        $this->assertSame('accounting.Expenses.show', AccountingPermissions::crud('expenses')['show']);
        $this->assertSame('accounting.Cost center transactions.show', AccountingPermissions::COST_CENTER_TRANSACTIONS);
        $this->assertSame('accounting.Periodic inventory.update', AccountingPermissions::crud('periodic')['update']);
        $this->assertSame('accounting.Settings.show', AccountingPermissions::crud('settings')['show']);
        $this->assertSame('accounting.Accounts routing.update', AccountingPermissions::crud('routing')['update']);
    }

    public function test_maps_accounting_documents_and_leaves_sales_purchases_unmapped(): void
    {
        $this->assertSame(AccountingPermissions::JOURNAL_UPDATE, AccountingPermissions::forTransactionType('journal_entry', 'update'));
        $this->assertSame(AccountingPermissions::RECEIPT_CREATE, AccountingPermissions::forTransactionType('receipt_voucher', 'create'));
        $this->assertSame(AccountingPermissions::PAYMENT_DELETE, AccountingPermissions::forTransactionType('payment_voucher', 'delete'));
        $this->assertNull(AccountingPermissions::forTransactionType('sell', 'show'));
        $this->assertNull(AccountingPermissions::forTransactionType('purchases', 'update'));
    }

    public function test_report_catalog_covers_hub_and_expense_report_gap(): void
    {
        $this->assertSame('accountingReports.Trial balance.show', AccountingPermissions::report('trial-balance')['show']);
        $this->assertSame('accountingReports.Account statement.show', AccountingPermissions::report('account-statement')['show']);
        $this->assertSame('accountingReports.Expense report.print', AccountingPermissions::report('expense-report')['print']);
        $this->assertSame('accountingReports.Receivables aging.show', AccountingPermissions::report('receivables-aging')['show']);
        $this->assertSame('accountingReports.Receivables age report.show', AccountingPermissions::report('receivables-age-report')['show']);
        $this->assertSame('accountingReports.Payables aging.show', AccountingPermissions::report('payables-aging')['show']);
        $this->assertSame('accountingReports.Payables age report.print', AccountingPermissions::report('payables-age-report')['print']);
        $this->assertContains(AccountingPermissions::EXPENSE_REPORT_SHOW, AccountingPermissions::reportShowAny());
        $this->assertContains(AccountingPermissions::RECEIVABLES_AGE_REPORT_SHOW, AccountingPermissions::reportShowAny());
        $this->assertContains(AccountingPermissions::SETTINGS_SHOW, AccountingPermissions::settingsHubShowAny());
        $this->assertContains(AccountingPermissions::ROUTING_SHOW, AccountingPermissions::settingsHubShowAny());
    }

    public function test_new_gap_names_exist_in_catalog_definitions(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            AccountingPermissions::RECEIPT_UPDATE,
            AccountingPermissions::PAYMENT_DELETE,
            AccountingPermissions::EXPENSES_DELETE,
            AccountingPermissions::PERIODIC_SHOW,
            AccountingPermissions::SETTINGS_UPDATE,
            AccountingPermissions::EXPENSE_REPORT_SHOW,
            AccountingPermissions::RECEIVABLES_AGE_REPORT_SHOW,
            AccountingPermissions::RECEIVABLES_AGE_REPORT_PRINT,
            AccountingPermissions::DASHBOARD_SHOW,
            AccountingPermissions::JOURNAL_DUPLICATE,
        ] as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_rejects_entities_outside_accounting_menu(): void
    {
        foreach (['warehouse', 'serviceFee'] as $entity) {
            try {
                AccountingPermissions::crud($entity);
                $this->fail("Expected unknown accounting EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
