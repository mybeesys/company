<?php

namespace Modules\Screen\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Screen\Models\Device;

class ScreenPairingService
{
    public function __construct(
        protected ScreenPairingBroadcastService $broadcast
    ) {}

    /**
     * ربط شاشة (pairing_id من QR) بجهاز وإرسال screen.linked عبر WebSocket.
     *
     * @return array{device: Device, token: string, expires_at: ?string}
     */
    public function pairFromAdmin(Request $request, string $pairingId, string $deviceCode, ?int $establishmentId = null): array
    {
        $pairingId = strtolower(trim($pairingId));
        if (! Device::isValidExternalPairingId($pairingId)) {
            throw ValidationException::withMessages([
                'pairing_id' => [__('screen::general.screen_pairing_invalid_id')],
            ]);
        }

        $hash = Device::hashPairingToken($pairingId);

        if (DB::table('screen_pairing_sessions')->where('pairing_id_hash', $hash)->exists()) {
            throw ValidationException::withMessages([
                'pairing_id' => [__('screen::general.screen_pairing_already_used')],
            ]);
        }

        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
        if ($hasEstablishment && $establishmentId === null) {
            throw ValidationException::withMessages([
                'establishment_id' => [__('messages.field_is_required', ['field' => __('screen::fields.establishment')])],
            ]);
        }

        return DB::transaction(function () use ($request, $pairingId, $hash, $deviceCode, $establishmentId, $hasEstablishment) {
            $device = Device::query()->where('code', $deviceCode)->first();

            if ($device) {
                $payload = ['code' => $deviceCode];
                if ($hasEstablishment && $establishmentId !== null) {
                    $payload['establishment_id'] = $establishmentId;
                }
                $device->update($payload);
            } else {
                $payload = ['code' => $deviceCode];
                if ($hasEstablishment && $establishmentId !== null) {
                    $payload['establishment_id'] = $establishmentId;
                }
                $device = Device::create($payload);
            }

            $device->tokens()->where('name', 'screen-player-api')->delete();

            $ttlDays = (int) config('screen.api_token_ttl_days', 365);
            $expiresAt = $ttlDays > 0 ? Carbon::now()->addDays($ttlDays) : null;
            $newToken = $device->createToken('screen-player-api', ['screen:player'], $expiresAt);

            $device->forceFill(['pairing_token_hash' => $hash])->save();

            DB::table('screen_pairing_sessions')->insert([
                'pairing_id_hash' => $hash,
                'device_id' => $device->id,
                'linked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

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

            $this->broadcast->linked($pairingId, [
                'tenant_id' => (string) tenant('id'),
                'token' => $newToken->plainTextToken,
                'api_base_url' => $request->getSchemeAndHttpHost(),
                'expires_at' => $expiresAt?->toIso8601String(),
                'device' => $devicePayload,
                'device_channel' => config('screen.device_channel_prefix').$device->id,
            ]);

            return [
                'device' => $device,
                'token' => $newToken->plainTextToken,
                'expires_at' => $expiresAt?->toIso8601String(),
            ];
        });
    }
}
