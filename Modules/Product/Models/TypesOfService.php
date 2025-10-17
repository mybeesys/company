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
    $editUrl = url("/type-service-edit/{$row->id}");
    $deleteUrl = url("/type-service-destroy/{$row->id}");

    $actions = '
        <div class="d-flex justify-content-center gap-2">
          <a href="' . $editUrl . '"
               class="btn btn-icon btn-light btn-sm"
               title="' . __('messages.edit') . '">
                <i class="ki-outline ki-pencil fs-4"></i>
            </a>
        <a href="' . $deleteUrl . '"
               class="btn btn-icon btn-light btn-sm"
               title="' . __('general::lang.delete') . '">
                <i class="ki-outline ki-trash fs-4"></i>
            </a>

        </div>
    ';

    return $actions;
})

        ->rawColumns(['actions'])
        ->make(true);
    }
}
