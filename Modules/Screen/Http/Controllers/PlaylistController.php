<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Screen\Classes\PlaylistTable;
use Modules\Screen\Http\Requests\StorePlaylistRequest;
use Modules\Screen\Models\Device;
use Modules\Screen\Models\Playlist;

class PlaylistController extends Controller
{

    public function index(Request $request)
    {
        if($request->ajax()){
            $playlists = Playlist::all();
            return PlaylistTable::getPlaylistTable($playlists);
        }

    }
    public function store(StorePlaylistRequest $request)
    {
        $data = $request->safe();
        try {
            return DB::transaction(function () use ($data) {
                $validDevicesCount = Device::whereIn('id', $data->devices)
                    ->whereIn('establishment_id', $data->establishments_ids)
                    ->count();
                if ($validDevicesCount !== count($data->devices)) {
                    return response()->json(['error' => __('employee::responses.something_wrong_happened')], 422);
                }

                $days_settings = [
                    'days_settings_option' => $data->days_settings,
                    'start_time' => $data->start_time,
                    'start_date_time' => $data->start_date_time,
                    'days_of_the_weak' => $data->days_of_the_weak,
                    'screen_orientation' => $data->screen_orientation,
                    'transition_seconds' => (int) $data->transition_seconds,
                ];
                $playlist = Playlist::create(['name' => $data->name, 'days_settings' => $days_settings]);

                foreach ($data->selected_promos as $index => $promoId) {
                    $playlist->promos()->attach($promoId, [
                        'created_at' => now()->addSeconds($index),
                        'updated_at' => now()->addSeconds($index)
                    ]);
                }
                $playlist->establishments()->sync($data->establishments_ids);
                $playlist->devices()->sync($data->devices);

                return response()->json(['message' => __('employee::responses.operation_success')]);
            });
        } catch (\Throwable $e) {
            \Log::error('playlist creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function show(Playlist $playlist)
    {
        return response()->json([
            'data' => [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'days_settings' => $playlist->days_settings ?? [],
                'establishments_ids' => $playlist->establishments()->pluck('est_establishments.id')->toArray(),
                'devices' => $playlist->devices()->pluck('screen_devices.id')->toArray(),
                'selected_promos' => $playlist->promos()->pluck('screen_promos.id')->toArray(),
            ]
        ]);
    }

    public function update(StorePlaylistRequest $request, Playlist $playlist)
    {
        $data = $request->safe();
        try {
            return DB::transaction(function () use ($data, $playlist) {
                $validDevicesCount = Device::whereIn('id', $data->devices)
                    ->whereIn('establishment_id', $data->establishments_ids)
                    ->count();
                if ($validDevicesCount !== count($data->devices)) {
                    return response()->json(['error' => __('employee::responses.something_wrong_happened')], 422);
                }

                $days_settings = [
                    'days_settings_option' => $data->days_settings,
                    'start_time' => $data->start_time,
                    'start_date_time' => $data->start_date_time,
                    'days_of_the_weak' => $data->days_of_the_weak,
                    'screen_orientation' => $data->screen_orientation,
                    'transition_seconds' => (int) $data->transition_seconds,
                ];
                $playlist->update(['name' => $data->name, 'days_settings' => $days_settings]);

                $playlist->promos()->detach();
                foreach ($data->selected_promos as $index => $promoId) {
                    $playlist->promos()->attach($promoId, [
                        'created_at' => now()->addSeconds($index),
                        'updated_at' => now()->addSeconds($index)
                    ]);
                }
                $playlist->establishments()->sync($data->establishments_ids);
                $playlist->devices()->sync($data->devices);

                return response()->json(['message' => __('employee::responses.updated_successfully', ['name' => __('screen::general.playlist')])]);
            });
        } catch (\Throwable $e) {
            \Log::error('playlist update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }

    public function getPlaylistPromos(Playlist $playlist)
    {
         $promos = $playlist->promos->select('path');
         return response()->json(['data' => $promos]);
    }


    public function destroy(Playlist $playlist)
    {
        $delete = $playlist->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('screen::fields.promo')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
