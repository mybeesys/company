<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryCostingService;
use Modules\Sales\Services\PosInternalConsumptionPricer;
use PHPUnit\Framework\TestCase;

final class PosInternalConsumptionPricerTest extends TestCase
{
    public function test_detects_purpose_or_type_id(): void
    {
        $byPurpose = Request::create('/', 'POST', ['purpose' => 'internal_consumption']);
        $byType = Request::create('/', 'POST', ['internal_consumption_type_id' => 7]);
        $standard = Request::create('/', 'POST', ['purpose' => 'standard']);

        $this->assertTrue(PosInternalConsumptionPricer::isRequested($byPurpose));
        $this->assertTrue(PosInternalConsumptionPricer::isRequested($byType));
        $this->assertFalse(PosInternalConsumptionPricer::isRequested($standard));
    }

    public function test_detects_header_and_line_discounts(): void
    {
        $this->assertTrue(PosInternalConsumptionPricer::requestHasDiscount(
            Request::create('/', 'POST', ['discount_value' => 2])
        ));
        $this->assertTrue(PosInternalConsumptionPricer::requestHasDiscount(
            Request::create('/', 'POST', ['coupon_code' => 'SAVE'])
        ));
        $this->assertTrue(PosInternalConsumptionPricer::requestHasDiscount(
            Request::create('/', 'POST', [
                'items' => [['product_id' => 1, 'quantity' => 1, 'discount_amount' => 1.5]],
            ])
        ));
        $this->assertFalse(PosInternalConsumptionPricer::requestHasDiscount(
            Request::create('/', 'POST', [
                'discount_value' => 0,
                'items' => [['product_id' => 1, 'quantity' => 1, 'discount_amount' => 0]],
            ])
        ));
    }

    public function test_apply_rewrites_prices_to_inventory_cost_and_clears_sale_fields(): void
    {
        $costing = $this->createMock(InventoryCostingService::class);
        $costing->method('previewOutboundCosts')->willReturn([
            ['product_id' => 10, 'qty' => 2.0, 'qty_base' => 2.0, 'unit_cost' => 4.5, 'total_cost' => 9.0],
            ['product_id' => 20, 'qty' => 1.0, 'qty_base' => 1.0, 'unit_cost' => 1.25, 'total_cost' => 1.25],
        ]);

        $pricer = new PosInternalConsumptionPricer($costing);
        $request = Request::create('/', 'POST', [
            'purpose' => 'standard',
            'discount_value' => 0,
            'total_tax' => 3.7,
            'total_paid' => 40,
            'payments' => [['method_id' => 12, 'amount' => 40]],
            'items' => [
                [
                    'product_id' => 10,
                    'quantity' => 2,
                    'price' => 20,
                    'price_after_discount' => 20,
                    'tax_value' => 3,
                    'discount_amount' => 0,
                    'order_item_modifiers' => [
                        [
                            'modifier_id' => 20,
                            'quantity' => 1,
                            'price' => 5,
                            'tax_value' => 0.75,
                        ],
                    ],
                ],
            ],
        ]);

        $pricer->applyToRequest($request, 1);

        $this->assertSame('internal_consumption', $request->input('purpose'));
        $this->assertSame(0, $request->input('discount_value'));
        $this->assertSame(0, $request->input('total_tax'));
        $this->assertSame([], $request->input('payments'));
        $this->assertEqualsWithDelta(10.25, (float) $request->input('total_paid'), 0.0001);
        $this->assertEqualsWithDelta(4.5, (float) $request->input('items.0.price'), 0.0001);
        $this->assertEqualsWithDelta(4.5, (float) $request->input('items.0.price_after_discount'), 0.0001);
        $this->assertEqualsWithDelta(0.0, (float) $request->input('items.0.tax_value'), 0.0001);
        $this->assertEqualsWithDelta(1.25, (float) $request->input('items.0.order_item_modifiers.0.price'), 0.0001);
    }
}
