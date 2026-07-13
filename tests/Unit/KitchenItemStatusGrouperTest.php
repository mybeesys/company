<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\General\Models\TransactionSellLine;
use Modules\Reservation\Models\OrderTableItems;
use Modules\Reservation\Support\KitchenItemStatusGrouper;
use Tests\TestCase;

class KitchenItemStatusGrouperTest extends TestCase
{
    public function test_table_line_group_includes_main_modifier_and_combo_children(): void
    {
        $lines = collect([
            $this->makeTableLine(415, null, 292, 0),
            $this->makeTableLine(416, 415, 262, 6.09),
            $this->makeTableLine(417, 415, 263, 1.74),
            $this->makeTableLine(418, null, 287, 0),
            $this->makeTableLine(419, 421, 274, 1),
            $this->makeTableLine(420, 421, 279, 2),
            $this->makeTableLine(421, null, 262, 0),
        ]);

        $fromMain = KitchenItemStatusGrouper::tableLineGroupIds($lines->firstWhere('id', 415), $lines);
        $fromCombo = KitchenItemStatusGrouper::tableLineGroupIds($lines->firstWhere('id', 416), $lines);
        $fromOtherMain = KitchenItemStatusGrouper::tableLineGroupIds($lines->firstWhere('id', 421), $lines);

        $this->assertEqualsCanonicalizing([415, 416, 417], $fromMain);
        $this->assertEqualsCanonicalizing([415, 416, 417], $fromCombo);
        $this->assertEqualsCanonicalizing([421, 419, 420], $fromOtherMain);
    }

    public function test_pos_line_group_matches_kitchen_payload_grouping(): void
    {
        $lines = collect([
            $this->makePosLine(10, 292, 'بيج بيك', 18, 20.7),
            $this->makePosLine(11, 300, 'جبنة إضافية', 2, 2.3),
            $this->makePosLine(12, 262, 'برجر دبل', 0, 0),
            $this->makePosLine(13, 263, 'بيبسي', 0, 0),
            $this->makePosLine(14, 287, 'شاورما عربي', 12, 13.8),
        ]);

        $fromMain = KitchenItemStatusGrouper::posLineGroupIds($lines, 10);
        $fromModifier = KitchenItemStatusGrouper::posLineGroupIds($lines, 11);
        $fromCombo = KitchenItemStatusGrouper::posLineGroupIds($lines, 12);
        $fromNextMain = KitchenItemStatusGrouper::posLineGroupIds($lines, 14);

        $this->assertSame([10, 11, 12, 13], $fromMain);
        $this->assertSame([10, 11, 12, 13], $fromModifier);
        $this->assertSame([10, 11, 12, 13], $fromCombo);
        $this->assertSame([14], $fromNextMain);
    }

    public function test_pos_line_group_keeps_two_cheap_products_separate(): void
    {
        $lines = collect([
            $this->makePosLine(1664, 263, 'بيبسي', 1.74, 2),
            $this->makePosLine(1665, 264, 'برتقال المراعي', 2.61, 3),
        ]);

        $this->assertSame([1664], KitchenItemStatusGrouper::posLineGroupIds($lines, 1664));
        $this->assertSame([1665], KitchenItemStatusGrouper::posLineGroupIds($lines, 1665));
    }

    private function makeTableLine(int $id, ?int $parentId, int $productId, float $price): OrderTableItems
    {
        $line = new OrderTableItems([
            'transaction_id' => 207,
            'product_id' => $productId,
            'parent_id' => $parentId,
            'qyt' => 1,
            'unit_price' => $price,
            'line_status' => 'inpreparation',
        ]);
        $line->id = $id;

        return $line;
    }

    private function makePosLine(
        int $id,
        int $productId,
        string $name,
        float $price,
        float $priceWithTax,
    ): TransactionSellLine {
        $line = new TransactionSellLine([
            'transaction_id' => 1108,
            'product_id' => $productId,
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
