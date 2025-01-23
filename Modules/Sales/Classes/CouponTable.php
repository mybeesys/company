<?php

namespace Modules\Sales\Classes;

use Yajra\DataTables\Facades\DataTables;


class CouponTable
{
    public static function getCouponColumns()
    {
        return [
            ["class" => "text-start px-3 align-middle", "name" => "name"],
            ["class" => "text-start min-w-250px px-3 align-middle", "name" => "code"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "discount_apply_to"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "coupon_count"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "person_use_time_count"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "value_type"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "value"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "start_date"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "end_date"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "apply_to_clients_groups"],
            ["class" => "text-start min-w-100px px-3 align-middle", "name" => "is_active"],
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

                         <a class="btn btn-icon btn-bg-light w-75px h-35px coupon-restore-btn me-1 text-gray-600 hover-primary" data-id="' . $row->id . '">
                            ' . __('employee::fields.restore') . '
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
            ->editColumn('apply_to_clients_groups', function ($row) {
                return $row->apply_to_clients_groups
                    ? '<div class="badge badge-light-success">True</div>'
                    : '<div class="badge badge-light-danger">False</div>';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">True</div>'
                    : '<div class="badge badge-light-danger">False</div>';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">' . __("employee::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("employee::fields.inActive") . '</div>';
            })
            ->editColumn('value_type', function ($row) {
                return $row->value_type === 'fixed'
                    ? '<div class="badge badge-light-primary">' . __("sales::general.fixed") . '</div>'
                    : '<div class="badge badge-light-success">' . __("sales::general.percent") . '</div>';
            })
            ->editColumn('discount_apply_to', function ($row) {
                return $row->discount_apply_to === 'all'
                    ? '<div class="badge badge-light-primary">' . __("sales::fields.all") . '</div>'
                    : ($row->discount_apply_to === 'product' ? '<div class="badge badge-light-success">' . __("sales::fields.product") . '</div>' : '<div class="badge badge-light-info">' . __("sales::fields.category") . '</div>');
            })
            ->editColumn('person_use_time_count', function ($row) {
                return '<div class="badge badge-light-secondary text-gray-600 fs-5">' . $row->person_use_time_count . '</div>';
            })
            ->editColumn('coupon_count', function ($row) {
                return '<div class="badge badge-light-secondary text-gray-600 fs-5">' . $row->coupon_count . '</div>';
            })
            ->rawColumns(['actions', 'is_active', 'id', 'value_type', 'discount_apply_to', 'apply_to_clients_groups', 'coupon_count', 'person_use_time_count'])
            ->make(true);
    }
}
