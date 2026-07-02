<?php

namespace Modules\Screen\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Screen\Models\Device;
use Modules\Screen\Services\ScreenPairingPinService;

class ScreenPlayerAuthApiController extends Controller
{
    /**
     * إصدار توكن تشغيل للجهاز بعد التحقق من كود الجهاز فقط.
     * device_id اختياري — معرّف يولّده/يخزّنه التطبيق محلياً (ليس screen_devices.id) ولا يُستخدم حالياً في التحقق.
     */
    public function issueToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $device = Device::query()
            ->where('code', $validated['device_code'])
            ->first();

        if (! $device) {
            return response()->json(['message' => __('screen::general.screen_player_auth_invalid')], 401);
        }

        $device->tokens()->where('name', 'screen-player-api')->delete();

        $ttlDays = (int) config('screen.api_token_ttl_days', 365);
        $expiresAt = $ttlDays > 0 ? Carbon::now()->addDays($ttlDays) : null;

        $newToken = $device->createToken('screen-player-api', ['screen:player'], $expiresAt);

        $payload = [
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'device' => $this->serializeDevice($device),
        ];

        if (! empty($validated['device_id'])) {
            $payload['device_id'] = $validated['device_id'];
        }

        return response()->json($payload);
    }

    /**
     * ربط الشاشة عبر PIN مؤقت يولّده الأدمن (بديل لمسح QR).
     *
     * POST /api/v1/screen/player/auth/pair-pin
     */
    public function verifyPairingPin(Request $request, ScreenPairingPinService $pinService): JsonResponse
    {
        $pinLength = max(4, min(8, (int) config('screen.pairing_pin_length', 6)));

        try {
            $validated = $request->validate([
                'pin' => ['required', 'string', 'regex:/^\d{'.$pinLength.'}$/'],
            ]);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'pin' => [__('screen::general.screen_pairing_pin_invalid')],
            ]);
        }

        $result = $pinService->verifyAndPair($request, $validated['pin']);
        $device = $result['device'];

        return response()->json([
            'message' => __('screen::general.screen_pairing_success'),
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
            'device_channel' => $result['device_channel'],
            'device' => $this->serializeDevice($device),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        return response()->json(['data' => $this->serializeDevice($device)]);
    }

    public function revokeCurrent(Request $request): JsonResponse
    {
        /** @var Device|null $device */
        $device = $request->user();
        if (! $device instanceof Device) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $device->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => __('screen::general.screen_auth_revoked')]);
    }

    protected function serializeDevice(Device $device): array
    {
        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');
        if ($hasEstablishment) {
            $device->loadMissing('establishment:id,name');
        }

        $row = [
            'id' => $device->id,
            'code' => $device->code,
        ];

        if ($hasEstablishment) {
            $row['establishment_id'] = $device->establishment_id;
            $row['establishment_name'] = $device->establishment?->name;
        }

        return $row;
    }
}
