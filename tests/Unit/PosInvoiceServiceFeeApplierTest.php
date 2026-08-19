<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Sales\Services\PosInvoiceServiceFeeApplier;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use PHPUnit\Framework\TestCase;

final class PosInvoiceServiceFeeApplierTest extends TestCase
{
    public function test_omitted_fee_ids_leave_sale_unchanged(): void
    {
        $request = Request::create('/', 'POST', []);

        $this->assertNull(PosInvoiceServiceFeeApplier::appliedIdsFromRequest($request));
    }

    public function test_reads_selected_fee_ids_from_either_payload_shape(): void
    {
        $byList = Request::create('/', 'POST', [
            'applied_service_fee_ids' => [3, 5, 3, 0],
        ]);
        $this->assertSame([3, 5], PosInvoiceServiceFeeApplier::appliedIdsFromRequest($byList));

        $byObjects = Request::create('/', 'POST', [
            'service_fees' => [
                ['id' => 9],
                ['service_fee_id' => 12],
            ],
        ]);
        $this->assertSame([9, 12], PosInvoiceServiceFeeApplier::appliedIdsFromRequest($byObjects));

        $empty = Request::create('/', 'POST', ['applied_service_fee_ids' => []]);
        $this->assertSame([], PosInvoiceServiceFeeApplier::appliedIdsFromRequest($empty));
    }

    public function test_collects_parent_and_modifier_lines_for_item_fees(): void
    {
        $products = [
            (object) [
                'product_id' => 10,
                'quantity' => 1,
                'price' => 10,
                'price_after_discount' => 10,
                'discount_amount' => 0,
                'total_before_vat' => 10,
                'tax_value' => 1.5,
                'order_item_modifiers' => [
                    [
                        'modifier_id' => 20,
                        'quantity' => 1,
                        'price' => 2,
                        'total_before_vat' => 2,
                        'tax_value' => 0.3,
                    ],
                ],
            ],
        ];

        $lines = PosInvoiceServiceFeeApplier::collectLines($products);

        $this->assertCount(2, $lines);
        $this->assertEqualsWithDelta(10.0, $lines[0]['net'], 0.0001);
        $this->assertEqualsWithDelta(1.5, $lines[0]['vat'], 0.0001);
        $this->assertEqualsWithDelta(2.0, $lines[1]['net'], 0.0001);
    }

    public function test_mapper_persists_selected_service_fee_without_changing_product_net(): void
    {
        $request = Request::create('/api/stor-sales-invoice', 'POST', [
            'invoice_type' => 'cash',
            'total_before_discount' => 100,
            'total_after_discount' => 100,
            'total_tax' => 15,
            'total_paid' => 125,
            'discount_value' => 0,
            'created_at' => '2026-08-19 12:00',
            'customer_id' => 1,
            'user_id' => 1,
            'establishment_id' => 6,
            'status' => 'final',
            'invoice_number' => 'INV-SF',
        ]);

        $transaction = PosSalesInvoiceMapper::mapTransactionAttributes($request, [
            'tax_amount' => 15,
            'final_total' => 125,
            'totalAfterDiscount' => 100,
            'total_before_tax' => 100,
            'service_fee_amount' => 10,
            'service_fee_tax' => 0,
            'service_fees_payload' => [
                ['id' => 3, 'name_ar' => 'خدمة', 'fee_amount' => 10, 'tax_amount' => 0],
            ],
        ]);

        $this->assertEqualsWithDelta(100.0, (float) $transaction['totalAfterDiscount'], 0.0001);
        $this->assertEqualsWithDelta(15.0, (float) $transaction['tax_amount'], 0.0001);
        $this->assertEqualsWithDelta(125.0, (float) $transaction['final_total'], 0.0001);
        $this->assertEqualsWithDelta(10.0, (float) $transaction['service_fee_amount'], 0.0001);
        $this->assertSame(3, $transaction['service_fees_payload'][0]['id']);
        $this->assertSame('sell', $transaction['type']);
        $this->assertSame('standard', $transaction['purpose']);
    }
}
