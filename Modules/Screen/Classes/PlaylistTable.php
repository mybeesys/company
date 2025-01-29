<?php

namespace Modules\Screen\Classes;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Yajra\DataTables\Facades\DataTables;


class PlaylistTable
{

    // <canvas id="canvas" class="rounded w-100 my-auto"></canvas>

    public static function getPlaylistTable($playlists)
    {
        return DataTables::of($playlists)
            ->addColumn('main', function ($playlist) {
                $html = '
                <div class="d-flex align-items-center">
                <div class="d-flex gap-3 m-5 border border-gray-300 rounded px-5 py-3">
                <div style="width: 300px;">
                    <div class="my-auto">';
                $html .= '<div class="rounded" style="position: relative; width: 300px; aspect-ratio: 16 / 9; background-color: black; overflow: hidden;">
                            <img src="' . asset('storage/tenant' . tenancy()->tenant->id . '/' . $playlist->promos?->first()?->thumbnail) . '"
                                 alt="' . $playlist->name . '"
                                 style="width: 100%; height: 100%; object-fit: contain; background-color: black;">
                        </div>';
                $html .= '</div>
                    </div>
                    <div class="px-5 py-3 d-flex flex-column gap-2" style="width: 275px;">
                        <span class="text-gray-700 fw-bold">' . $playlist->name . '</span>
                        <span class="text-gray-700 fw-bolder">' . $playlist->days_settings['days_settings_option'] . '</span>
                    </div>
                     <div class="d-flex flex-column gap-5 py-5 justify-content-center" style="width: 150px;">
                                    <a href="#" 
                                        class="btn btn-primary px-6 py-2 rounded playlist-preview-btn" 
                                        data-id="' . $playlist->id . '">' . __('screen::general.preview') . '</a>
                                <a href="#" class="btn btn-danger px-6 py-2 rounded playlist-delete-btn" data-id="' . $playlist->id . '">' . __('screen::general.delete') . '</a>
                    </div>
                </div>';
                return $html;
            })
            ->rawColumns(['main'])
            ->make(true);
    }
}
