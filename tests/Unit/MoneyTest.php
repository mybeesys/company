<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\General\Support\Money;
use Modules\Sales\Services\InvoiceMoneyReconciler;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_parse_treats_arabic_comma_as_decimal(): void
    {
        $this->assertSame(33.91, Money::parse('33,91'));
    }

    public function test_parse_strips_thousands_comma_without_losing_halalas(): void
    {
        $this->assertSame(5086.50, Money::round('5,086.50'));
        $this->assertSame(38996.50, Money::round('38,996.50'));
    }

    public function test_php_float_cast_would_drop_thousands_but_parse_does_not(): void
    {
        $this->assertSame(5.0, (float) '5,086.50');
        $this->assertSame(5086.50, Money::parse('5,086.50'));
    }

    public function test_european_dot_thousands_comma_decimal(): void
    {
        $this->assertSame(1234.56, Money::parse('1.234,56'));
    }

    public function test_arabic_indic_digits(): void
    {
        $this->assertSame(33.91, Money::parse('٣٣٫٩١'));
    }

    public function test_exclusive_vat_rounds_unit_price_including_tax_then_multiplies_qty(): void
    {
        $three = InvoiceMoneyReconciler::reconcile(
            [[
                'qty' => '3',
                'unit_price' => '33.91',
                'discount' => '0',
                'discount_type' => 'fixed',
                'tax_vat' => '15',
            ]],
            0,
            'fixed'
        );

        $this->assertSame(101.73, $three['total_before_vat']);
        $this->assertSame(15.27, $three['total_vat']);
        $this->assertSame(117.00, $three['total_after_vat']);
        $this->assertSame(117.00, $three['products'][0]['total_after_vat']);
    }

    public function test_qty_times_unit_price_and_15_percent_vat_is_exact_halalas(): void
    {
        $result = InvoiceMoneyReconciler::reconcile(
            [[
                'qty' => '1000',
                'unit_price' => '33.91',
                'discount' => '0',
                'discount_type' => 'fixed',
                'tax_vat' => '15',
            ]],
            0,
            'fixed'
        );

        $this->assertSame(33910.00, $result['total_before_vat']);
        $this->assertSame(5090.00, $result['total_vat']);
        $this->assertSame(39000.00, $result['total_after_vat']);
        $this->assertSame(33910.00, $result['products'][0]['total_before_vat']);
        $this->assertSame(5090.00, $result['products'][0]['vat_value']);
        $this->assertSame(39000.00, $result['products'][0]['total_after_vat']);
    }

    public function test_remaining_does_not_invent_a_halala(): void
    {
        $this->assertSame(0.0, Money::remaining(116.99, 116.99));
        $this->assertSame(0.0, Money::remaining(116.99, '116,99'));
        $this->assertSame(0.01, Money::remaining(117.00, 116.99));
    }
}
