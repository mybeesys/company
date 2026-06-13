<?php

namespace Modules\Screen\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenDeviceBroadcastService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function toDevice(int $deviceId, string $event, array $payload): void
    {
        if ($deviceId <= 0 || $event === '' || ! tenant()) {
            return;
        }

        $body = array_merge([
            'event' => $event,
            'device_id' => $deviceId,
            'tenant_id' => (string) tenant('id'),
        ], $payload);

        try {
            Http::timeout(3)
                ->withHeaders([
                    'X-Socket-Secret' => (string) config('realtime.internal_secret'),
                ])
                ->post((string) config('realtime.broadcast_url'), [
                    'tenant_id' => (string) tenant('id'),
                    'event' => $event,
                    'device_id' => $deviceId,
                    'payload' => $body,
                    'screen_device' => true,
                ]);
        } catch (\Throwable $e) {
            Log::error('Screen device socket broadcast failed: '.$e->getMessage(), [
                'device_id' => $deviceId,
                'event' => $event,
            ]);
        }
    }
}
