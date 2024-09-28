<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountingAccountFactory;

class AccountingAccount extends Model 
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    protected $table = 'accounting_accounts';
    // protected $connection = 'tenant';

    public function scopeForTenant($query)
    {
        if (tenant()) {
            return $query->on(tenant()->database);
        }
        return $query;
    }

    public function child_accounts()
    {
        return $this->hasMany(AccountingAccount::class, 'parent_account_id');
    }

    public function account_sub_type()
    {
        return $this->belongsTo(AccountingAccountTypes::class, 'account_sub_type_id');
    }

    public function detail_type()
    {
        return $this->belongsTo(AccountingAccountTypes::class, 'detail_type_id');
    }
}