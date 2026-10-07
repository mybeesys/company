<?php

declare(strict_types=1);

namespace Tests\Unit;

use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\Sales\Services\InvoiceServiceFeeCalculator;
use PHPUnit\Framework\TestCase;

final class InvoiceServiceFeeCalculatorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function orderContext(): array
    {
        return [
            'lines' => [[
                'qty' => 1,
                'net' => 100.0,
                'vat' => 15.0,
                'gross' => 115.0,
                'tax_rate' => 15.0,
            ]],
            'subtotal_after_discount' => 100.0,
            'product_vat' => 15.0,
            'product_total' => 115.0,
        ];
    }

    public function test_percent_order_fee_uses_net_when_calculated_before_tax(): void
    {
        $computed = InvoiceServiceFeeCalculator::computeFee([
            'id' => 1,
            'name_ar' => 'عمولة',
            'amount' => 20,
            'service_fee_type' => EstablishmentServiceFee::TYPE_PERCENT,
            'application_type' => EstablishmentServiceFee::APPLY_ORDER,
            'calculation_method' => EstablishmentServiceFee::CALC_BEFORE_TAX,
            'taxable' => true,
            'fee_direction' => 'COLLECTED',
            'show_on_invoice' => true,
        ], $this->orderContext());

        $this->assertEqualsWithDelta(20.0, $computed['fee_amount'], 0.0001);
        $this->assertEqualsWithDelta(3.0, $computed['tax_amount'], 0.0001);
        $this->assertSame('0', $computed['calculation_method']);
    }

    public function test_percent_order_fee_uses_gross_when_calculated_after_tax(): void
    {
        $computed = InvoiceServiceFeeCalculator::computeFee([
            'id' => 1,
            'name_ar' => 'عمولة',
            'amount' => 20,
            'service_fee_type' => EstablishmentServiceFee::TYPE_PERCENT,
            'application_type' => EstablishmentServiceFee::APPLY_ORDER,
            'calculation_method' => EstablishmentServiceFee::CALC_AFTER_TAX,
            'taxable' => true,
            'fee_direction' => 'COLLECTED',
            'show_on_invoice' => true,
        ], $this->orderContext());

        $this->assertEqualsWithDelta(23.0, $computed['fee_amount'], 0.0001);
        $this->assertEqualsWithDelta(3.45, $computed['tax_amount'], 0.0001);
        $this->assertSame('1', $computed['calculation_method']);
    }

    public function test_paid_fee_does_not_increase_customer_total(): void
    {
        $computed = InvoiceServiceFeeCalculator::computeFee([
            'id' => 8,
            'amount' => 20,
            'service_fee_type' => EstablishmentServiceFee::TYPE_PERCENT,
            'application_type' => EstablishmentServiceFee::APPLY_ORDER,
            'calculation_method' => EstablishmentServiceFee::CALC_BEFORE_TAX,
            'taxable' => true,
            'fee_direction' => 'PAID',
            'show_on_invoice' => false,
        ], $this->orderContext());

        $this->assertFalse($computed['increases_customer_total']);
        $this->assertFalse($computed['show_on_invoice']);
        $this->assertEqualsWithDelta(20.0, $computed['fee_amount'], 0.0001);
    }
}
