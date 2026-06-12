<?php

namespace Modules\Screen\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Screen\Http\Controllers\Api\Concerns\SerializesScreenPromo;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Promo;

class ScreenPlayerPromoApiController extends Controller
{
    use SerializesScreenPromo;

    /**
     * كل المواد الإعلانية المرتبطة بقوائم تشغيل هذا الجهاز (بدون تكرار).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        $promoIds = $device->playlists()
            ->with(['promos:id'])
            ->get()
            ->flatMap(fn ($playlist) => $playlist->promos->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if ($promoIds === []) {
            return response()->json(['data' => []]);
        }

        $data = Promo::query()
            ->whereIn('id', $promoIds)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Promo $promo) => $this->serializePromo($promo))
            ->values();

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Promo $promo): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        if (! $this->deviceCanAccessPromo($device, $promo)) {
            return response()->json(['message' => __('screen::general.screen_player_promo_not_found')], 404);
        }

        return response()->json(['data' => $this->serializePromo($promo)]);
    }

    protected function deviceCanAccessPromo(Device $device, Promo $promo): bool
    {
        return $device->playlists()
            ->whereHas('promos', fn ($q) => $q->where('screen_promos.id', $promo->id))
            ->exists();
    }
}
