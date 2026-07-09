<?php

namespace Modules\Screen\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Screen\Http\Requests\StorePlaylistRequest;
use Modules\Screen\Models\Playlist;
use Modules\Screen\Models\Promo;
use Modules\Screen\Services\ScreenPlaylistSyncNotifier;

class ScreenPlaylistApiController extends Controller
{
    public function __construct(
        protected ScreenPlaylistSyncNotifier $playlistSyncNotifier
    ) {}

    public function index(): JsonResponse
    {
        $data = Playlist::query()->orderByDesc('id')->get()->map(fn (Playlist $p) => $this->serializePlaylistSummary($p));

        return response()->json(['data' => $data]);
    }

    public function show(Playlist $playlist): JsonResponse
    {
        return response()->json(['data' => $this->serializePlaylistDetail($playlist)]);
    }

    public function store(StorePlaylistRequest $request): JsonResponse
    {
        $data = $request->safe();
        $orientation = $data->screen_orientation ?? 'landscape';

        try {
            $playlist = DB::transaction(function () use ($data, $orientation) {
                $days_settings = [
                    'days_settings_option' => $data->days_settings,
                    'start_time' => $data->start_time,
                    'start_date_time' => $data->start_date_time,
                    'days_of_the_weak' => $data->days_of_the_weak,
                    'screen_orientation' => $orientation,
                    'transition_seconds' => (int) $data->transition_seconds,
                ];

                $playlist = Playlist::create([
                    'name' => $data->name,
                    'days_settings' => $days_settings,
                    'screen_orientation' => $orientation,
                ]);

                foreach ($data->selected_promos as $index => $promoId) {
                    $playlist->promos()->attach($promoId, [
                        'created_at' => now()->addSeconds($index),
                        'updated_at' => now()->addSeconds($index),
                    ]);
                }
                $playlist->establishments()->sync($data->establishments_ids);
                $playlist->devices()->sync($data->devices);

                return $playlist->fresh();
            });

            $this->playlistSyncNotifier->created($playlist);

            return response()->json([
                'message' => __('employee::responses.operation_success'),
                'data' => $this->serializePlaylistDetail($playlist),
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('api playlist store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['message' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function update(StorePlaylistRequest $request, Playlist $playlist): JsonResponse
    {
        $data = $request->safe();
        $orientation = $data->screen_orientation ?? $playlist->screen_orientation ?? 'landscape';

        try {
            $playlist = DB::transaction(function () use ($data, $playlist, $orientation) {
                $days_settings = [
                    'days_settings_option' => $data->days_settings,
                    'start_time' => $data->start_time,
                    'start_date_time' => $data->start_date_time,
                    'days_of_the_weak' => $data->days_of_the_weak,
                    'screen_orientation' => $orientation,
                    'transition_seconds' => (int) $data->transition_seconds,
                ];
                $playlist->update([
                    'name' => $data->name,
                    'days_settings' => $days_settings,
                    'screen_orientation' => $orientation,
                ]);

                $playlist->promos()->detach();
                foreach ($data->selected_promos as $index => $promoId) {
                    $playlist->promos()->attach($promoId, [
                        'created_at' => now()->addSeconds($index),
                        'updated_at' => now()->addSeconds($index),
                    ]);
                }
                $playlist->establishments()->sync($data->establishments_ids);
                $playlist->devices()->sync($data->devices);

                return $playlist->fresh();
            });

            $this->playlistSyncNotifier->updated($playlist);

            return response()->json([
                'message' => __('employee::responses.updated_successfully', ['name' => __('screen::general.playlist')]),
                'data' => $this->serializePlaylistDetail($playlist),
            ]);
        } catch (\Throwable $e) {
            \Log::error('api playlist update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['message' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function destroy(Playlist $playlist): JsonResponse
    {
        $deviceIds = $playlist->devices()->pluck('screen_devices.id')->all();
        $playlistId = $playlist->id;
        $playlist->delete();

        $this->playlistSyncNotifier->deleted($playlistId, $deviceIds);

        return response()->json([
            'message' => __('employee::responses.deleted_successfully', ['name' => __('screen::general.playlist')]),
        ]);
    }

    /**
     * قائمة المواد الإعلانية المرتبطة بالـ playlist مع روابط الملفات.
     */
    public function promos(Playlist $playlist): JsonResponse
    {
        $base = 'storage/tenant'.tenancy()->tenant->id.'/';
        $rows = $playlist->promos()->get(['screen_promos.id', 'screen_promos.name', 'screen_promos.path', 'screen_promos.thumbnail']);

        $data = $rows->map(function (Promo $promo) use ($base) {
            return [
                'id' => $promo->id,
                'name' => $promo->name,
                'path' => $promo->path,
                'media_url' => asset($base.$promo->path),
                'thumbnail' => $promo->thumbnail,
                'thumbnail_url' => $promo->thumbnail ? asset($base.$promo->thumbnail) : null,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    protected function serializePlaylistSummary(Playlist $playlist): array
    {
        return [
            'id' => $playlist->id,
            'name' => $playlist->name,
            'screen_orientation' => $playlist->screen_orientation,
            'promos_count' => $playlist->promos()->count(),
            'devices_count' => $playlist->devices()->count(),
            'establishments_count' => $playlist->establishments()->count(),
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
            'establishments_ids' => $playlist->establishments()->pluck('est_establishments.id')->values()->all(),
            'devices' => $playlist->devices()->pluck('screen_devices.id')->values()->all(),
            'selected_promos' => $playlist->promos()->pluck('screen_promos.id')->values()->all(),
            'created_at' => $playlist->created_at?->toIso8601String(),
            'updated_at' => $playlist->updated_at?->toIso8601String(),
        ];
    }
}
