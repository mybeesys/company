<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Employee\Transformers\Collections\TimeSheetRuleCollection;

class TimeSheetRuleController extends Controller
{
    public function index()
    {
        $timeSheetRules = TimeSheetRule::all();
        return new TimeSheetRuleCollection($timeSheetRules);
    }
}