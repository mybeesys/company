<?php

namespace Modules\Screen\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Screen\Services\ScreenPairingService;

class ScreenAdminAuthApiController extends Controller
{
    /**
     * ربط شاشة Player عبر مسح QR (pairing_id) وإرسال screen.linked على WebSocket.
     *
     * POST /api/admin/v1/screen/auth/token
     */
    public function pair(Request $request, ScreenPairingService $pairingService): JsonResponse
    {
        $hasEstablishment = Schema::hasColumn('screen_devices', 'establishment_id');

        $rules = [
            'pairing_id' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/i'],
            'device_code' => ['required', 'string', 'max:255'],
        ];
        if ($hasEstablishment) {
            $rules['establishment_id'] = ['required', 'integer', 'exists:est_establishments,id'];
        } else {
            $rules['establishment_id'] = ['nullable', 'integer'];
        }

        $validated = $request->validate($rules);

        $result = $pairingService->pairFromAdmin(
            $request,
            $validated['pairing_id'],
            $validated['device_code'],
            isset($validated['establishment_id']) ? (int) $validated['establishment_id'] : null
        );

        $device = $result['device'];

        return response()->json([
            'message' => __('screen::general.screen_pairing_success'),
            'device' => [
                'id' => $device->id,
                'code' => $device->code,
            ],
        ]);
    }
}
