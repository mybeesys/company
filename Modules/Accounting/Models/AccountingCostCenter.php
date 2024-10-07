<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountingCostCenterFactory;

class AccountingCostCenter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): AccountingCostCenterFactory
    // {
    //     // return AccountingCostCenterFactory::new();
    // }
}
