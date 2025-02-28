<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\App;
use Locale;
use Yajra\DataTables\Facades\DataTables;

// use Modules\Product\Database\Factories\TypesOfServiceFactory;

class TypesOfService extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public static function forDropdown($business_id)
    {
        $types_of_service = TypesOfService::where('business_id', $business_id)
            ->pluck('name', 'id');

        return $types_of_service;
    }

    public static function getTypesOfServiceColumns(){
        return [
            ["class" => "text-start min-w-150px", "name" => "name"],
            ["class" => "text-start min-w-200px", "name" => "description"],
            ["class" => "text-start min-w-100px", "name" => "packing_charge"],
        ];
    }

    public static function getTypesOfServiceTable($typesOfService){
        return DataTables::of($typesOfService)
        ->editColumn('name', function ($row) {
            return App::getLocale()=='ar'?$row->name_ar: $row->name_en ;
        })
        ->editColumn('description', function ($row) {
            return $row->description ?? '--';
        })
        ->editColumn('packing_charge', function ($row) {
            return $row->packing_charge ?
            number_format($row->packing_charge, 4) . ($row->packing_charge_type == 'percent' ? '%' : '')
            : '0.0000';})
        ->addColumn('actions', function ($row) {
            $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

            // $actions .= '<div class="menu-item px-3">
            //     <a href="' . url("/product-show/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.show') . '</a>
            // </div>';

            $actions .= '<div class="menu-item px-3">
                <a href="' . url("/type-service-edit/{$row->id}") . '" class="menu-link px-3">' . __('messages.edit') . '</a>
            </div>';

            // $actions .= '<div class="menu-item px-3">
            //     <a href="' . url("/product-delete/{$row->id}") . '" class="menu-link px-3">' . __('general::lang.delete') . '</a>
            // </div>';

            return $actions;
        })
        ->rawColumns(['actions'])
        ->make(true);
    }
}