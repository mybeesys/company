<?php

namespace Modules\Screen\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenPairingBroadcastService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function linked(string $pairingId, array $payload): void
    {
        if ($pairingId === '' || ! tenant()) {
            return;
        }

        $body = array_merge([
            'event' => 'screen.linked',
            'id' => $pairingId,
        ], $payload);

        try {
            Http::timeout(3)
                ->withHeaders([
                    'X-Socket-Secret' => (string) config('realtime.internal_secret'),
                ])
                ->post((string) config('realtime.broadcast_url'), [
                    'tenant_id' => (string) tenant('id'),
                    'event' => 'screen.linked',
                    'pairing_id' => $pairingId,
                    'payload' => $body,
                    'screen_pairing' => true,
                ]);
        } catch (\Throwable $e) {
            Log::error('Screen pairing socket broadcast failed: '.$e->getMessage(), [
                'pairing_id' => $pairingId,
            ]);
        }
    }
}
