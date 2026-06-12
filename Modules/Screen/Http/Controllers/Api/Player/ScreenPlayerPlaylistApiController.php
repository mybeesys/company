<?php

namespace Modules\Screen\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Screen\Http\Controllers\Api\Concerns\SerializesScreenPromo;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Playlist;
use Modules\Screen\Models\Promo;

class ScreenPlayerPlaylistApiController extends Controller
{
    use SerializesScreenPromo;

    public function index(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        $data = $device->playlists()
            ->orderByDesc('screen_playlists.id')
            ->get()
            ->map(fn (Playlist $playlist) => $this->serializePlaylistSummary($playlist));

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Playlist $playlist): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        if (! $this->deviceOwnsPlaylist($device, $playlist)) {
            return response()->json(['message' => __('screen::general.screen_player_playlist_not_found')], 404);
        }

        return response()->json(['data' => $this->serializePlaylistDetail($playlist)]);
    }

    public function promos(Request $request, Playlist $playlist): JsonResponse
    {
        /** @var Device $device */
        $device = $request->user();

        if (! $this->deviceOwnsPlaylist($device, $playlist)) {
            return response()->json(['message' => __('screen::general.screen_player_playlist_not_found')], 404);
        }

        $data = $playlist->promos()
            ->get()
            ->map(fn (Promo $promo) => $this->serializePromo($promo))
            ->values();

        return response()->json(['data' => $data]);
    }

    protected function deviceOwnsPlaylist(Device $device, Playlist $playlist): bool
    {
        return $device->playlists()->where('screen_playlists.id', $playlist->id)->exists();
    }

    protected function serializePlaylistSummary(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'screen_orientation' => $playlist->screen_orientation,
            'days_settings' => $playlist->days_settings ?? [],
            'promos_count' => $playlist->promos()->count(),
            'updated_at' => $playlist->updated_at?->toIso8601String(),
        ];
    }

    protected function serializePlaylistDetail(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'screen_orientation' => $playlist->screen_orientation,
            'days_settings' => $playlist->days_settings ?? [],
            'selected_promos' => $playlist->promos()->pluck('screen_promos.id')->values()->all(),
            'created_at' => $playlist->created_at?->toIso8601String(),
            'updated_at' => $playlist->updated_at?->toIso8601String(),
        ];
    }
}
