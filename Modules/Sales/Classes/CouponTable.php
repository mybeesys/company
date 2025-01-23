<?php

namespace Modules\Sales\Classes;

use Yajra\DataTables\Facades\DataTables;


class CouponTable
{
    public static function getCouponColumns()
    {
        return [
            ["class" => "text-start px-3", "name" => "name"],
            ["class" => "text-start min-w-250px px-3", "name" => "code"],
            ["class" => "text-start min-w-100px px-3", "name" => "value_type"],
            ["class" => "text-start min-w-100px px-3", "name" => "value"],
            ["class" => "text-start min-w-100px px-3", "name" => "start_date"],
            ["class" => "text-start min-w-100px px-3", "name" => "end_date"],
            ["class" => "text-start min-w-100px px-3", "name" => "discount_apply_to"],
        ];
    }

    public static function getCouponTable($coupons)
    {
        return DataTables::of($coupons)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                         {$row->id} 
                </div>";
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<div class="justify-content-center d-flex">';
                    if (!$row->deleted_at) {
                        $actions .= '
                        <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px coupon-delete-btn me-1" data-id="' . $row->id . '">
                            <i class="ki-outline ki-trash fs-3"></i>
                        </a>';
                    } else {
                        $actions .= '
                        <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px coupon-delete-btn me-1" data-deleted="' . $row->deleted_at . '" data-id="' . $row->id . '">
                            <i class="ki-outline ki-trash fs-3"></i>
                        </a>

                         <a class="btn btn-icon btn-bg-light btn-active-color-primary w-75px h-35px coupon-restore-btn me-1 text-dark" data-id="' . $row->id . '">
                            '. __('employee::fields.restore') .'
                        </a>
                        ';
                    }

                    if (!$row->deleted_at) {
                        $actions .= '
                        <a class="btn btn-icon btn-bg-light btn-active-color-primary w-35px h-35px me-1 coupon-edit-btn" data-id="' . $row->id . '">
                            <i class="ki-outline ki-pencil fs-2"></i>
                        </a>';
                    }
                    $actions .= '</div>';
                    return $actions;
                }
            )
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'is_active', 'id'])
            ->make(true);
    }
}
