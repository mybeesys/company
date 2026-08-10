<?php

namespace Modules\Reservation\Support;

use Illuminate\Http\Request;
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
            ->get()
            ->reject(fn (Transaction $transaction) => self::shouldExcludePosTransactionFromKitchen($transaction, $tableOrders));

        return $tableOrders->concat($posTransactions)
            ->map(fn ($order) => self::formatOrder($order, $categoryIds))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * فاتورة POS مرتبطة بطاولة — المطبخ يعرض table_orders فقط (محلي).
     */
    public static function requestRepresentsTableSale(Request $request): bool
    {
        self::linkTableOrderToInvoiceRequest($request);

        return $request->filled('table_id') || $request->filled('table_order_id');
    }

    /**
     * يربط فاتورة الكاشير بطلب الطاولة إن وُجد (table_id / table_order_id / محلي + نفس الإجمالي).
     */
    public static function linkTableOrderToInvoiceRequest(Request $request): void
    {
        if ($request->filled('table_id') && ! $request->filled('table_order_id')) {
            $linkedTableOrderId = TableOrders::where('table_id', $request->table_id)
                ->where('order_status', 'inpreparation')
                ->when($request->filled('establishment_id'), function ($query) use ($request) {
                    $query->where('establishment_id', $request->establishment_id);
                })
                ->latest('id')
                ->value('id');

            if ($linkedTableOrderId) {
                $request->merge(['table_order_id' => $linkedTableOrderId]);
            }

            return;
        }

        if ($request->filled('table_order_id') || $request->filled('table_id')) {
            return;
        }

        if (! self::isDineInOrderType($request->input('order_type'))) {
            return;
        }

        $establishmentId = $request->input('establishment_id');
        $userId = $request->input('user_id');
        $finalTotal = (float) $request->input('total_paid', 0);

        if (! $establishmentId || ! $userId || $finalTotal <= 0) {
            return;
        }

        $tableOrder = TableOrders::query()
            ->where('establishment_id', $establishmentId)
            ->where('created_by', $userId)
            ->where('order_status', 'inpreparation')
            ->whereBetween('final_total', [$finalTotal - 0.05, $finalTotal + 0.05])
            ->latest('id')
            ->first();

        if ($tableOrder) {
            $request->merge([
                'table_order_id' => $tableOrder->id,
                'table_id' => $tableOrder->table_id,
            ]);
        }
    }

    public static function isDineInOrderType(mixed $orderType): bool
    {
        if ($orderType === null || $orderType === '') {
            return false;
        }

        $service = TypesOfService::find($orderType);
        if (! $service) {
            return false;
        }

        $name = mb_strtolower((string) ($service->name_ar ?? $service->name ?? ''));

        if (str_contains($name, 'سفر')) {
            return false;
        }

        return str_contains($name, 'محل');
    }

    /**
     * @param  Collection<int, TableOrders>  $activeTableOrders
     */
    public static function shouldExcludePosTransactionFromKitchen(
        Transaction $transaction,
        Collection $activeTableOrders,
    ): bool {
        $tableOrderId = self::normalizeOptionalId($transaction->table_order_id);
        if ($tableOrderId !== null) {
            if ($activeTableOrders->contains(fn (TableOrders $order) => (int) $order->id === $tableOrderId)) {
                return true;
            }

            return TableOrders::where('id', $tableOrderId)
                ->where('order_status', 'inpreparation')
                ->exists();
        }

        $tableId = self::normalizeOptionalId($transaction->table_id);
        if ($tableId !== null) {
            return $activeTableOrders->contains(
                fn (TableOrders $order) => (int) $order->table_id === $tableId
            );
        }

        if (self::isDineInOrderType($transaction->order_type)) {
            return self::matchesActiveTableOrderDuplicate($transaction, $activeTableOrders);
        }

        return false;
    }

    /**
     * @param  Collection<int, TableOrders>  $activeTableOrders
     */
    private static function matchesActiveTableOrderDuplicate(
        Transaction $transaction,
        Collection $activeTableOrders,
    ): bool {
        foreach ($activeTableOrders as $tableOrder) {
            if ((int) $tableOrder->establishment_id !== (int) $transaction->establishment_id) {
                continue;
            }

            if ((int) $tableOrder->created_by !== (int) $transaction->created_by) {
                continue;
            }

            if (abs((float) $tableOrder->final_total - (float) $transaction->final_total) > 0.05) {
                continue;
            }

            $tableAt = $tableOrder->updated_at ?? $tableOrder->created_at;
            $txAt = $transaction->created_at;

            if ($tableAt && $txAt && abs($tableAt->diffInSeconds($txAt)) <= 300) {
                return true;
            }
        }

        return false;
    }

    public static function shouldBroadcastPosTransactionToKitchen(Transaction $transaction): bool
    {
        $query = TableOrders::where('order_status', 'inpreparation');
        if (! empty($transaction->establishment_id)) {
            $query->where('establishment_id', $transaction->establishment_id);
        }

        return ! self::shouldExcludePosTransactionFromKitchen($transaction, $query->get());
    }

    private static function normalizeOptionalId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
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
        } elseif (! empty($order->order_type)) {
            $service = TypesOfService::find($order->order_type);
            if ($service) {
                $serviceName = $service->name_ar ?? $serviceName;
            }
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
        if ($allLines->contains(fn (TransactionSellLine $line) => ! empty($line->parent_id))) {
            return self::formatPosSellLinesByParent($allLines, $categoryIds);
        }

        return self::formatPosSellLinesSequential($allLines, $categoryIds);
    }

    /**
     * @param  Collection<int, TransactionSellLine>  $allLines
     * @param  array<int>  $categoryIds
     * @return array<int, array<string, mixed>>
     */
    private static function formatPosSellLinesByParent(Collection $allLines, array $categoryIds = []): array
    {
        $parentItems = $allLines->filter(
            fn (TransactionSellLine $line) => empty($line->parent_id)
        );

        return $parentItems
            ->map(function (TransactionSellLine $mainItem) use ($allLines, $categoryIds) {
                $subItems = $allLines->filter(
                    fn (TransactionSellLine $line) => (string) $line->parent_id === (string) $mainItem->id
                );

                $modifiers = $subItems
                    ->filter(fn (TransactionSellLine $line) => self::isPosModifierLine($line))
                    ->values();
                $combos = $subItems
                    ->filter(fn (TransactionSellLine $line) => self::isPosComboLine($line))
                    ->values();

                if (! self::lineGroupMatchesCategories($mainItem, $modifiers, $combos, $categoryIds)) {
                    return null;
                }

                return self::mapMainKitchenItem(
                    $mainItem,
                    $modifiers,
                    $combos,
                    fn ($combo) => $combo->product->name_ar ?? ''
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
    private static function formatPosSellLinesSequential(Collection $allLines, array $categoryIds = []): array
    {
        $items = [];
        $currentMain = null;
        /** @var array<int, TransactionSellLine> $currentChildren */
        $currentChildren = [];

        $flush = function () use (&$items, &$currentMain, &$currentChildren, $categoryIds) {
            if (! $currentMain) {
                return;
            }

            [$modifiers, $combos] = self::splitLegacyPosChildLines(collect($currentChildren));

            if (self::lineGroupMatchesCategories($currentMain, $modifiers, $combos, $categoryIds)) {
                $items[] = self::mapMainKitchenItem(
                    $currentMain,
                    $modifiers,
                    $combos,
                    fn ($combo) => $combo->product->name_ar ?? ''
                );
            }

            $currentMain = null;
            $currentChildren = [];
        };

        foreach ($allLines->sortBy('id')->values() as $line) {
            if (self::isPosComboComponentLine($line)) {
                if ($currentMain) {
                    $currentChildren[] = $line;
                }

                continue;
            }

            if ($currentMain === null) {
                $currentMain = $line;

                continue;
            }

            if (self::legacyPosChildrenContainCombos($currentChildren)) {
                $flush();
                $currentMain = $line;

                continue;
            }

            if (! self::shouldAttachAsLegacyPosChildLine($line, $currentMain, $currentChildren)) {
                $flush();
                $currentMain = $line;

                continue;
            }

            $currentChildren[] = $line;
        }

        $flush();

        return $items;
    }

    /**
     * @param  Collection<int, TransactionSellLine>  $children
     * @return array{0: Collection<int, TransactionSellLine>, 1: Collection<int, TransactionSellLine>}
     */
    private static function splitLegacyPosChildLines(Collection $children): array
    {
        if ($children->isEmpty()) {
            return [collect(), collect()];
        }

        $lines = $children->values()->all();
        $firstZeroIndex = null;

        foreach ($lines as $index => $line) {
            if (self::isPosComboComponentLine($line)) {
                $firstZeroIndex = $index;
                break;
            }
        }

        if ($firstZeroIndex === null) {
            return [$children->values(), collect()];
        }

        $comboStart = $firstZeroIndex;
        while ($comboStart > 0) {
            $previous = $lines[$comboStart - 1];
            if (self::isPosComboComponentLine($previous)) {
                break;
            }

            if ((float) ($previous->unit_price ?? 0) >= 5.0) {
                $comboStart--;

                continue;
            }

            break;
        }

        return [
            collect(array_slice($lines, 0, $comboStart))->values(),
            collect(array_slice($lines, $comboStart))->values(),
        ];
    }

    /**
     * @param  array<int, TransactionSellLine>  $children
     */
    public static function legacyPosChildrenContainCombos(array $children): bool
    {
        if ($children === []) {
            return false;
        }

        [, $combos] = self::splitLegacyPosChildLines(collect($children));

        return $combos->isNotEmpty();
    }

    /**
     * هل يُضاف السطر كابن (موديفاير/كومبو) للمنتج الرئيسي الحالي؟
     *
     * @param  array<int, TransactionSellLine>  $currentChildren
     */
    public static function shouldAttachAsLegacyPosChildLine(
        TransactionSellLine $line,
        TransactionSellLine $currentMain,
        array $currentChildren,
    ): bool {
        if (self::isPosModifierLine($line)) {
            return true;
        }

        if (! empty($line->combo_id) || self::isPosComboComponentLine($line)) {
            return true;
        }

        if (self::legacyPosChildrenContainCombos($currentChildren)) {
            return false;
        }

        return self::couldBeLegacyPosModifierLine($line, $currentMain);
    }

    /**
     * تخمين إضافة POS قديمة (بدون parent_id).
     *
     * لا نعتمد نسبة السعر: منتجات مستقلة رخيصة نسبياً (متبل، مشروب…) كانت
     * تُلصق خطأً تحت الصنف السابق. الموديفاير الحقيقي يُحفظ بـ modifier_id
     * (وparent_id في مسار POS الحالي).
     */
    public static function couldBeLegacyPosModifierLine(
        TransactionSellLine $line,
        TransactionSellLine $main,
    ): bool {
        return self::isPosModifierLine($line);
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

    public static function isPosComboComponentLine(TransactionSellLine $line): bool
    {
        if (! empty($line->combo_id)) {
            return true;
        }

        if ($line->modifier_id !== null && $line->modifier_id !== '') {
            return false;
        }

        return (float) ($line->unit_price ?? 0) === 0.0
            && (float) ($line->unit_price_before_discount ?? 0) === 0.0
            && (float) ($line->unit_price_inc_tax ?? 0) === 0.0;
    }

    public static function isPosModifierLine(TransactionSellLine $line): bool
    {
        return $line->modifier_id !== null && $line->modifier_id !== '';
    }

    public static function isPosComboLine(TransactionSellLine $line): bool
    {
        if (! empty($line->combo_id)) {
            return true;
        }

        if (self::isPosModifierLine($line)) {
            return false;
        }

        return ! empty($line->parent_id);
    }

    private static function isPosMainLine(TransactionSellLine $line): bool
    {
        return (float) ($line->unit_price ?? 0) > 0.0
            || (float) ($line->unit_price_before_discount ?? 0) > 0.0
            || (float) ($line->unit_price_inc_tax ?? 0) > 0.0;
    }

    public static function looksLikePosMainProductLine(TransactionSellLine $line, TransactionSellLine $currentMain): bool
    {
        if ((int) $line->product_id === (int) $currentMain->product_id) {
            return false;
        }

        $linePrice = (float) ($line->unit_price ?? 0);
        $mainPrice = (float) ($currentMain->unit_price ?? 0);

        return $linePrice > 0 && $linePrice >= max(5.0, $mainPrice * 0.5);
    }
}
