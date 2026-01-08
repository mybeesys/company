<?php

namespace Modules\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\FavoriteBills;
use Modules\General\Models\PrefixSetting;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;

// use Modules\Reservation\Database\Factories\TableOrdersFactory;

class TableOrders extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    public function getIsFavoriteAttribute()
    {
        return $this->favorites()->where('user_id', Auth::user()->id)->exists();
    }

    public function favorites()
    {
        return $this->hasMany(FavoriteBills::class);
    }


    public function client()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function sell_lines()
    {
        return $this->hasMany(TransactionSellLine::class, 'transaction_id')->where('is_show', 1);;
    }

    public function purchases_lines()
    {
        return $this->hasMany(TransactionePurchasesLine::class, 'transaction_id');
    }

    public function payment()
    {
        return $this->hasMany(TransactionPayments::class, 'transaction_id');
    }

    public function prefixSetting()
    {
        return $this->hasOne(PrefixSetting::class, 'type', 'type');
    }

    public function accountsTransactions()
    {
        return $this->hasOne(AccountingAccountsTransaction::class, 'transaction_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
