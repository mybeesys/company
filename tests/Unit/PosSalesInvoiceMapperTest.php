<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Product\Models\ProductComboItem;
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
        $this->assertEqualsWithDelta(1.7, (float) $line['tax_value'], 0.0001);
        $this->assertSame('45', $line['tax_id']);
    }

    public function test_maps_sell_line_note_when_present(): void
    {
        $item = (object) [
            'product_id' => 290,
            'quantity' => 1.0,
            'price' => 11.3,
            'price_after_discount' => 11.3,
            'tax_id' => 45,
            'tax_value' => 1.7,
            'discount_amount' => '0',
            'note' => 'بدون بصل',
        ];

        $line = PosSalesInvoiceMapper::mapSellLineAttributes($item);

        $this->assertSame('بدون بصل', $line['note']);
    }

    public function test_maps_sell_line_note_as_null_when_empty(): void
    {
        $item = (object) [
            'product_id' => 290,
            'quantity' => 1.0,
            'price' => 11.3,
            'price_after_discount' => 11.3,
            'discount_amount' => '0',
            'note' => '   ',
        ];

        $line = PosSalesInvoiceMapper::mapSellLineAttributes($item);

        $this->assertNull($line['note']);
    }

    public function test_maps_sample_pos_return_payload_when_header_tax_is_zero(): void
    {
        $request = Request::create('/api/stor-sell-return', 'POST', [
            'id' => 1782884664913,
            'invoice_type' => 'cash',
            'payment_status' => 'paid',
            'total_before_discount' => 40.0,
            'total_after_discount' => 40.0,
            'total_tax' => 0.0,
            'total_paid' => 40.0,
            'discount_value' => 0.0,
            'created_at' => '2026-07-01 08:44',
            'customer_id' => 11,
            'user_id' => 1,
            'establishment_id' => 6,
            'status' => 'approved',
            'invoice_number' => 'RET0001',
            'shift_id' => 1782883440805,
            'order_type' => 'سفري',
            'items' => [
                [
                    'product_id' => 283,
                    'quantity' => 2.0,
                    'price' => 17.39,
                    'price_with_tax' => 20.0,
                    'price_after_discount' => 0.0,
                    'price_with_tax_after_discount' => 0.0,
                    'tax_id' => 45,
                    'tax_value' => 2.61,
                    'discount_type' => 'fixd',
                    'discount_amount' => '0',
                    'unit_id' => 297,
                ],
            ],
        ]);

        $this->assertSame('cash', PosSalesInvoiceMapper::resolveInvoiceType($request));
        $this->assertEqualsWithDelta(5.22, PosSalesInvoiceMapper::resolveTaxAmount($request), 0.0001);
        $this->assertEqualsWithDelta(34.78, PosSalesInvoiceMapper::resolveTaxableAfterDiscount($request), 0.0001);

        $transaction = PosSalesInvoiceMapper::mapReturnTransactionAttributes($request, [
            'parent_id' => 99,
            'establishment_id' => 6,
        ]);

        $this->assertSame('sell-return', $transaction['type']);
        $this->assertSame(99, $transaction['parent_id']);
        $this->assertEqualsWithDelta(34.78, (float) $transaction['total_before_tax'], 0.0001);
        $this->assertEqualsWithDelta(34.78, (float) $transaction['totalAfterDiscount'], 0.0001);
        $this->assertEqualsWithDelta(5.22, (float) $transaction['tax_amount'], 0.0001);
        $this->assertEqualsWithDelta(40.0, (float) $transaction['final_total'], 0.0001);
        $this->assertArrayNotHasKey('total_after_discount', $transaction);

        $line = PosSalesInvoiceMapper::mapSellLineAttributes((object) $request->input('items')[0]);
        $this->assertEqualsWithDelta(34.78, (float) $line['total_before_vat'], 0.0001);
        $this->assertEqualsWithDelta(5.22, (float) $line['tax_value'], 0.0001);
        $this->assertEqualsWithDelta(40.0, (float) $line['unit_price_inc_tax'], 0.0001);
    }

    public function test_maps_combo_line_from_option_id_with_zero_invoice_price(): void
    {
        $comboItem = new ProductComboItem([
            'item_id' => 287,
            'combo_id' => 32,
            'price' => 100,
        ]);
        $comboItem->id = 56;

        $combo = (object) [
            'combo_group_id' => 32,
            'option_id' => 287,
            'option_name' => 'شاورما عربي',
            'price' => 100.0,
            'quantity' => 1,
        ];

        $line = PosSalesInvoiceMapper::mapComboLineAttributes($combo, $comboItem);

        $this->assertSame(287, $line['product_id']);
        $this->assertSame(0.0, (float) $line['unit_price_before_discount']);
        $this->assertSame(0.0, (float) $line['unit_price_inc_tax']);
        $this->assertSame('1', $line['is_show']);
    }
}
