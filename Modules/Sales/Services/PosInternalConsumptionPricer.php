<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryCostingService;
use Modules\Sales\Support\TransactionPurpose;

/**
 * Rewrites a POS sell payload to inventory cost (same preview engine as web internal consumption).
 */
final class PosInternalConsumptionPricer
{
    public function __construct(
        private readonly InventoryCostingService $costing
    ) {}

    public static function isRequested(Request $request): bool
    {
        return TransactionPurpose::isRequested(
            $request->input('purpose'),
            $request->input('internal_consumption_type_id')
        );
    }

    public static function requestHasDiscount(Request $request): bool
    {
        if ((float) ($request->input('discount_value') ?? 0) > 0) {
            return true;
        }

        if (trim((string) $request->input('coupon_code', '')) !== '') {
            return true;
        }

        $items = $request->input('items', []);
        if (! is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array) $item;
            if (self::rowHasDiscount($item)) {
                return true;
            }

            foreach ($item['order_item_modifiers'] ?? [] as $modifier) {
                if (self::rowHasDiscount(is_array($modifier) ? $modifier : (array) $modifier)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function rowHasDiscount(array $row): bool
    {
        return (float) ($row['discount_amount'] ?? 0) > 0;
    }

    public function applyToRequest(Request $request, int $establishmentId): void
    {
        $items = $request->input('items', []);
        if (! is_array($items)) {
            $items = [];
        }

        $previewLines = $this->collectPreviewLines($items);
        $costs = $this->costing->previewOutboundCosts($establishmentId, $previewLines);
        $rewritten = $this->applyCostsToItems($items, $costs);
        $total = $this->sumAllCosts($costs);

        $request->merge([
            'items' => $rewritten,
            'purpose' => TransactionPurpose::INTERNAL_CONSUMPTION,
            'discount_value' => 0,
            'discount_type' => null,
            'coupon_code' => '',
            'total_tax' => 0,
            'total_before_discount' => $total,
            'total_after_discount' => $total,
            'total_paid' => $total,
            'payments' => [],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{product_id:int, qty:float, unit_id:int|null}>
     */
    public function collectPreviewLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array) $item;
            $lines[] = $this->previewLine(
                (int) ($item['product_id'] ?? 0),
                (float) ($item['quantity'] ?? 0),
                $item['unit_id'] ?? null
            );

            foreach ($item['order_item_modifiers'] ?? [] as $modifier) {
                $modifier = is_array($modifier) ? $modifier : (array) $modifier;
                $lines[] = $this->previewLine(
                    (int) ($modifier['modifier_id'] ?? $modifier['product_id'] ?? 0),
                    (float) ($modifier['quantity'] ?? 0),
                    $modifier['unit_id'] ?? null
                );
            }

            foreach ($item['order_item_combos'] ?? [] as $combo) {
                $combo = is_array($combo) ? $combo : (array) $combo;
                $comboItem = PosSalesInvoiceMapper::resolveComboOption((object) $combo);
                $lines[] = $this->previewLine(
                    $comboItem ? (int) $comboItem->item_id : (int) ($combo['option_id'] ?? 0),
                    (float) ($combo['quantity'] ?? 1),
                    $combo['unit_id'] ?? null
                );
            }
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<array{product_id:int, qty:float, qty_base:float, unit_cost:float, total_cost:float}>  $costs
     * @return list<array<string, mixed>>
     */
    public function applyCostsToItems(array $items, array $costs): array
    {
        $index = 0;
        $rewritten = [];

        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array) $item;
            $item = $this->priceRowAtCost($item, $costs[$index] ?? null);
            $index++;

            $modifiers = [];
            foreach ($item['order_item_modifiers'] ?? [] as $modifier) {
                $modifier = is_array($modifier) ? $modifier : (array) $modifier;
                $modifiers[] = $this->priceRowAtCost($modifier, $costs[$index] ?? null);
                $index++;
            }
            $item['order_item_modifiers'] = $modifiers;

            $combos = [];
            foreach ($item['order_item_combos'] ?? [] as $combo) {
                $combo = is_array($combo) ? $combo : (array) $combo;
                $combos[] = $this->priceRowAtCost($combo, $costs[$index] ?? null);
                $index++;
            }
            $item['order_item_combos'] = $combos;

            $rewritten[] = $item;
        }

        return $rewritten;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{product_id?:int, qty?:float, unit_cost?:float, total_cost?:float}|null  $cost
     * @return array<string, mixed>
     */
    private function priceRowAtCost(array $row, ?array $cost): array
    {
        $unitCost = round((float) ($cost['unit_cost'] ?? 0), 4);
        $totalCost = round((float) ($cost['total_cost'] ?? 0), 4);

        $row['price'] = $unitCost;
        $row['price_after_discount'] = $unitCost;
        $row['price_with_tax'] = $unitCost;
        $row['price_with_tax_after_discount'] = $unitCost;
        $row['discount_amount'] = 0;
        $row['discount_type'] = null;
        $row['tax_id'] = null;
        $row['tax_value'] = 0;
        $row['total_before_vat'] = $totalCost;

        return $row;
    }

    /**
     * @param  list<array{total_cost?:float}>  $costs
     */
    public function sumAllCosts(array $costs): float
    {
        $total = 0.0;
        foreach ($costs as $cost) {
            $total += (float) ($cost['total_cost'] ?? 0);
        }

        return round($total, 4);
    }

    /**
     * @return array{product_id:int, qty:float, unit_id:int|null}
     */
    private function previewLine(int $productId, float $qty, mixed $unitId): array
    {
        return [
            'product_id' => $productId,
            'qty' => $qty,
            'unit_id' => ! empty($unitId) ? (int) $unitId : null,
        ];
    }
}
