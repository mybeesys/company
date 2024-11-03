<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Employee\Database\Factories\TimeSheetRuleFactory;

class TimeSheetRule extends BaseScheduleModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'rule_value' => 'array',
    ];
}
