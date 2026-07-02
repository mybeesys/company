<?php

namespace Modules\Screen\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Screen\Models\Device;
use Modules\Screen\Services\ScreenDeviceUnlinkService;
use Modules\Screen\Services\ScreenPairingPinService;

class ScreenAdminDeviceApiController extends Controller
{
    /**
     * فصل الشاشة عن الجلسة الحالية (إلغاء توken Player + إشعار WebSocket).
     *
     * POST /api/admin/v1/screen/devices/{device}/unlink
     */
    public function unlink(Request $request, Device $device, ScreenDeviceUnlinkService $unlinkService): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $unlinkService->unlink($device, $validated['reason'] ?? null);

        return response()->json([
            'message' => __('screen::general.screen_unlink_success'),
            'device' => [
                'id' => $result['device']->id,
                'code' => $result['device']->code,
            ],
            'tokens_revoked' => $result['tokens_revoked'],
        ]);
    }

    /**
     * توليد PIN مؤقت لربط الشاشة (صلاحية محدودة — افتراضي 2 دقيقة).
     *
     * POST /api/admin/v1/screen/devices/{device}/pairing-pin
     */
    public function generatePairingPin(Request $request, Device $device, ScreenPairingPinService $pinService): JsonResponse
    {
        $result = $pinService->generateForDevice($device, $request->user()?->id);

        return response()->json([
            'message' => __('screen::general.screen_pairing_pin_generated'),
            'pin' => $result['pin'],
            'expires_at' => $result['expires_at'],
            'expires_in_seconds' => $result['expires_in_seconds'],
            'device' => [
                'id' => $result['device']->id,
                'code' => $result['device']->code,
            ],
        ]);
    }
}
