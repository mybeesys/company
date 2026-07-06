<?php

namespace Tests\Unit;

use Modules\Accounting\Support\AccountingOpeningBalanceScope;
use Tests\TestCase;

class AccountingOpeningBalanceScopeTest extends TestCase
{
    public function test_opening_scope_includes_prior_dates_and_opening_balance_on_start(): void
    {
        $query = AccountingOpeningBalanceScope::applyOpeningScope(
            AccountingAccountsTransactionQueryStub::query(),
            '2025-01-01'
        );

        $this->assertStringContainsString('operation_date', $query->toSql());
        $this->assertStringContainsString('sub_type', $query->toSql());
        $this->assertContains(AccountingOpeningBalanceScope::SUB_TYPE, $query->getBindings());
    }

    public function test_period_scope_excludes_opening_balance_on_start_date(): void
    {
        $query = AccountingOpeningBalanceScope::applyExcludeOpeningOnStartFromPeriod(
            AccountingAccountsTransactionQueryStub::query(),
            '2025-01-01'
        );

        $this->assertStringContainsString('sub_type', $query->toSql());
        $this->assertContains(AccountingOpeningBalanceScope::SUB_TYPE, $query->getBindings());
    }

    public function test_opening_condition_sql_uses_start_date_twice(): void
    {
        $sql = AccountingOpeningBalanceScope::openingConditionSql();

        $this->assertSame(2, substr_count($sql, 'DATE(?)'));
        $this->assertStringContainsString(AccountingOpeningBalanceScope::SUB_TYPE, $sql);
    }

    public function test_period_condition_sql_uses_three_placeholders(): void
    {
        $sql = AccountingOpeningBalanceScope::periodConditionSql();

        $this->assertSame(3, substr_count($sql, 'DATE(?)'));
        $this->assertStringContainsString('NOT', $sql);
    }
}

/**
 * Minimal Eloquent stub so scope tests do not need a database connection.
 */
class AccountingAccountsTransactionQueryStub extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'accounting_accounts_transactions';

    public static function query(): \Illuminate\Database\Eloquent\Builder
    {
        return (new static)->newQuery();
    }
}
