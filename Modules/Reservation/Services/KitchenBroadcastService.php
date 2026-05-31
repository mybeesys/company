<?php

namespace Modules\Reservation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\General\Models\Transaction;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Support\KitchenOrderPayload;

class KitchenBroadcastService
{
    /**
     * @param  TableOrders|Transaction  $order
     */
    public function orderCreated($order, string $source = 'local'): void
    {
        if (! KitchenOrderPayload::appearsInKitchen($order->order_status)) {
            return;
        }

        $establishmentId = KitchenOrderPayload::establishmentIdFromOrder($order);
        if (! $establishmentId) {
            return;
        }

        $order->loadMissing(['sell_lines.product']);
        $formatted = KitchenOrderPayload::formatOrder($order);
        if (! $formatted) {
            return;
        }

        $this->send('kitchen:order:created', [
            'establishment_id' => $establishmentId,
            'order' => $formatted,
        ], $establishmentId);
    }

    /**
     * @param  TableOrders|Transaction  $order
     */
    public function orderUpdated($order, string $source = 'local'): void
    {
        $establishmentId = KitchenOrderPayload::establishmentIdFromOrder($order);
        if (! $establishmentId) {
            return;
        }

        if (! KitchenOrderPayload::appearsInKitchen($order->order_status)) {
            $this->orderRemoved($order->id, $establishmentId, $this->removalReason($order->order_status));

            return;
        }

        $order->loadMissing(['sell_lines.product']);
        $formatted = KitchenOrderPayload::formatOrder($order);
        if (! $formatted) {
            return;
        }

        $this->send('kitchen:order:updated', [
            'establishment_id' => $establishmentId,
            'order' => $formatted,
        ], $establishmentId);
    }

    public function orderRemoved(int $orderId, int $establishmentId, string $reason = 'completed'): void
    {
        $this->send('kitchen:order:removed', [
            'establishment_id' => $establishmentId,
            'order_id' => $orderId,
            'reason' => $reason,
        ], $establishmentId);
    }

    /**
     * @param  TableOrders|Transaction  $order
     */
    public function itemStatusChanged($order, int $itemId, string $itemStatus, string $source = 'local'): void
    {
        $establishmentId = KitchenOrderPayload::establishmentIdFromOrder($order);
        if (! $establishmentId) {
            return;
        }

        $order->loadMissing(['sell_lines.product']);
        $formatted = KitchenOrderPayload::formatOrder($order);

        $this->send('kitchen:item:status_changed', [
            'establishment_id' => $establishmentId,
            'order_id' => $order->id,
            'item_id' => $itemId,
            'status' => $itemStatus,
            'order_status' => $order->order_status,
            'order_type' => $formatted['order_type'] ?? ($source === 'local' ? 'محلي' : 'سفري'),
        ], $establishmentId);

        $this->orderUpdated($order, $source);
    }

    private function removalReason(?string $orderStatus): string
    {
        return match ($orderStatus) {
            'canceled' => 'cancelled',
            'served', 'completed', 'prepared' => 'completed',
            default => 'archived',
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $event, array $payload, int $establishmentId): void
    {
        if (! tenant()) {
            return;
        }

        $envelope = array_merge([
            'event_id' => (string) Str::uuid(),
            'schema_version' => config('realtime.schema_version', 1),
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], $payload);

        try {
            Http::timeout(2)
                ->withHeaders([
                    'X-Socket-Secret' => config('realtime.internal_secret'),
                ])
                ->post(config('realtime.broadcast_url'), [
                    'tenant_id' => (string) tenancy()->tenant->id,
                    'establishment_id' => $establishmentId,
                    'event' => $event,
                    'payload' => $envelope,
                    'kitchen' => true,
                ]);
        } catch (\Throwable $e) {
            Log::error('Kitchen socket broadcast failed: '.$e->getMessage(), [
                'event' => $event,
                'establishment_id' => $establishmentId,
            ]);
        }
    }
}
