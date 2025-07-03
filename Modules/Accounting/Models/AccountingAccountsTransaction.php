<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\App;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Yajra\DataTables\Facades\DataTables;

// use Modules\Accounting\Database\Factories\AccountingAccountsTransactionFactory;

class AccountingAccountsTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'accounting_accounts_transactions';

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public function accTransMapping()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'acc_trans_mapping_id');
    }

    public function transactionPayments()
    {
        return $this->belongsTo(TransactionPayments::class, 'transaction_payment_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }


    public function costCenter()
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }


    public static function getAccountTransactionType($tansaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchases' => 'debit',
            'expense' => 'debit',
            'purchase_return' => 'credit',
            'sell_return' => 'debit',
            'payroll' => 'debit',
            'expense_refund' => 'credit',
            'hms_booking' => 'credit',
        ];

        return $account_transaction_types[$tansaction_type];
    }


    public static function getReceiptsColumns()
    {
        return [

            ["class" => "text-start min-w-150px ", "name" => "account"],
            ["class" => "text-start min-w-150px ", "name" => "debit/credit"],
            ["class" => "text-start min-w-150px", "name" => "operation_date"],
            ["class" => "text-start min-w-80px ", "name" => "amount"],
            ["class" => "text-start min-w-150px  ", "name" => "created_by"],
            ["class" => "text-start min-w-150px ", "name" => "note"],
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
            ->editColumn('account', function ($row) {
                return $row->account->gl_code . ' - ' . (App::getLocale() == 'en' ? $row->account->name_en : $row->account->name_ar);
            })
            ->editColumn('operation_date', function ($row) {
                return  $row->operation_date ?? '--';
            })
            ->editColumn('type', function ($row) {
                return  __('accounting::lang.' . $row->type);
            })

            ->editColumn('amount', function ($row) {
                return $row->amount;
            })
            ->editColumn('created_by', function ($row) {
                return  $row->createdBy->name;
            })

            ->editColumn('note', function ($row) {
                return  $row->note ?? '--';
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

            ->rawColumns(['actions', 'account','type', 'id'])
            ->make(true);
    }
}
