<?php

namespace Modules\Employee\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Employee\Models\TimeSheetRule;

class EnureTimeSheetRulesExists
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $settings_count = count(include base_path('Modules/Employee/data/timesheet-rules.php'));
        $stored_settings_count = TimeSheetRule::all()->count();
        if($settings_count > $stored_settings_count){
            return to_route('schedules.timesheet-rules.index')->with('error', __('employee::responses.please_set_time_sheet_rules_first'));
        }
        return $next($request);
    }
}
