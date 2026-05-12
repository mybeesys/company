<?php

namespace Modules\Screen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Screen\Models\Device;

class ScreenAuthApiController extends Controller
{
    /**
     * إصدار توكن Sanctum للشاشة بعد التحقق عبر رمز الاقتران (QR) أو رمز PIN مع كود الجهاز.
     */
    public function issueToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairing_token' => ['nullable', 'string', 'min:16', 'max:512'],
            'device_code' => ['required_without:pairing_token', 'string', 'max:255'],
            'pin' => ['required_without:pairing_token', 'string', 'min:4', 'max:32'],
        ]);

        $device = null;

        if (! empty($validated['pairing_token'])) {
            $hash = Device::hashPairingToken($validated['pairing_token']);
            $device = Device::query()->where('pairing_token_hash', $hash)->first();
            if (! $device) {
                return response()->json(['message' => __('screen::general.screen_auth_invalid_pairing')], 401);
            }
        } else {
            $device = Device::query()->where('code', $validated['device_code'])->first();
            if (! $device || empty($device->pin_hash)) {
                return response()->json(['message' => __('screen::general.screen_auth_invalid_pin')], 401);
            }
            if (! Hash::check($validated['pin'], $device->pin_hash)) {
                return response()->json(['message' => __('screen::general.screen_auth_invalid_pin')], 401);
            }
        }

        $ttlDays = (int) config('screen.api_token_ttl_days', 365);
        $expiresAt = $ttlDays > 0 ? Carbon::now()->addDays($ttlDays) : null;

        $newToken = $device->createToken('screen-api', ['screen:api'], $expiresAt);

        return response()->json([
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
            'device' => [
                'id' => $device->id,
                'code' => $device->code,
            ],
        ]);
    }

    /**
     * إلغاء التوكن الحالي (اختياري من الشاشة عند الخروج).
     */
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
}
