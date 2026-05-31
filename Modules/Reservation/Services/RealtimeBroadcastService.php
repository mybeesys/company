<?php

namespace Modules\Reservation\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Support\TableRealtimePayload;

class RealtimeBroadcastService
{
    public function tableUpdated(Table $table): void
    {
        $table->loadMissing(['area', 'activeOrder', 'reservation']);
        $establishmentId = $table->area?->establishment_id;

        $this->send('table:updated', [
            'data' => TableRealtimePayload::formatListItem($table),
        ], $establishmentId, $table->id);
    }

    public function orderCreated(TableOrders $order, int $createdBy): void
    {
        $table = Table::with('area')->find($order->table_id);
        $establishmentId = $table?->area?->establishment_id;

        $this->send('order:created', [
            'table_id' => $order->table_id,
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'created_by' => $createdBy,
        ], $establishmentId, $order->table_id);

        if ($table) {
            $this->tableUpdated($table);
        }
        $this->orderUpdated($order->table_id);
    }

    public function orderUpdated(int $tableId): void
    {
        $details = TableRealtimePayload::formatTableDetails($tableId);
        if (! $details) {
            return;
        }

        $table = Table::with('area')->find($tableId);
        $establishmentId = $table?->area?->establishment_id;

        $this->send('order:updated', [
            'table_id' => $tableId,
            'data' => $details,
        ], $establishmentId, $tableId);
    }

    public function orderStatusChanged(TableOrders $order): void
    {
        $table = Table::with('area')->find($order->table_id);
        $establishmentId = $table?->area?->establishment_id;

        $this->send('order:status_changed', [
            'table_id' => $order->table_id,
            'order_id' => $order->id,
            'status' => $order->status,
            'order_status' => $order->order_status,
        ], $establishmentId, $order->table_id);

        if ($table) {
            $this->tableUpdated($table);
        }
    }

    /**
     * طلب منتهٍ (served / canceled / paid+إغلاق) — تحديث شبكة الطاولات + تفاصيل الطاولة.
     */
    public function orderFinished(TableOrders $order): void
    {
        $this->orderStatusChanged($order);
        $this->orderUpdated($order->table_id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $event, array $payload, ?int $establishmentId = null, ?int $tableId = null): void
    {
        if (! tenant()) {
            return;
        }

        $envelope = array_merge([
            'event_id' => (string) Str::uuid(),
            'schema_version' => config('realtime.schema_version', 1),
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
        ], $payload);

        try {
            Http::timeout(2)
                ->withHeaders([
                    'X-Socket-Secret' => config('realtime.internal_secret'),
                ])
                ->post(config('realtime.broadcast_url'), [
                    'tenant_id' => (string) tenancy()->tenant->id,
                    'establishment_id' => $establishmentId,
                    'table_id' => $tableId,
                    'event' => $event,
                    'payload' => $envelope,
                ]);
        } catch (\Throwable $e) {
            Log::error('Socket broadcast failed: '.$e->getMessage(), [
                'event' => $event,
                'table_id' => $tableId,
            ]);
        }
    }
}
