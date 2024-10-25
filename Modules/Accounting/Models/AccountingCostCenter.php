<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Accounting\Database\Factories\AccountingCostCenterFactory;

class AccountingCostCenter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function chiledCostCenter()
    {
        return $this->hasMany(AccountingCostCenter::class, 'parent_id');
    }

    public function parentCostCenter()
    {
        return $this->belongsTo(AccountingCostCenter::class, 'parent_id');
    }




    public static function forDropdown()
    {
        $main_CostCenter_ids = AccountingCostCenter::where('parent_id', 'null')->get()->pluck('id');
        $parent_CostCenter_ids = AccountingCostCenter::where('parent_id', '<>', 'null')->get()->pluck('parent_id');


        $query = AccountingCostCenter::where('active', 1)->whereNotIn('id', $parent_CostCenter_ids)->whereNotIn('id', $main_CostCenter_ids);
        return $query->get();
    }


    public function transactions()
    {
        return $this->hasMany(AccountingAccountsTransaction::class, 'cost_center_id');
    }
}
