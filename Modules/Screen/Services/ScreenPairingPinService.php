<?php

namespace Modules\Screen\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\ScreenPairingPin;

class ScreenPairingPinService
{
    public function __construct(
        protected ScreenDeviceBroadcastService $deviceBroadcast
    ) {}

    /**
     * @return array{pin: string, expires_at: string, expires_in_seconds: int, device: Device}
     */
    public function generateForDevice(Device $device, ?int $createdBy = null): array
    {
        $ttlSeconds = max(30, (int) config('screen.pairing_pin_ttl_seconds', 120));
        $pinLength = max(4, min(8, (int) config('screen.pairing_pin_length', 6)));

        return DB::transaction(function () use ($device, $createdBy, $ttlSeconds, $pinLength) {
            $this->invalidateActivePinsForDevice($device->id);

            $pin = $this->generateUniquePin($pinLength);
            $expiresAt = Carbon::now()->addSeconds($ttlSeconds);

            ScreenPairingPin::create([
                'device_id' => $device->id,
                'pin_hash' => ScreenPairingPin::hashPin($pin),
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ]);

            return [
                'pin' => $pin,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in_seconds' => $ttlSeconds,
                'device' => $device->fresh(),
            ];
        });
    }

    /**
     * @return array{device: Device, token: string, expires_at: ?string, device_channel: string}
     */
    public function verifyAndPair(Request $request, string $pin): array
    {
        $pin = trim($pin);
        $pinHash = ScreenPairingPin::hashPin($pin);

        $session = ScreenPairingPin::query()
            ->with('device')
            ->where('pin_hash', $pinHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $session || ! $session->device) {
            throw ValidationException::withMessages([
                'pin' => [__('screen::general.screen_pairing_pin_invalid')],
            ]);
        }

        return DB::transaction(function () use ($request, $session) {
            $locked = ScreenPairingPin::query()
                ->whereKey($session->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'pin' => [__('screen::general.screen_pairing_pin_invalid')],
                ]);
            }

            $device = $locked->device;
            $locked->forceFill(['used_at' => now()])->save();

            $device->tokens()->where('name', 'screen-player-api')->delete();

            $ttlDays = (int) config('screen.api_token_ttl_days', 365);
            $expiresAt = $ttlDays > 0 ? Carbon::now()->addDays($ttlDays) : null;
            $newToken = $device->createToken('screen-player-api', ['screen:player'], $expiresAt);

            $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
            if ($hasEstablishment) {
                $device->loadMissing('establishment:id,name');
            }

            $devicePayload = [
                'id' => $device->id,
                'code' => $device->code,
            ];
            if ($hasEstablishment) {
                $devicePayload['establishment_id'] = $device->establishment_id;
                $devicePayload['establishment_name'] = $device->establishment?->name;
            }

            $deviceChannel = config('screen.device_channel_prefix').$device->id;

            $this->deviceBroadcast->toDevice($device->id, 'screen.linked', [
                'tenant_id' => (string) tenant('id'),
                'token' => $newToken->plainTextToken,
                'api_base_url' => $request->getSchemeAndHttpHost(),
                'expires_at' => $expiresAt?->toIso8601String(),
                'device' => $devicePayload,
                'device_channel' => $deviceChannel,
                'pairing_method' => 'pin',
            ]);

            return [
                'device' => $device,
                'token' => $newToken->plainTextToken,
                'expires_at' => $expiresAt?->toIso8601String(),
                'device_channel' => $deviceChannel,
            ];
        });
    }

    protected function invalidateActivePinsForDevice(int $deviceId): void
    {
        ScreenPairingPin::query()
            ->where('device_id', $deviceId)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);
    }

    protected function generateUniquePin(int $length): string
    {
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $pin = (string) random_int($min, $max);
            $hash = ScreenPairingPin::hashPin($pin);
            $exists = ScreenPairingPin::query()
                ->where('pin_hash', $hash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();

            if (! $exists) {
                return $pin;
            }
        }

        throw ValidationException::withMessages([
            'pin' => [__('screen::general.screen_pairing_pin_generate_failed')],
        ]);
    }
}
