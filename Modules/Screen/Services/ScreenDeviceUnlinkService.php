<?php

namespace Modules\Screen\Services;

use Illuminate\Support\Facades\DB;
use Modules\Screen\Models\Device;

class ScreenDeviceUnlinkService
{
    public function __construct(
        protected ScreenDeviceBroadcastService $broadcast
    ) {}

    /**
     * فصل الشاشة (إلغاء جلسة Player) — لا يحذف سجل الجهاز.
     *
     * @return array{device: Device, tokens_revoked: int}
     */
    public function unlink(Device $device, ?string $reason = null): array
    {
        return DB::transaction(function () use ($device, $reason) {
            $tokensRevoked = $device->tokens()->where('name', 'screen-player-api')->delete();

            $this->broadcast->toDevice($device->id, 'screen.unlinked', [
                'device' => [
                    'id' => $device->id,
                    'code' => $device->code,
                ],
                'reason' => $reason ?: 'admin_unlink',
            ]);

            return [
                'device' => $device->fresh(),
                'tokens_revoked' => (int) $tokensRevoked,
            ];
        });
    }
}
