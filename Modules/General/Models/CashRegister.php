<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;

// use Modules\General\Database\Factories\CashRegisterFactory;

class CashRegister extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function cash_register_transactions()
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function user()
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
}
