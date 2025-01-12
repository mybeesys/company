<?php


namespace Modules\Employee\Classes;
use Cache;
use Carbon\Carbon;
use Modules\Employee\Services\PayrollService;
use Yajra\DataTables\DataTables;

class PayrollGroupTable
{

    public function __construct(private PayrollService $payrollService)
    {
    }

    public static function getPayrollGroupColumns()
    {
        return [
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "name"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "date"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "state"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "payment_status"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "gross_total"],
            // ["class" => "text-start min-w-125px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "net_total"],
        ];
    }

    public static function getPayrollGroupTable($payroll_groups)
    {
        return DataTables::of($payroll_groups)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                 {$row->id} 
                        </div>";
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $row->state === 'draft' ?
                        $actions = '
                    <div class="justify-content-center d-flex text-nowrap"> 
                <a class="btn btn-icon btn-bg-light btn-active-color-primary h-35px w-100 px-2 confirm-btn me-1" data-id="' . $row->id . '">
					<span class="fs-6" >' . __("employee::general.confirm_payroll_group") . '</span>
				</a>                    
                <a class="btn btn-icon btn-bg-light btn-active-color-primary min-w-35px h-35px delete-btn me-1" data-id="' . $row->id . '">
					<i class="ki-outline ki-trash fs-3"></i>
				</a>      
                <a href="' . url("schedule/payroll-group/{$row->id}/edit") . '" class="btn btn-icon btn-bg-light btn-active-color-primary min-w-35px h-35px me-1 edit-btn" data-id="' . $row->id . '" >
					<i class="ki-outline ki-pencil fs-2"></i>
				</a>' : $actions = '';

                    $actions .= '                
                </div>';
                    return $actions;
                }
            )
            ->rawColumns(['actions', 'id'])
            ->make(true);
    }
}