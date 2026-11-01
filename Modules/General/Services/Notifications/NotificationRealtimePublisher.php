<?php

namespace Modules\General\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class NotificationRealtimePublisher
{
    /**
     * @param  list<int>  $userIds
     * @param  array<string, mixed>  $payload
     */
    public function publishBadgeBump(array $userIds, array $payload = []): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === [] || ! tenant()) {
            return;
        }

        try {
            Http::timeout(2)
                ->withHeaders([
                    'X-Socket-Secret' => (string) config('realtime.internal_secret'),
                ])
                ->post(config('realtime.broadcast_url'), [
                    'tenant_id' => (string) tenancy()->tenant->id,
                    'dashboard_notification' => true,
                    'user_ids' => $userIds,
                    'event' => 'notification:new',
                    'payload' => array_merge([
                        'event_id' => (string) Str::uuid(),
                        'schema_version' => config('realtime.schema_version', 1),
                        'event' => 'notification:new',
                        'timestamp' => now()->toIso8601String(),
                    ], $payload),
                ]);
        } catch (\Throwable $e) {
            Log::debug('Dashboard notification socket publish failed: '.$e->getMessage());
        }
    }
}
