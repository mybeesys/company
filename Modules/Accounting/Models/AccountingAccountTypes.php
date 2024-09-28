<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountingAccountTypesFactory;

class AccountingAccountTypes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    protected $table = 'accounting_account_types';

    public static function accounting_primary_type()
    {
        $accounting_primary_type = [
            'asset' => ['label' => __('accounting::lang.asset'), 'GLC' => 1, 'color' => '#006ae6'],
            'liabilities' => ['label' => __('accounting::lang.liabilities'), 'GLC' => 2, 'color' => '#00a261'],
            'equity' => ['label' => __('accounting::lang.equity'), 'GLC' => 3, 'color' => '#e42855'],
            'income' => ['label' => __('accounting::lang.income'), 'GLC' => 4, 'color' => '#c59a00'],
            'expenses' => ['label' => __('accounting::lang.expenses'), 'GLC' => 5, 'color' => '#7239ea'],
        ];

        return $accounting_primary_type;
    }
}