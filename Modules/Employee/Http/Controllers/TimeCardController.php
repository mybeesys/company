<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Employee\Classes\TimeCardTable;
use Modules\Employee\Http\Requests\StoreTimecardRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\TimeCard;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Establishment\Models\Establishment;

class TimeCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $timecards = TimeCard::with('employee');

            if ($request->has('date') && !empty($request->date)) {
                $timecards->whereDate('date', $request->date);
            }
            if ($request->has('employee_status') && isset($request->employee_status)) {
                $timecards->whereHas('employee', fn($query) => $query->where('is_active', $request->employee_status));
            }
            return TimeCardTable::getTimecardTable($timecards);
        }
        $columns = TimeCardTable::getTimecardColumns();
        return view('employee::schedules.timecards.index', compact('columns'));
    }

    public function createLiveValidation(StoreTimecardRequest $request)
    {
    }

    /**                                                                                                                             
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $maximum_regular_hours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value ?? '08:00';
        $maximum_overtime_hours = TimeSheetRule::firstWhere('rule_name', 'maximum_overtime_hours_per_day')?->rule_value ?? '02:00';
        $employees = Employee::get(['id', 'name', 'name_en']);
        $establishments = Establishment::get()->select('id', 'name');
        return view('employee::schedules.timecards.create', compact('employees', 'establishments', 'maximum_regular_hours', 'maximum_overtime_hours'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimecardRequest $request)
    {
        TimeCard::create($request->safe()->merge([
            'clock_in_time' => Carbon::parse($request->get('clock_in_time'))->format('Y-m-d H:i:s'),
            'clock_out_time' => Carbon::parse($request->get('clock_out_time'))->format('Y-m-d H:i:s'),
            'date' => Carbon::parse($request->get('date'))->format('Y-m-d')
        ])->toArray());
        return to_route('schedules.timecards.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::main.timecard')]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeCard $timecard)
    {
        $maximum_regular_hours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value ?? '08:00';
        $maximum_overtime_hours = TimeSheetRule::firstWhere('rule_name', 'maximum_overtime_hours_per_day')?->rule_value ?? '02:00';
        $employees = Employee::get(['id', 'name', 'name_en']);
        $establishments = Establishment::get()->select('id', 'name');
        return view('employee::schedules.timecards.edit', compact('employees', 'timecard', 'establishments', 'maximum_regular_hours', 'maximum_overtime_hours'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTimecardRequest $request, TimeCard $timecard)
    {
        $timecard->update($request->safe()->merge([
            'clock_in_time' => Carbon::parse($request->get('clock_in_time'))->format('Y-m-d H:i:s'),
            'clock_out_time' => Carbon::parse($request->get('clock_out_time'))->format('Y-m-d H:i:s'),
            'date' => Carbon::parse($request->get('date'))->format('Y-m-d')
        ])->toArray());
        return to_route('schedules.timecards.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::main.timecard')]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TimeCard $timecard)
    {
        $delete = $timecard->delete();
        if ($delete) {
            return response()->json(['message' => __('employee::responses.deleted_successfully', ['name' => __('employee::main.timecard')])]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
