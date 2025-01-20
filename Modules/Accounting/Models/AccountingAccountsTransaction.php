<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Transaction;

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
}
