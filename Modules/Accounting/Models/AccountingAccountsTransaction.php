<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    // public function transaction()
    // {
    //     return $this->belongsTo(Transaction::class, 'transaction_id');
    // }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    // public function costCenter()
    // {
    //     return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    // }
}