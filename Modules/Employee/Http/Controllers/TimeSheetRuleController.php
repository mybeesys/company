<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Http\Requests\StoreTimesheetRuleRequest;
use Modules\Employee\Models\TimeSheetRule;

class TimeSheetRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stored_settings = TimeSheetRule::pluck('rule_value', 'rule_name')->toArray();
        $settings = include base_path('Modules/Employee/data/timesheet-rules.php');
        return view('employee::schedules.timesheet-rules.index', compact('settings', 'stored_settings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimesheetRuleRequest $request)
    {
        foreach ($request->safe()->all() as $setting_name => $value) {
            TimeSheetRule::updateOrCreate(['rule_name' => $setting_name], ['rule_value' => $value]);
        }
        return back()->with('success', __('employee::responses.opreation_success'));
    }

}
