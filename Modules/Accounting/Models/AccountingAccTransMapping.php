<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Support\AccountingNote;
use Modules\Employee\Models\Employee;

// use Modules\Accounting\Database\Factories\AccountingAccTransMappingFactory;

class AccountingAccTransMapping extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    public function setNoteAttribute(mixed $value): void
    {
        $this->attributes['note'] = AccountingNote::normalizeForStorage($value);
    }

    public function added_by()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(AccountingAccountsTransaction::class, 'acc_trans_mapping_id');
    }

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public function cost_center()
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }
}
