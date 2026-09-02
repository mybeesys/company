<?php

namespace Modules\Accounting\classes;

use Modules\Accounting\Support\AccountingAccess;
use Modules\Accounting\Support\AccountingPermissions;
use Yajra\DataTables\Facades\DataTables;

class AccountingAccTransMappingTable
{
    public static function getAccTransMappingColumns()
    {
        return [

            ['class' => 'text-start min-w-150px px-3', 'name' => 'operation_date'],
            ['class' => 'text-start min-w-140px px-3', 'name' => 'type'],
            ['class' => 'text-start min-w-150px px-3', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-160px px-3', 'name' => 'created_by'],
            ['class' => 'text-end min-w-120px px-3', 'name' => 'total_amount'],
            ['class' => 'text-start min-w-220px px-3', 'name' => 'note'],

        ];
    }

    public static function getAccTransMappingTable($acc_trans_mapping)
    {
        return DataTables::of($acc_trans_mapping)
            ->filter(function ($query) {
                $search = (string) (request('search.value') ?? '');
                $search = trim($search);
                if ($search === '') {
                    return;
                }
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('ref_no', 'like', $like)
                        ->orWhere('note', 'like', $like)
                        ->orWhere('type', 'like', $like)
                        ->orWhere('operation_date', 'like', $like)
                        ->orWhereHas('added_by', function ($q2) use ($like) {
                            $q2->where('name', 'like', $like);
                        })
                        ->orWhereHas('transactions', function ($q3) use ($like) {
                            $q3->where('sub_type', 'like', $like);
                        })
                        ->orWhereHas('transactions', function ($q4) use ($like) {
                            $q4->where('note', 'like', $like);
                        });
                });
            })
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('created_by', function ($row) {

                return $row->added_by->name;
            })

            ->editColumn('type', function ($row) {
                return __('accounting::lang.'.$row->type);
            })

            ->addColumn('total_amount', function ($row) {
                $sum = $row->transactions
                    ->where('type', 'debit')
                    ->sum(fn ($line) => (float) $line->amount);

                return number_format($sum, 2);
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">'.__('employee::fields.actions').'<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                    $actions .= '<div class="menu-item px-3">
                            <a href="'.url("/journal-entry-show/{$row->id}").'" class="menu-link px-3">'.__('employee::fields.show').'</a>
                        </div>';

                    if (AccountingAccess::can(AccountingPermissions::JOURNAL_UPDATE)) {
                        $actions .= '<div class="menu-item px-3">
                            <a href="'.url("/journal-entry-edit/{$row->id}").'" class="menu-link px-3">'.__('employee::fields.edit').'</a>
                        </div>';
                    }

                    if ($row->is_manual && AccountingAccess::can(AccountingPermissions::JOURNAL_DUPLICATE)) {
                        $actions .= '<div class="menu-item px-3">
                        <a href="'.url("/journal-entry-duplication/{$row->id}").'" class="menu-link px-3">'.__('accounting::fields.duplication').'</a>
                    </div>';
                    }

                    if (AccountingAccess::can(AccountingPermissions::JOURNAL_PRINT)) {
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.url("/journal-entry-print/{$row->id}").'" class="menu-link px-3">'.__('accounting::fields.print').'</a>
                </div>';
                    }

                    if (AccountingAccess::can(AccountingPermissions::JOURNAL_DELETE)) {
                        $actions .= '<div class="menu-item px-3">
                                    <a class="menu-link px-3 delete-btn" href="'.url("/journal-entry-destroy/{$row->id}").'" data-id="'.$row->id.'"  data-ref_no="'.$row->ref_no.'">'.__('employee::fields.delete').'</a>
                                </div>';
                    }

                    return $actions;
                }
            )

            ->rawColumns(['actions', 'id'])
            ->make(true);
    }
}
