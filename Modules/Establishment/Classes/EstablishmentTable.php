<?php

namespace Modules\Establishment\Classes;

use Yajra\DataTables\DataTables;

class EstablishmentTable
{
    public static function getEstablishmentColumns()
    {
        return [
            ['class' => 'text-start min-w-200px px-3', 'name' => 'name'],
            ['class' => 'text-start min-w-200px px-3', 'name' => 'is_main_establishment'],
            ['class' => 'text-start min-w-200px px-3', 'name' => 'main_establishment'],
            ['class' => 'text-start min-w-150px px-3', 'name' => 'contact_details'],
            ['class' => 'text-start min-w-100px px-3', 'name' => 'status'],
        ];
    }

    public static function getDeviceColumns()
    {
        return [
            ['class' => 'text-start min-w-200px px-3', 'name' => 'device_name'],
            ['class' => 'text-start min-w-200px px-3', 'name' => 'device_type'],
            ['class' => 'text-start min-w-200px px-3', 'name' => 'ref'],
            ['class' => 'text-start min-w-200px px-3', 'name' => 'establishment'],
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
                    ? '<div class="badge badge-light-success">'.__('establishment::fields.yes').'</div>'
                    : '<div class="badge badge-light-danger">'.__('establishment::fields.no').'</div>';
            })
            ->addColumn(
                'actions',
                function ($row) {
                    if ($row->deleted_at) {
                        return '';
                    }

                    $editUrl = url("/establishment/{$row->id}/edit");
                    $label = e(__('establishment::general.branch_settings'));

                    return '<a href="'.$editUrl.'" class="btn btn-sm btn-light-primary btn-flex btn-center">'
                        .'<i class="ki-outline ki-setting-2 fs-5 text-primary me-1"></i>'
                        .'<span class="text-primary">'.$label.'</span>'
                        .'</a>';
                }
            )
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<div class="badge badge-light-success">'.__('establishment::fields.active').'</div>'
                    : '<div class="badge badge-light-danger">'.__('establishment::fields.inActive').'</div>';
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
        <button class="btn btn-warning" onclick="deleteDevice('.$row->id.')">
            '.__('establishment::fields.delete').'
        </button>
    ';
            })
            ->rawColumns(['id', 'device_name', 'device_type', 'ref', 'establishment', 'actions'])
            ->make(true);
    }
}
