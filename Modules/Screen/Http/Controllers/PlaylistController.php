<?php

namespace Modules\Screen\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Modules\Screen\Classes\PlaylistTable;
use Modules\Screen\Http\Requests\StorePlaylistRequest;
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
            DB::transaction(function () use ($data) {
                $days_settings = [
                    'days_settings_option' => $data->days_settings,
                    'start_time' => $data->start_time,
                    'start_date_time' => $data->start_date_time,
                    'days_of_the_weak' => $data->days_of_the_weak,
                    'screen_orientation' => $data->screen_orientation,
                ];
                $playlist = Playlist::create(['name' => $data->name, 'days_settings' => $days_settings]);

                $playlist->promos()->sync($data->selected_promos);
                $playlist->establishments()->sync($data->establishments_ids);
                $playlist->devices()->sync($data->devices);

                return response()->json(['message' => __('employee::responses.operation_success')]);
            });
        } catch (\Throwable $e) {
            \Log::error('coupons creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
