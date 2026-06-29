<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Tests\TestCase;

final class PosSalesInvoiceMapperTest extends TestCase
{
    public function test_maps_sample_pos_payload_totals_and_invoice_type(): void
    {
        $request = Request::create('/api/stor-sales-invoice', 'POST', [
            'id' => 1782737465532,
            'invoice_type' => 'cash',
            'payment_status' => 'paid',
            'total_before_discount' => 11.3,
            'total_after_discount' => 13.0,
            'total_tax' => 1.7,
            'total_paid' => 13.0,
            'discount_value' => 0.0,
            'created_at' => '2026-06-29 03:51',
            'customer_id' => 6,
            'user_id' => 1,
            'establishment_id' => 6,
            'status' => 'approved',
            'invoice_number' => 'INV0001',
            'shift_id' => 1782737439172,
            'order_type' => 'محلي',
        ]);

        $this->assertSame('cash', PosSalesInvoiceMapper::resolveInvoiceType($request));
        $this->assertEqualsWithDelta(11.3, PosSalesInvoiceMapper::resolveTaxableAfterDiscount($request), 0.0001);

        $transaction = PosSalesInvoiceMapper::mapTransactionAttributes($request);

        $this->assertSame('cash', $transaction['invoice_type']);
        $this->assertSame('11.3', (string) $transaction['total_before_tax']);
        $this->assertEqualsWithDelta(11.3, (float) $transaction['totalAfterDiscount'], 0.0001);
        $this->assertEqualsWithDelta(1.7, (float) $transaction['tax_amount'], 0.0001);
        $this->assertEqualsWithDelta(13.0, (float) $transaction['final_total'], 0.0001);
        $this->assertSame('محلي', $transaction['order_type']);
        $this->assertArrayNotHasKey('total_after_discount', $transaction);
    }

    public function test_maps_sell_line_when_price_after_discount_is_zero(): void
    {
        $item = (object) [
            'product_id' => 290,
            'quantity' => 1.0,
            'price' => 11.3,
            'price_after_discount' => 0.0,
            'price_with_tax' => 13.0,
            'price_with_tax_after_discount' => 0.0,
            'tax_id' => 45,
            'tax_value' => 1.7,
            'discount_type' => 'fixd',
            'discount_amount' => '0',
            'unit_id' => 305,
        ];

        $line = PosSalesInvoiceMapper::mapSellLineAttributes($item);

        $this->assertEqualsWithDelta(11.3, (float) $line['unit_price_before_discount'], 0.0001);
        $this->assertEqualsWithDelta(11.3, (float) $line['unit_price'], 0.0001);
        $this->assertEqualsWithDelta(11.3, (float) $line['total_before_vat'], 0.0001);
        $this->assertEqualsWithDelta(13.0, (float) $line['unit_price_inc_tax'], 0.0001);
        $this->assertSame('45', $line['tax_id']);
    }
}
