<?php

namespace Modules\Establishment\Classes;

use Yajra\DataTables\DataTables;

use Illuminate\Support\Facades\Log;

class EstablishmentTable
{
    public static function getEstablishmentColumns()
    {
        return [
            ["class" => "text-start min-w-200px px-3", "name" => "name"],
            ["class" => "text-start min-w-200px px-3", "name" => "is_main_establishment"],
            ["class" => "text-start min-w-200px px-3", "name" => "main_establishment"],
            ["class" => "text-start min-w-150px px-3", "name" => "contact_details"],
            ["class" => "text-start min-w-100px px-3", "name" => "status"],
        ];
    }

    public static function getDeviceColumns()
    {
        return [
            ["class" => "text-start min-w-200px px-3", "name" => "device_name"],
            ["class" => "text-start min-w-200px px-3", "name" => "device_type"],
            ["class" => "text-start min-w-200px px-3", "name" => "ref"],
            ["class" => "text-start min-w-200px px-3", "name" => "establishment"],
        ];
    }


    public static function getEstablishmentTable($establishments)
    {
        return DataTables::of($establishments)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->editColumn('parent_id', function ($row) {
                return $row?->main?->{get_name_by_lang()};
            })
            ->editColumn('is_main', function ($row) {
                return $row->is_main
                    ? '<div class="badge badge-light-success">True</div>'
                    : '<div class="badge badge-light-danger">False</div>';
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">';
                    $row->deleted_at ?? $actions .= '<div class="menu-item px-3">
                        <a href="' . url("/establishment/{$row->id}/edit") . '" class="menu-link px-3">' . __('establishment::fields.edit') . '</a>
                    </div></div>';
                    return $actions;
                }
            )
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">' . __("establishment::fields.active") . '</div>'
                    : '<div class="badge badge-light-danger">' . __("establishment::fields.inActive") . '</div>';
            })
            ->rawColumns(['actions', 'is_active', 'id', 'is_main'])
            ->make(true);
    }
    public static function getDeviceTable($establishments)
    {
        return DataTables::of($establishments)
            ->editColumn('id', function ($row) {
                return $row->id;
            })
            ->editColumn('device_name', function ($row) {
                return $row->name;
            })
            ->editColumn('device_type', function ($row) {
                $translations = [
                    'kitchen screen' => '<span style="color: blue;">شاشة مطبخ</span>',
                    'cashier' => '<span style="color: green;">الكاشير</span>',
                    'waiters' => '<span style="color: orange;">جهاز الويترز</span>',
                ];

                return app()->getLocale() === 'ar' ? ($translations[$row->type] ?? $row->type) : $row->type;
            })
            ->editColumn('ref', function ($row) {
                return $row->ref;
            })
            ->editColumn('establishment', function ($row) {
                return app()->getLocale() === 'ar' ? $row->establishment->name : $row->establishment->name_en;
            })
            ->addColumn('actions', function ($row) {
                return '
        <button class="btn btn-warning" onclick="deleteDevice(' . $row->id . ')">
            ' . __('establishment::fields.delete') . '
        </button>
    ';
            })
            ->rawColumns(['id', 'device_name', 'device_type', 'ref', 'establishment', 'actions'])
            ->make(true);
    }
}
