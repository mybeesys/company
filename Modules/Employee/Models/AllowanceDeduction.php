<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Employee\Database\Factories\AllowanceDeductionFactory;

class AllowanceDeduction extends Model
{
    use HasFactory;

    protected $table = 'emp_allowances_deductions';

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
