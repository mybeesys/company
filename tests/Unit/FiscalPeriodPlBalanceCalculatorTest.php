<?php

namespace Tests\Unit;

use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodPlBalanceCalculator;
use Tests\TestCase;

class FiscalPeriodPlBalanceCalculatorTest extends TestCase
{
    public function test_leading_gl_digit_ignores_separators(): void
    {
        $calc = new FiscalPeriodPlBalanceCalculator();

        $this->assertSame('5', $calc->leadingGlDigit('5.17'));
        $this->assertSame('4', $calc->leadingGlDigit('41-01'));
        $this->assertSame('', $calc->leadingGlDigit(''));
    }

    public function test_is_income_falls_back_to_gl_prefix(): void
    {
        $calc = new FiscalPeriodPlBalanceCalculator();

        $this->assertTrue($calc->isIncomeAccount((object) [
            'account_type' => 'normal',
            'account_primary_type' => 'normal',
            'gl_code' => '411',
        ]));

        $this->assertFalse($calc->isIncomeAccount((object) [
            'account_type' => 'normal',
            'account_primary_type' => 'normal',
            'gl_code' => '517',
        ]));

        $this->assertTrue($calc->isIncomeAccount((object) [
            'account_type' => 'income',
            'account_primary_type' => 'asset',
            'gl_code' => '111',
        ]));
    }
}
