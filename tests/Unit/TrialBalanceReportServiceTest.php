<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Accounting\Services\TrialBalanceReportService;
use Tests\TestCase;

class TrialBalanceReportServiceTest extends TestCase
{
    public function test_period_movement_excludes_opening_balance(): void
    {
        $account = (object) [
            'debit_opening_balance' => 1000.0,
            'credit_opening_balance' => 0.0,
            'debit_balance' => 200.0,
            'credit_balance' => 50.0,
        ];

        $period = TrialBalanceReportService::periodMovement($account);
        $closing = TrialBalanceReportService::closingBalance($account);

        $this->assertSame(150.0, $period['period_net']);
        $this->assertSame(150.0, $period['period_debit_net']);
        $this->assertSame(0.0, $period['period_credit_net']);

        $this->assertSame(1150.0, $closing['closing_debit_balance']);
        $this->assertSame(0.0, $closing['closing_credit_balance']);
    }

    public function test_normalize_primary_type_aliases(): void
    {
        $this->assertSame('liabilities', TrialBalanceReportService::normalizePrimaryType('liability'));
        $this->assertSame('expenses', TrialBalanceReportService::normalizePrimaryType('expense'));
    }

    public function test_with_accordion_groups_inserts_headers(): void
    {
        $rows = collect([
            (object) [
                'id' => 1,
                'gl_code' => '111',
                'name' => 'Cash',
                'account_primary_type' => 'asset',
                'debit_opening_balance' => 10,
                'credit_opening_balance' => 0,
                'debit_balance' => 5,
                'credit_balance' => 0,
            ],
            (object) [
                'id' => 2,
                'gl_code' => '511',
                'name' => 'Rent',
                'account_primary_type' => 'expenses',
                'debit_opening_balance' => 0,
                'credit_opening_balance' => 0,
                'debit_balance' => 20,
                'credit_balance' => 0,
            ],
        ]);

        $grouped = TrialBalanceReportService::withAccordionGroups($rows);

        $this->assertTrue((bool) $grouped->first()->is_group);
        $this->assertSame('asset', $grouped->first()->group_key);
        $this->assertGreaterThan(2, $grouped->count());
        $this->assertSame(2, $grouped->where('is_group', true)->count());
    }

    public function test_pl_opening_warning_detects_residuals(): void
    {
        $rows = new Collection([
            (object) [
                'is_group' => false,
                'gl_code' => '517',
                'account_primary_type' => 'expenses',
                'debit_opening_balance' => 41819.18,
                'credit_opening_balance' => 0,
            ],
            (object) [
                'is_group' => false,
                'gl_code' => '111',
                'account_primary_type' => 'asset',
                'debit_opening_balance' => 100,
                'credit_opening_balance' => 0,
            ],
        ]);

        $warning = TrialBalanceReportService::plOpeningWarning($rows, '2026-01-01');

        $this->assertTrue($warning['show_warning']);
        $this->assertSame(1, $warning['pl_opening_count']);
        $this->assertNotEmpty($warning['message']);
    }
}
