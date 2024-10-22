<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Employee\Classes\TimeCardTable;
use Modules\Employee\Http\Requests\StoreTimecardRequest;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\TimeCard;

class TimeCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $timecards = TimeCard::
                select('id', 'clockInTime', 'clockOutTime', 'hoursWorked', 'overtimeHours', 'date');
            return TimeCardTable::getTimecardTable($timecards);
        }
        $columns = TimeCardTable::getTimecardColumns();
        return view('employee::timecards.index', compact('columns'));
    }

    public function createLiveValidation(StoreTimecardRequest $request)
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::get(['id', 'name', 'name_en']);
        return view('employee::timecards.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTimecardRequest $request)
    {
        TimeCard::create($request->safe()->merge([
            'clockInTime' => Carbon::parse($request->get('clockInTime'))->format('Y-m-d H:i:s'),
            'clockOutTime' => Carbon::parse($request->get('clockOutTime'))->format('Y-m-d H:i:s'),
            'date' => Carbon::parse($request->get('date'))->format('Y-m-d')
        ])->toArray());
        return redirect()->route('timecards.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::main.timecard')]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TimeCard $timecard)
    {
        $employees = Employee::get(['id', 'name', 'name_en']);
        return view('employee::timecards.edit', compact('employees', 'timecard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreTimecardRequest $request, TimeCard $timecard)
    {
        $timecard->update($request->safe()->merge([
            'clockInTime' => Carbon::parse($request->get('clockInTime'))->format('Y-m-d H:i:s'),
            'clockOutTime' => Carbon::parse($request->get('clockOutTime'))->format('Y-m-d H:i:s'),
            'date' => Carbon::parse($request->get('date'))->format('Y-m-d')
        ])->toArray());
        return redirect()->route('timecards.index')->with('success', __('employee::responses.updated_successfully', ['name' => __('employee::main.timecard')]));
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
