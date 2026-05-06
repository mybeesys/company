<?php

namespace Modules\Screen\Classes;

use Yajra\DataTables\Facades\DataTables;

class PromoTable
{
    public static function commonHtml($promo, $width = '')
    {
        $thumbnailUrl = $promo->thumbnail
            ? asset('storage/tenant'.tenancy()->tenant->id.'/'.$promo->thumbnail)
            : asset('assets/media/icons/duotune/files/fil004.svg');

        $html = '<div class="d-flex '.$width.' gap-3 m-5 border border-gray-300 rounded px-5 py-3">
                <div style="width: 300px;">
                    <div class="my-auto">';
        $html .= '<div class="rounded" style="position: relative; width: 300px; aspect-ratio: 16 / 9; background-color: black; overflow: hidden;">
                            <img src="'.$thumbnailUrl.'"
                                 alt="'.$promo->name.'"
                                 style="width: 100%; height: 100%; object-fit: contain; background-color: black;">
                        </div>';

        return $html;
    }

    // <canvas id="canvas" class="rounded w-100 my-auto"></canvas>

    public static function getPromoIndexTable($promos)
    {
        return DataTables::of($promos)
            ->addColumn('main', function ($promo) {
                $extension = strtolower(pathinfo($promo->path, PATHINFO_EXTENSION)); // Get the file extension in lowercase
                $type = in_array($extension, ['mp4'])
                    ? "video/$extension"
                    : (in_array($extension, ['jpg', 'jpeg', 'png'])
                        ? "image/$extension"
                        : 'unknown');
                $html = self::commonHtml($promo, 'w-800px');
                $html .= '</div>
                            </div>
                            <div class="px-5 py-3 d-flex flex-column gap-2" style="width: 275px;">
                                <span class="text-gray-700 fw-bold">'.$promo->name.'</span>
                                <span class="text-gray-700 fw-bolder">'.$type.'</span>
                            </div>
                            <div class="d-flex flex-row flex-wrap gap-2 align-items-center justify-content-center py-4 px-2" style="min-width: 7.5rem;">
                                    <a href="#"
                                        class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light-primary promo-preview-btn"
                                        data-id="'.$promo->id.'"
                                        data-type="'.$type.'"
                                        data-path="'.asset('storage/tenant'.tenancy()->tenant->id.'/'.$promo->path).'"
                                        title="'.e(__('screen::general.preview')).'"
                                        aria-label="'.e(__('screen::general.preview')).'"><i class="fas fa-eye fs-6 text-gray-600"></i></a>
                                <a href="#" class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light-danger promo-delete-btn" data-id="'.$promo->id.'"
                                        title="'.e(__('screen::general.delete')).'"
                                        aria-label="'.e(__('screen::general.delete')).'"><i class="fas fa-trash-alt fs-6 text-gray-600"></i></a>
                                <a href="#" class="btn btn-icon btn-sm btn-light border border-gray-300 rounded btn-active-light promo-rename-btn" data-id="'.$promo->id.'" data-name="'.e($promo->name).'"
                                        title="'.e(__('screen::general.rename')).'"
                                        aria-label="'.e(__('screen::general.rename')).'"><i class="fas fa-pen fs-6 text-gray-600"></i></a>
                            </div>
                        </div>';

                return $html;
            })
            ->rawColumns(['main'])
            ->make(true);
    }

    public static function getPlaylistPromoTable($promos)
    {
        return DataTables::of($promos)
            ->addColumn('DT_RowId', function ($promo) {
                return $promo->id;
            })
            ->addColumn('main', function ($promo) {
                $extension = strtolower(pathinfo($promo->path, PATHINFO_EXTENSION));
                $type = in_array($extension, ['mp4'])
                    ? "video/$extension"
                    : (in_array($extension, ['jpg', 'jpeg', 'png'])
                        ? "image/$extension"
                        : 'unknown');

                $html = '<div class="d-flex align-items-center">';
                $html .= self::commonHtml($promo, $extension);
                $html .= '</div>
                    </div>
                    <div class="px-5 py-3 d-flex flex-column gap-2" style="width: 275px;">
                        <span class="text-gray-700 fw-bold">'.$promo->name.'</span>
                        <span class="text-gray-700 fw-bolder">'.$type.'</span>
                    </div>
                </div>';

                return $html;
            })
            ->rawColumns(['main'])
            ->make(true);
    }
}
