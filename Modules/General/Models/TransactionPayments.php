<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounting\Models\AccountingAccount;
use Modules\ClientsAndSuppliers\Models\Contact;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TransactionPaymentsFactory;

class TransactionPayments extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function client()
    {
        return $this->belongsTo(Contact::class, 'payment_for');
    }



    public static function getReceiptsColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "ref_no"],
            ["class" => "text-start min-w-150px", "name" => "paid_on"],
            ["class" => "text-start min-w-150px  ", "name" => "transaction_ref_no"],
            ["class" => "text-start min-w-150px ", "name" => "client"],
            ["class" => "text-start min-w-80px ", "name" => "piad_amount"],
        ];
    }

    public static function getReceiptsTable($transactions)
    {

        return DataTables::of($transactions)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('payment_ref_no', function ($row) {
                return $row->payment_ref_no;
            })
            ->editColumn('paid_on', function ($row) {
                return  $row->paid_on ?? '--';
            })
            ->editColumn('transaction_ref_no', function ($row) {
                return
                    '<a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6" href="' . url("/transaction-show/{$row->transaction->id}") . '">' . $row->transaction->ref_no . '</a>';
            })
            ->editColumn('client', function ($row) {
                return  $row->client->name ?? '--';
            })

            ->editColumn('amount', function ($row) {
                return  $row->amount ?? '0.00';
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">' . __('employee::fields.actions') . '<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

                    $actions .= '<div class="menu-item px-3">
                <a href="#" class="menu-link px-3">' . __('general.print') . '</a>
            </div>';


                    return $actions;
                }
            )

            ->rawColumns(['actions', 'transaction_ref_no', 'client', 'id'])
            ->make(true);
    }
}
