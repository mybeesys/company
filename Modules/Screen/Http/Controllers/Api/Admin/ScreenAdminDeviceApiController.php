<?php

namespace Modules\Screen\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Screen\Models\Device;
use Modules\Screen\Services\ScreenDeviceUnlinkService;

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
}
