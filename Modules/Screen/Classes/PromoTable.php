<?php

namespace Modules\Screen\Classes;

use Yajra\DataTables\Facades\DataTables;


class PromoTable
{
    public static function commonHtml($promo, $width = '')
    {
        $html = '<div class="d-flex ' . $width . ' ' . 'gap-3 m-5 border border-gray-300 rounded px-5 py-3">
                <div style="width: 300px;">
                    <div class="my-auto">';
        $html .= '<div class="rounded" style="position: relative; width: 300px; aspect-ratio: 16 / 9; background-color: black; overflow: hidden;">
                            <img src="' . asset('storage/tenant' . tenancy()->tenant->id . '/' . $promo->thumbnail) . '"
                                 alt="' . $promo->name . '"
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
                                <span class="text-gray-700 fw-bold">' . $promo->name . '</span>
                                <span class="text-gray-700 fw-bolder">' . $type . '</span>
                            </div>
                            <div class="d-flex flex-column gap-5 py-5 justify-content-center" style="width: 150px;">
                                    <a href="#" 
                                        class="btn btn-primary px-6 py-2 rounded promo-preview-btn" 
                                        data-id="' . $promo->id . '"
                                        data-type="' . $type . '"
                                        data-path="' . asset('storage/tenant' . tenancy()->tenant->id . '/' . $promo->path) . '">' . __('screen::general.preview') . '</a>
                                <a href="#" class="btn btn-danger px-6 py-2 rounded promo-delete-btn" data-id="' . $promo->id . '">' . __('screen::general.delete') . '</a>
                                <a href="#" class="btn btn-secondary px-6 py-2 rounded promo-rename-btn" data-id="' . $promo->id . '" data-name="' . $promo->name . '" >' . __('screen::general.rename') . '</a>
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
                        <span class="text-gray-700 fw-bold">' . $promo->name . '</span>
                        <span class="text-gray-700 fw-bolder">' . $type . '</span>
                    </div>
                </div>';
                return $html;
            })
            ->rawColumns(['main'])
            ->make(true);
    }
}
