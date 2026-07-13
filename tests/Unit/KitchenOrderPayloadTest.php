<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Reservation\Models\OrderTableItems;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Support\KitchenOrderPayload;
use Tests\TestCase;

class KitchenOrderPayloadTest extends TestCase
{
    public function test_table_order_nests_combo_and_modifier_under_parent(): void
    {
        $main = $this->makeTableLine(1, null, 292, 'بيج بيك', 18, 20.7);
        $modifier = $this->makeTableLine(2, 1, 300, 'جبنة إضافية', 2, 2.3, modifierId: 300);
        $combo = $this->makeTableLine(3, 1, 262, 'برجر دبل', 6.09, 0, comboId: '262');

        $items = KitchenOrderPayload::formatTableOrderItems(collect([$main, $modifier, $combo]));

        $this->assertCount(1, $items);
        $this->assertSame(292, $items[0]['product_id']);
        $this->assertCount(1, $items[0]['order_item_modifiers']);
        $this->assertSame('جبنة إضافية', $items[0]['order_item_modifiers'][0]['modifier_name']);
        $this->assertCount(1, $items[0]['order_item_combos']);
        $this->assertSame('262', $items[0]['order_item_combos'][0]['option_id']);
        $this->assertSame('برجر دبل', $items[0]['order_item_combos'][0]['option_name']);
    }

    public function test_pos_sell_lines_group_main_modifiers_and_zero_price_combos(): void
    {
        $main = $this->makePosLine(10, 292, 'بيج بيك', 18, 20.7);
        $modifier = $this->makePosLine(11, 300, 'جبنة إضافية', 2, 2.3);
        $combo = $this->makePosLine(12, 262, 'برجر دبل', 0, 0);
        $comboDrink = $this->makePosLine(13, 263, 'بيبسي', 0, 0);

        $items = KitchenOrderPayload::formatPosSellLines(collect([$main, $modifier, $combo, $comboDrink]));

        $this->assertCount(1, $items);
        $this->assertSame(292, $items[0]['product_id']);
        $this->assertCount(1, $items[0]['order_item_modifiers']);
        $this->assertCount(2, $items[0]['order_item_combos']);
        $this->assertSame('برجر دبل', $items[0]['order_item_combos'][0]['option_name']);
        $this->assertSame('بيبسي', $items[0]['order_item_combos'][1]['option_name']);
    }

    public function test_two_cheap_pos_products_stay_separate_mains(): void
    {
        $pepsi = $this->makePosLine(1664, 263, 'بيبسي', 1.74, 2);
        $orange = $this->makePosLine(1665, 264, 'برتقال المراعي', 2.61, 3);

        $items = KitchenOrderPayload::formatPosSellLines(collect([$pepsi, $orange]));

        $this->assertCount(2, $items);
        $this->assertSame(263, $items[0]['product_id']);
        $this->assertSame(264, $items[1]['product_id']);
        $this->assertSame([], $items[0]['order_item_modifiers']);
        $this->assertSame([], $items[1]['order_item_modifiers']);
    }

    public function test_legacy_pos_lines_keep_paid_combo_upgrade_out_of_modifiers(): void
    {
        $burgerMain = $this->makePosLine(1645, 262, 'برجر دبل', 6.09, 21);
        $shawarmaMain = $this->makePosLine(1646, 287, 'شاورما عربي', 100, 230);
        $cheese = $this->makePosLine(1647, 274, 'جبنة شرائح', 1, 2);
        $lotus = $this->makePosLine(1648, 279, 'كريمة اللوتس', 2, 4);
        $bigBakeCombo = $this->makePosLine(1649, 292, 'بيج بيك', 18, 20.7);
        $comboBurger = $this->makePosLine(1650, 262, 'برجر دبل', 0, 0);
        $comboPepsi = $this->makePosLine(1651, 263, 'بيبسي', 0, 0);

        $items = KitchenOrderPayload::formatPosSellLines(collect([
            $burgerMain,
            $shawarmaMain,
            $cheese,
            $lotus,
            $bigBakeCombo,
            $comboBurger,
            $comboPepsi,
        ]));

        $this->assertCount(2, $items);
        $shawarma = $items[1];
        $this->assertSame(287, $shawarma['product_id']);
        $this->assertCount(2, $shawarma['order_item_modifiers']);
        $this->assertSame('جبنة شرائح', $shawarma['order_item_modifiers'][0]['modifier_name']);
        $this->assertCount(3, $shawarma['order_item_combos']);
        $this->assertSame('بيج بيك', $shawarma['order_item_combos'][0]['option_name']);
        $this->assertSame('برجر دبل', $shawarma['order_item_combos'][1]['option_name']);
    }

    public function test_pos_sell_lines_with_parent_id_use_explicit_combo_and_modifier_flags(): void
    {
        $main = $this->makePosLine(20, 287, 'شاورما عربي', 100, 230);
        $modifier = $this->makePosLine(21, 274, 'جبنة شرائح', 1, 2, parentId: 20, modifierId: 274);
        $combo = $this->makePosLine(22, 292, 'بيج بيك', 18, 20.7, parentId: 20, comboId: '292');

        $items = KitchenOrderPayload::formatPosSellLines(collect([$main, $modifier, $combo]));

        $this->assertCount(1, $items);
        $this->assertCount(1, $items[0]['order_item_modifiers']);
        $this->assertCount(1, $items[0]['order_item_combos']);
        $this->assertSame('بيج بيك', $items[0]['order_item_combos'][0]['option_name']);
    }

    public function test_table_order_item_note_is_included_in_kitchen_payload(): void
    {
        $main = $this->makeTableLine(1, null, 292, 'بيج بيك', 18, 20.7, note: 'حار جداً');

        $items = KitchenOrderPayload::formatTableOrderItems(collect([$main]));

        $this->assertSame('حار جداً', $items[0]['note']);
    }

    public function test_kitchen_key_distinguishes_table_and_pos_with_same_numeric_id(): void
    {
        $tableOrder = new \Modules\Reservation\Models\TableOrders();
        $tableOrder->id = 42;
        $posOrder = new \Modules\General\Models\Transaction(['type' => 'sell']);
        $posOrder->id = 42;

        $this->assertSame('local', KitchenOrderPayload::resolveSource($tableOrder));
        $this->assertSame('pos', KitchenOrderPayload::resolveSource($posOrder));
        $this->assertSame('local:42', KitchenOrderPayload::kitchenKey($tableOrder));
        $this->assertSame('pos:42', KitchenOrderPayload::kitchenKey($posOrder));
    }

    public function test_pos_transaction_linked_to_table_order_is_excluded_from_kitchen(): void
    {
        $tableOrder = new TableOrders([
            'table_id' => 15,
            'establishment_id' => 3,
            'order_status' => 'inpreparation',
        ]);
        $tableOrder->id = 207;

        $linkedPos = new Transaction([
            'type' => 'sell',
            'table_order_id' => '207',
            'order_status' => 'inpreparation',
        ]);

        $takeawayPos = new Transaction([
            'type' => 'sell',
            'order_status' => 'inpreparation',
        ]);

        $active = collect([$tableOrder]);

        $this->assertTrue(KitchenOrderPayload::shouldExcludePosTransactionFromKitchen($linkedPos, $active));
        $this->assertFalse(KitchenOrderPayload::shouldExcludePosTransactionFromKitchen($takeawayPos, $active));
    }

    public function test_pos_transaction_with_table_id_is_excluded_when_table_has_active_order(): void
    {
        $tableOrder = new TableOrders([
            'table_id' => 15,
            'order_status' => 'inpreparation',
        ]);
        $tableOrder->id = 88;

        $linkedPos = new Transaction([
            'type' => 'sell',
            'table_id' => '15',
            'order_status' => 'inpreparation',
        ]);

        $this->assertTrue(
            KitchenOrderPayload::shouldExcludePosTransactionFromKitchen($linkedPos, collect([$tableOrder]))
        );
    }

    public function test_request_with_table_id_is_table_sale(): void
    {
        $this->assertTrue(KitchenOrderPayload::requestRepresentsTableSale(
            Request::create('/api/stor-sales-invoice', 'POST', [
                'table_id' => 15,
                'table_order_id' => 99,
            ])
        ));
        $this->assertFalse(KitchenOrderPayload::requestRepresentsTableSale(
            Request::create('/api/stor-sales-invoice', 'POST', [])
        ));
    }

    private function makeTableLine(
        int $id,
        ?int $parentId,
        int $productId,
        string $name,
        float $price,
        float $priceWithTax,
        ?int $modifierId = null,
        ?string $comboId = null,
        ?string $note = null,
    ): OrderTableItems {
        $line = new OrderTableItems([
            'transaction_id' => 99,
            'product_id' => $productId,
            'parent_id' => $parentId,
            'modifier_id' => $modifierId,
            'combo_id' => $comboId,
            'qyt' => 1,
            'unit_price' => $price,
            'unit_price_inc_tax' => $priceWithTax,
            'line_status' => 'inpreparation',
            'note' => $note,
        ]);
        $line->id = $id;
        $line->setRelation('product', (object) [
            'name_ar' => $name,
            'category_id' => 9,
        ]);

        return $line;
    }

    private function makePosLine(
        int $id,
        int $productId,
        string $name,
        float $price,
        float $priceWithTax,
        ?int $parentId = null,
        ?int $modifierId = null,
        ?string $comboId = null,
    ): TransactionSellLine {
        $line = new TransactionSellLine([
            'transaction_id' => 1108,
            'product_id' => $productId,
            'parent_id' => $parentId,
            'modifier_id' => $modifierId,
            'combo_id' => $comboId,
            'qyt' => 1,
            'unit_price' => $price,
            'unit_price_before_discount' => $price,
            'unit_price_inc_tax' => $priceWithTax,
            'line_status' => 'inpreparation',
        ]);
        $line->id = $id;
        $line->setRelation('product', (object) [
            'name_ar' => $name,
            'category_id' => 9,
        ]);

        return $line;
    }
}
