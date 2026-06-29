<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\General\Support\TransactionLineTaxRate;
use Tests\TestCase;

final class TransactionLineTaxRateTest extends TestCase
{
    public function test_display_percent_returns_stored_value_when_not_a_tax_record_id(): void
    {
        $this->assertSame('15', TransactionLineTaxRate::displayPercent('15'));
        $this->assertSame('0', TransactionLineTaxRate::displayPercent('0'));
    }

    public function test_display_percent_returns_dash_for_empty(): void
    {
        $this->assertSame('--', TransactionLineTaxRate::displayPercent(null));
        $this->assertSame('--', TransactionLineTaxRate::displayPercent(''));
    }

    public function test_normalize_for_storage_returns_string_percentage_unchanged_when_not_an_id(): void
    {
        $this->assertSame('15', TransactionLineTaxRate::normalizeForStorage(15));
        $this->assertSame('0', TransactionLineTaxRate::normalizeForStorage('0'));
    }

    public function test_normalize_for_storage_returns_null_for_empty(): void
    {
        $this->assertNull(TransactionLineTaxRate::normalizeForStorage(null));
        $this->assertNull(TransactionLineTaxRate::normalizeForStorage(''));
    }
}
