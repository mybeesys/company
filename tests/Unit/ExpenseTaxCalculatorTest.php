<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Expense\Services\ExpenseTaxCalculator;
use Tests\TestCase;

final class ExpenseTaxCalculatorTest extends TestCase
{
    public function test_extracts_tax_from_inclusive_total(): void
    {
        $result = ExpenseTaxCalculator::extractTaxFromInclusiveTotal(115.0, 15.0);

        $this->assertEqualsWithDelta(15.0, $result['tax'], 0.02);
        $this->assertEqualsWithDelta(100.0, $result['net'], 0.02);
    }

    public function test_zero_percent_returns_no_tax(): void
    {
        $result = ExpenseTaxCalculator::extractTaxFromInclusiveTotal(100.0, 0.0);

        $this->assertSame(0.0, $result['tax']);
        $this->assertSame(100.0, $result['net']);
    }

    public function test_exclusive_net_adds_vat_on_top(): void
    {
        $result = ExpenseTaxCalculator::computeTaxFromExclusiveNet(100.0, 15.0);

        $this->assertEqualsWithDelta(15.0, $result['tax'], 0.000001);
        $this->assertEqualsWithDelta(115.0, $result['gross'], 0.000001);
    }
}
