<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Accounting\Models\AccountingAccount;

// use Modules\General\Database\Factories\TransactionPaymentsFactory;

class TransactionPayments extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }
}
