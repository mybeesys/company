<?php

namespace Modules\Reservation\Support;

use Illuminate\Support\Collection;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\TypesOfService;
use Modules\Reservation\Models\OrderTableItems;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\TableOrders;

class KitchenOrderPayload
{
    public static function resolveSource($order): string
    {
        return $order instanceof TableOrders ? 'local' : 'pos';
    }

    public static function kitchenKey($order): string
    {
        return self::kitchenKeyFromParts(self::resolveSource($order), (int) $order->id);
    }

    public static function kitchenKeyFromParts(string $source, int $orderId): string
    {
        return $source.':'.$orderId;
    }

    /**
     * نفس منطق GET /api/kitchen-orders
     *
     * @param  array<int>  $categoryIds
     * @return array<int, array<string, mixed>>
     */
    public static function ordersForEstablishment(int $establishmentId, array $categoryIds = []): array
    {
        $tableOrders = TableOrders::where('establishment_id', $establishmentId)
            ->where('order_status', 'inpreparation')
            ->when(! empty($categoryIds), function ($query) use ($categoryIds) {
                return $query->whereHas('sell_lines.product', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                });
            })
            ->with(['sell_lines.product', 'sell_lines.productCombo', 'createdBy'])
            ->get();

        $posTransactions = Transaction::where('establishment_id', $establishmentId)
            ->where('type', 'sell')
            ->where('order_status', 'inpreparation')
            ->when(! empty($categoryIds), function ($query) use ($categoryIds) {
                return $query->whereHas('sell_lines.product', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                });
            })
            ->with(['sell_lines.product', 'createdBy', 'client'])
            ->get();

        return $tableOrders->concat($posTransactions)
            ->map(fn ($order) => self::formatOrder($order, $categoryIds))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  TableOrders|Transaction  $order
     * @param  array<int>  $categoryIds
     */
    public static function formatOrder($order, array $categoryIds = []): ?array
    {
        $reservation = null;
        if (isset($order->table_id)) {
            $reservation = Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->first();
        }

        $allLines = $order->sell_lines ?? collect();
        $items = $order instanceof TableOrders
            ? self::formatTableOrderItems($allLines, $categoryIds)
            : self::formatPosSellLines($allLines, $categoryIds);

        if (! empty($categoryIds) && $items === []) {
            return null;
        }

        $serviceName = 'سفري';
        if ($order instanceof TableOrders) {
            $service = TypesOfService::find($order->order_type);
            $serviceName = $service->name_ar ?? 'محلي';
        }

        return [
            'id' => $order->id,
            'source' => self::resolveSource($order),
            'kitchen_key' => self::kitchenKey($order),
            'table_id' => $order->table_id ?? null,
            'created_by' => $order->created_by,
            'order_type' => $serviceName,
            'order_status' => $order->order_status,
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
            'updated_at' => $order->updated_at?->toIso8601String(),
            'customer_name' => $reservation->customer_name ?? ($order->client?->name ?? 'Guest'),
            'customer_phone' => $reservation->customer_phone ?? ($order->client?->mobile ?? ''),
            'guests_count' => $reservation->guests_count ?? 0,
            'discount_type' => $order->discount_type,
            'discount_value' => (float) $order->discount_amount,
            'total_before_discount' => (float) $order->total_before_tax,
            'total_after_discount' => (float) $order->total_after_discount,
            'total_tax' => (float) $order->tax_amount,
            'total_paid' => (float) $order->final_total,
            'note' => $order->description,
            'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, OrderTableItems>  $allLines
     * @param  array<int>  $categoryIds
     * @return array<int, array<string, mixed>>
     */
    public static function formatTableOrderItems(Collection $allLines, array $categoryIds = []): array
    {
        $parentItems = $allLines->filter(fn ($line) => $line->parent_id === null || $line->parent_id === '');

        return $parentItems
            ->map(function ($mainItem) use ($allLines, $categoryIds) {
                $subItems = $allLines->filter(
                    fn ($line) => (string) $line->parent_id === (string) $mainItem->id
                );

                $modifiers = $subItems
                    ->filter(fn ($line) => $line->modifier_id !== null && $line->modifier_id !== '')
                    ->values();
                $combos = $subItems
                    ->filter(fn ($line) => $line->combo_id !== null && $line->combo_id !== '')
                    ->values();

                if (! self::lineGroupMatchesCategories($mainItem, $modifiers, $combos, $categoryIds)) {
                    return null;
                }

                return self::mapMainKitchenItem(
                    $mainItem,
                    $modifiers,
                    $combos,
                    fn ($combo) => $combo->product->name_ar ?? $combo->productCombo->name_ar ?? ''
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TransactionSellLine>  $allLines
     * @param  array<int>  $categoryIds
     * @return array<int, array<string, mixed>>
     */
    public static function formatPosSellLines(Collection $allLines, array $categoryIds = []): array
    {
        $items = [];
        $currentMain = null;
        /** @var array<int, TransactionSellLine> $currentModifiers */
        $currentModifiers = [];
        /** @var array<int, TransactionSellLine> $currentCombos */
        $currentCombos = [];

        $flush = function () use (&$items, &$currentMain, &$currentModifiers, &$currentCombos, $categoryIds) {
            if (! $currentMain) {
                return;
            }

            $modifierCollection = collect($currentModifiers);
            $comboCollection = collect($currentCombos);

            if (self::lineGroupMatchesCategories($currentMain, $modifierCollection, $comboCollection, $categoryIds)) {
                $items[] = self::mapMainKitchenItem(
                    $currentMain,
                    $modifierCollection,
                    $comboCollection,
                    fn ($combo) => $combo->product->name_ar ?? ''
                );
            }

            $currentMain = null;
            $currentModifiers = [];
            $currentCombos = [];
        };

        foreach ($allLines->sortBy('id')->values() as $line) {
            if (self::isPosComboComponentLine($line)) {
                if ($currentMain) {
                    $currentCombos[] = $line;
                }

                continue;
            }

            if ($currentMain === null) {
                $currentMain = $line;

                continue;
            }

            if ($currentCombos !== []) {
                $flush();
                $currentMain = $line;

                continue;
            }

            if ($currentModifiers === [] && self::looksLikePosMainProductLine($line, $currentMain)) {
                $flush();
                $currentMain = $line;

                continue;
            }

            $currentModifiers[] = $line;
        }

        $flush();

        return $items;
    }

    public static function appearsInKitchen(?string $orderStatus): bool
    {
        return $orderStatus === 'inpreparation';
    }

    public static function establishmentIdFromOrder($order): ?int
    {
        return $order->establishment_id ?? null;
    }

    /**
     * @param  Collection<int, OrderTableItems|TransactionSellLine>  $modifiers
     * @param  Collection<int, OrderTableItems|TransactionSellLine>  $combos
     * @param  array<int>  $categoryIds
     */
    private static function lineGroupMatchesCategories(
        $mainItem,
        Collection $modifiers,
        Collection $combos,
        array $categoryIds
    ): bool {
        if ($categoryIds === []) {
            return true;
        }

        $allowed = array_map('intval', $categoryIds);
        $lines = collect([$mainItem])->merge($modifiers)->merge($combos);

        foreach ($lines as $line) {
            $categoryId = (int) ($line->product->category_id ?? 0);
            if ($categoryId > 0 && in_array($categoryId, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, OrderTableItems|TransactionSellLine>  $modifiers
     * @param  Collection<int, OrderTableItems|TransactionSellLine>  $combos
     */
    private static function mapMainKitchenItem(
        $mainItem,
        Collection $modifiers,
        Collection $combos,
        callable $comboNameResolver
    ): array {
        return [
            'id' => $mainItem->id,
            'order_id' => $mainItem->transaction_id,
            'product_id' => $mainItem->product_id,
            'product_name' => $mainItem->product->name_ar ?? '',
            'category_id' => $mainItem->product->category_id ?? null,
            'quantity' => (float) $mainItem->qyt,
            'price' => (float) $mainItem->unit_price,
            'price_with_tax' => (float) $mainItem->unit_price_inc_tax,
            'tax_id' => $mainItem->tax_id,
            'tax_value' => (float) $mainItem->tax_value,
            'discount_type' => $mainItem->discount_type,
            'discount_amount' => (float) $mainItem->discount_amount,
            'status' => $mainItem->line_status ?? 'inpreparation',
            'note' => $mainItem->note ?? '',
            'order_item_modifiers' => $modifiers->map(function ($mod) {
                return [
                    'id' => $mod->id,
                    'modifier_id' => $mod->modifier_id ?? $mod->product_id,
                    'modifier_name' => $mod->product->name_ar ?? '',
                    'quantity' => (float) $mod->qyt,
                    'price' => (float) $mod->unit_price,
                    'price_with_tax' => (float) $mod->unit_price_inc_tax,
                ];
            })->values()->all(),
            'order_item_combos' => $combos->map(function ($combo) use ($comboNameResolver) {
                return [
                    'id' => $combo->id,
                    'option_id' => (string) ($combo->combo_id ?? $combo->product_id),
                    'option_name' => $comboNameResolver($combo),
                    'price' => (float) $combo->unit_price,
                    'combo_group_id' => $combo->combo_id ? $combo->product_id : null,
                ];
            })->values()->all(),
        ];
    }

    private static function isPosComboComponentLine(TransactionSellLine $line): bool
    {
        if ($line->modifier_id !== null && $line->modifier_id !== '') {
            return false;
        }

        return (float) ($line->unit_price ?? 0) === 0.0
            && (float) ($line->unit_price_before_discount ?? 0) === 0.0
            && (float) ($line->unit_price_inc_tax ?? 0) === 0.0;
    }

    private static function isPosMainLine(TransactionSellLine $line): bool
    {
        return (float) ($line->unit_price ?? 0) > 0.0
            || (float) ($line->unit_price_before_discount ?? 0) > 0.0
            || (float) ($line->unit_price_inc_tax ?? 0) > 0.0;
    }

    private static function looksLikePosMainProductLine(TransactionSellLine $line, TransactionSellLine $currentMain): bool
    {
        if ((int) $line->product_id === (int) $currentMain->product_id) {
            return false;
        }

        $linePrice = (float) ($line->unit_price ?? 0);
        $mainPrice = (float) ($currentMain->unit_price ?? 0);

        return $linePrice > 0 && $linePrice >= max(5.0, $mainPrice * 0.5);
    }
}
