<?php

namespace Modules\Reservation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Support\EstablishmentOrderPayload;

/**
 * Socket.IO — طلبات الطاولات لـ My Bee POS (establishment-orders).
 */
class EstablishmentOrdersBroadcastService
{
    public function orderCreated(TableOrders $order): void
    {
        $this->broadcastOrder('establishment_order.created', $order);
    }

    public function orderUpdated(TableOrders $order): void
    {
        $this->broadcastOrder('establishment_order.updated', $order);
    }

    public function orderCancelled(TableOrders $order): void
    {
        $establishmentId = (int) $order->establishment_id;
        if (! $establishmentId || ! tenant()) {
            return;
        }

        $this->send('establishment_order.cancelled', [
            'establishment_id' => $establishmentId,
            'order_id' => $order->id,
        ], $establishmentId);
    }

    public function orderClosed(TableOrders $order): void
    {
        $establishmentId = (int) $order->establishment_id;
        if (! $establishmentId || ! tenant()) {
            return;
        }

        $this->send('establishment_order.closed', [
            'establishment_id' => $establishmentId,
            'order_id' => $order->id,
        ], $establishmentId);
    }

    private function broadcastOrder(string $event, TableOrders $order): void
    {
        $establishmentId = (int) $order->establishment_id;
        if (! $establishmentId || ! tenant()) {
            return;
        }

        $order->loadMissing(['sell_lines.product', 'sell_lines.productCombo', 'createdBy', 'payment']);
        $formatted = EstablishmentOrderPayload::formatOrder($order);
        if (! $formatted) {
            return;
        }

        $this->send($event, [
            'establishment_id' => $establishmentId,
            'order' => $formatted,
        ], $establishmentId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $event, array $payload, int $establishmentId): void
    {
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
                    'pos_orders' => true,
                ]);
        } catch (\Throwable $e) {
            Log::error('POS table orders socket broadcast failed: '.$e->getMessage(), [
                'event' => $event,
                'establishment_id' => $establishmentId,
            ]);
        }
    }
}
