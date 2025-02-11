<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\General\Database\Factories\CashRegisterTransactionFactory;

class CashRegisterTransaction extends Model
{
    use HasFactory;
    protected $casts = [
        'denominations' => 'array',
    ];


    protected $guarded = ['id'];


    public function cash_register_transactions()
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }
}