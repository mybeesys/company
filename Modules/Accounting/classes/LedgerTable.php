<?php


namespace Modules\Accounting\classes;

use Illuminate\Support\Facades\App;
use Yajra\DataTables\Facades\DataTables;

class LedgerTable
{

    public static function getAccTransMappingColumns()
    {
        return [

            ["class" => "text-start min-w-250px px-3", "name" => "transaction_number"],
            ["class" => "text-start min-w-200px px-3", "name" => "operation_date"],
            ["class" => "text-start min-w-200px px-3", "name" => "transaction"],
            ["class" => "text-start min-w-200px text-nowrap px-3", "name" => "cost_center"],
            ["class" => "text-start min-w-200px text-nowrap px-3", "name" => "added_by"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "debit"],
            ["class" => "text-start min-w-150px text-nowrap px-3", "name" => "credit"],

        ];
    }

    public static function getAccTransMappingTable($acc_trans_mapping)
    {
        return DataTables::of($acc_trans_mapping)
            ->editColumn('created_by', function ($row) {
                return $row->createdBy->name;
            })

            ->editColumn('sub_type', function ($row) {
                return __('accounting::lang.' . $row->sub_type);
            })
            ->editColumn('transaction_number', function ($row) {
                return $row->accTransMapping->ref_no;
            })

            ->editColumn('operation_date', function ($row) {
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $row->operation_date)->format('d/m/Y h:i A');
            })
            ->editColumn('debit', function ($row) {
                return $row->type == 'debit' ? $row->amount : '--';
            })
            ->editColumn('credit', function ($row) {
                return $row->type == 'credit' ? $row->amount : '--';
            })
            ->editColumn('cost_center', function ($row) {
                return $row->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $row->costCenter->name_ar : $row->costCenter->name_en);
            })

            ->rawColumns(['debit', 'credit', 'operation_date', 'transaction_number', 'cost_center', 'sub_type', 'created_by'])

            ->make(true);
    }
}
