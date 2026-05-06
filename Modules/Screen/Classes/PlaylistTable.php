<?php

namespace Modules\Screen\Classes;

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
                            <img src="'.asset('storage/tenant'.tenancy()->tenant->id.'/'.$playlist->promos?->first()?->thumbnail).'"
                                 alt="'.$playlist->name.'"
                                 style="width: 100%; height: 100%; object-fit: contain; background-color: black;">
                        </div>';
                $html .= '</div>
                    </div>
                    <div class="px-5 py-3 d-flex flex-column gap-2" style="width: 275px;">
                        <span class="text-gray-700 fw-bold">'.$playlist->name.'</span>
                        <span class="text-gray-700 fw-bolder">'.__('screen::general.'.$playlist->days_settings['days_settings_option']).'</span>
                        <span class="text-gray-600">'.__('screen::general.transition_speed_seconds').': '.(int) ($playlist->days_settings['transition_seconds'] ?? 5).'</span>
                    </div>
                     <div class="d-flex flex-row flex-wrap gap-2 align-items-center justify-content-center py-4 px-2" style="min-width: 7.5rem;">
                                    <a href="#"
                                        class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light-primary playlist-preview-btn"
                                        data-id="'.$playlist->id.'"
                                        title="'.e(__('screen::general.preview')).'"
                                        aria-label="'.e(__('screen::general.preview')).'"><i class="fas fa-eye fs-6 text-gray-600"></i></a>
                                <a href="#" class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light-primary playlist-edit-btn" data-id="'.$playlist->id.'"
                                        title="'.e(__('screen::general.edit')).'"
                                        aria-label="'.e(__('screen::general.edit')).'"><i class="fas fa-pen-to-square fs-6 text-gray-600"></i></a>
                                <a href="#" class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light-danger playlist-delete-btn" data-id="'.$playlist->id.'"
                                        title="'.e(__('screen::general.delete')).'"
                                        aria-label="'.e(__('screen::general.delete')).'"><i class="fas fa-trash-alt fs-6 text-gray-600"></i></a>
                    </div>
                </div>';

                return $html;
            })
            ->rawColumns(['main'])
            ->make(true);
    }
}
