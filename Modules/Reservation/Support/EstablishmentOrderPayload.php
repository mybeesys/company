<?php

namespace Modules\Reservation\Support;

use Modules\Product\Models\TypesOfService;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Models\TableOrders;

class EstablishmentOrderPayload
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function ordersForEstablishment(int $establishmentId, ?string $type = null): array
    {
        $orders = TableOrders::where('establishment_id', $establishmentId)
            ->where('order_status', '<>', 'canceled')
            ->with(['sell_lines.product', 'sell_lines.productCombo', 'createdBy', 'payment'])
            ->when($type, fn ($q) => $q->where('order_type', $type))
            ->get();

        return $orders->map(fn (TableOrders $order) => self::formatOrder($order))
            ->filter()
            ->values()
            ->all();
    }

    public static function formatOrder(TableOrders $order): ?array
    {
        $order->loadMissing(['sell_lines.product', 'sell_lines.productCombo', 'createdBy', 'payment']);

        $reservation = Reservation::where('table_id', $order->table_id)
            ->where('status', 'active')
            ->first();

        $table = $order->table_id ? Table::find($order->table_id) : null;
        $allLines = $order->sell_lines ?? collect();
        $parentItems = $allLines->where('parent_id', null);

        if ($parentItems->isEmpty()) {
            return null;
        }

        $service = TypesOfService::find($order->order_type);
        $waiter = $order->createdBy;
        $lineIndex = 0;

        $items = $parentItems->map(function ($mainItem) use ($allLines, $order, &$lineIndex) {
            $lineIndex++;
            $subItems = $allLines->where('parent_id', $mainItem->id);

            return [
                'id' => $mainItem->id,
                'line_id' => "{$order->id}-{$mainItem->id}-{$lineIndex}",
                'product_id' => $mainItem->product_id,
                'product_name' => $mainItem->product->name_ar ?? '',
                'quantity' => (float) $mainItem->qyt,
                'price' => (float) $mainItem->unit_price,
                'price_with_tax' => (float) $mainItem->unit_price_inc_tax,
                'price_without_tax' => (float) ($mainItem->unit_price_before_discount ?? $mainItem->unit_price),
                'note' => $mainItem->note ?? '',
                'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                    return [
                        'modifier_id' => $mod->modifier_id ?? $mod->product_id,
                        'modifier_name' => $mod->product->name_ar ?? '',
                        'quantity' => (float) $mod->qyt,
                        'price' => (float) $mod->unit_price,
                        'price_with_tax' => (float) $mod->unit_price_inc_tax,
                    ];
                })->values()->all(),
                'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                    return [
                        'option_id' => (string) $combo->combo_id,
                        'option_name' => $combo->productCombo->name_ar ?? '',
                        'price' => (float) $combo->unit_price,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $subTotal = (float) ($order->total_after_discount ?? $order->total_before_tax ?? 0);
        $taxAmount = (float) ($order->tax_amount ?? 0);
        $totalAmount = (float) ($order->final_total ?? 0);

        return [
            'id' => $order->id,
            'table_id' => (string) $order->table_id,
            'table_name' => $table?->code ?? (string) $order->table_id,
            'customer_name' => $reservation->customer_name ?? 'Guest',
            'ref_no' => $order->ref_no,
            'status' => $order->status,
            'invoice_type' => $order->invoice_type,
            'transaction_date' => $order->transaction_date,
            'order_status' => $order->order_status,
            'order_type' => $service->name_ar ?? 'محلي',
            'payment_status' => $order->payment_status,
            'total_amount' => (string) $totalAmount,
            'sub_total' => (string) $subTotal,
            'tax_amount' => (string) $taxAmount,
            'paid_amount' => $order->payment?->sum('amount') ?? 0,
            'description' => $order->description,
            'note' => $order->description ?? '',
            'establishment_id' => $order->establishment_id,
            'created_by' => $order->created_by,
            'waiter_name' => $waiter?->name_ar ?? $waiter?->name_en ?? '',
            'discount_amount' => $order->discount_amount,
            'discount_type' => $order->discount_type,
            'total_before_tax' => $order->total_before_tax,
            'total_after_discount' => $order->total_after_discount,
            'invoice_created' => ! empty($order->id),
            'invoice_id' => $order->id,
            'items' => $items,
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
