<?php

namespace Modules\Reservation\Support;

use Illuminate\Support\Collection;
use Modules\General\Models\Transaction;
use Modules\Product\Models\TypesOfService;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\TableOrders;

class KitchenOrderPayload
{
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
            ->with(['sell_lines.product', 'createdBy'])
            ->get();

        $posTransactions = Transaction::where('establishment_id', $establishmentId)
            ->where('type', 'sell')
            ->where('order_status', 'inpreparation')
            ->when(! empty($categoryIds), function ($query) use ($categoryIds) {
                return $query->whereHas('sell_lines.product', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                });
            })
            ->with(['sell_lines.product', 'createdBy'])
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

        $filteredLines = $allLines->filter(function ($line) use ($categoryIds) {
            if (empty($categoryIds)) {
                return true;
            }

            if (! $line->product || $line->product->category_id === null) {
                return false;
            }

            return in_array((int) $line->product->category_id, array_map('intval', $categoryIds), true);
        });

        if (! empty($categoryIds) && $filteredLines->isEmpty()) {
            return null;
        }

        $serviceName = 'محلي';
        if (! isset($order->table_id)) {
            $serviceName = 'سفري';
        } else {
            $service = TypesOfService::find($order->order_type);
            $serviceName = $service->name_ar ?? 'محلي';
        }

        return [
            'id' => $order->id,
            'table_id' => $order->table_id ?? null,
            'created_by' => $order->created_by,
            'order_type' => $serviceName,
            'order_status' => $order->order_status,
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
            'updated_at' => $order->updated_at?->toIso8601String(),
            'customer_name' => $reservation->customer_name ?? ($order->contact->name ?? 'Guest'),
            'customer_phone' => $reservation->customer_phone ?? ($order->contact->mobile ?? ''),
            'guests_count' => $reservation->guests_count ?? 0,
            'discount_type' => $order->discount_type,
            'discount_value' => (float) $order->discount_amount,
            'total_before_discount' => (float) $order->total_before_tax,
            'total_after_discount' => (float) $order->total_after_discount,
            'total_tax' => (float) $order->tax_amount,
            'total_paid' => (float) $order->final_total,
            'note' => $order->description,
            'items' => $filteredLines->map(function ($mainItem) {
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
                ];
            })->values()->all(),
        ];
    }

    public static function appearsInKitchen(?string $orderStatus): bool
    {
        return $orderStatus === 'inpreparation';
    }

    public static function establishmentIdFromOrder($order): ?int
    {
        return $order->establishment_id ?? null;
    }
}
