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
    /**
     * @param  array<string, mixed>  $payloadOverrides
     */
    public function tableUpdated(Table $table, array $payloadOverrides = [], ?int $notifyWaiterId = null): void
    {
        $table->loadMissing(['area', 'activeOrder', 'reservation']);
        $establishmentId = $table->area?->establishment_id;
        $data = array_merge(TableRealtimePayload::formatListItem($table), $payloadOverrides);
        $waiterId = $this->resolveWaiterId($notifyWaiterId, $table->assigned_waiter_id);

        $this->send('table:updated', [
            'data' => $data,
        ], $establishmentId, $table->id, $waiterId);
    }

    public function orderCreated(TableOrders $order, int $createdBy): void
    {
        $table = Table::with('area')->find($order->table_id);
        $establishmentId = $table?->area?->establishment_id;
        $waiterId = $this->resolveWaiterId(null, $table?->assigned_waiter_id);

        $this->send('order:created', [
            'table_id' => $order->table_id,
            'order_id' => $order->id,
            'order_status' => $order->order_status,
            'created_by' => $createdBy,
            'assigned_waiter_id' => $waiterId,
            'establishment_id' => $establishmentId,
        ], $establishmentId, $order->table_id, $waiterId);

        if ($table) {
            $this->tableUpdated($table, [], $waiterId);
        }
        $this->orderUpdated($order->table_id, $waiterId);
    }

    public function orderUpdated(int $tableId, ?int $notifyWaiterId = null): void
    {
        $details = TableRealtimePayload::formatTableDetails($tableId);
        if (! $details) {
            return;
        }

        $table = Table::with('area')->find($tableId);
        $establishmentId = $table?->area?->establishment_id;
        $waiterId = $this->resolveWaiterId($notifyWaiterId, $table?->assigned_waiter_id);

        $this->send('order:updated', [
            'table_id' => $tableId,
            'data' => $details,
        ], $establishmentId, $tableId, $waiterId);
    }

    /**
     * تغيير حالة الطلب — يطبَّق في تطبيق النادل حسب table_id / order_id
     * دون فلترة assigned_waiter_id (الفلتر للعرض فقط).
     */
    public function orderStatusChanged(TableOrders $order, ?int $notifyWaiterId = null): void
    {
        $table = Table::with(['area', 'activeOrder', 'reservation'])->find($order->table_id);
        $establishmentId = $table?->area?->establishment_id;
        $waiterId = $this->resolveWaiterId($notifyWaiterId, $table?->assigned_waiter_id);

        $this->send('order:status_changed', [
            'table_id' => $order->table_id,
            'order_id' => $order->id,
            'status' => $order->status,
            'order_status' => $order->order_status,
            'assigned_waiter_id' => $waiterId,
            'establishment_id' => $establishmentId,
        ], $establishmentId, $order->table_id, $waiterId);

        if ($table) {
            $this->tableUpdated($table, $this->waiterFilterOverrides($table, $waiterId), $waiterId);
        }
    }

    /**
     * طلب منتهٍ (served / canceled / completed) — حدث مستقل لتطبيق النادل
     * بالإضافة إلى order:status_changed (توافق خلفي).
     */
    public function orderFinished(TableOrders $order, ?int $notifyWaiterId = null): void
    {
        $table = Table::with('area')->find($order->table_id);
        $establishmentId = $table?->area?->establishment_id;
        $waiterId = $this->resolveWaiterId($notifyWaiterId, $table?->assigned_waiter_id);

        $this->send('order:finished', [
            'table_id' => $order->table_id,
            'order_id' => $order->id,
            'status' => $order->status,
            'order_status' => $order->order_status,
            'assigned_waiter_id' => $waiterId,
            'establishment_id' => $establishmentId,
        ], $establishmentId, $order->table_id, $waiterId);

        $this->orderStatusChanged($order, $waiterId);
        $this->orderUpdated($order->table_id, $waiterId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(
        string $event,
        array $payload,
        ?int $establishmentId = null,
        ?int $tableId = null,
        ?int $waiterId = null
    ): void {
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
                    'assigned_waiter_id' => $waiterId,
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

    /**
     * بعد served/canceled يُصفَّر assigned_waiter_id قبل البث. نُبقي المعرّف في
     * payload السوكت حتى لا يسقط تطبيق النادل الحدث بسبب فلتر النادل.
     *
     * @return array<string, mixed>
     */
    private function waiterFilterOverrides(Table $table, ?int $notifyWaiterId): array
    {
        if (! $notifyWaiterId || (int) $table->assigned_waiter_id === $notifyWaiterId) {
            return [];
        }

        return [
            'assigned_waiter_id' => $notifyWaiterId,
            'previous_assigned_waiter_id' => $notifyWaiterId,
        ];
    }

    private function resolveWaiterId(?int $notifyWaiterId, mixed $assignedWaiterId): ?int
    {
        if ($notifyWaiterId) {
            return $notifyWaiterId;
        }

        return $assignedWaiterId ? (int) $assignedWaiterId : null;
    }
}
