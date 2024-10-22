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
            'clockOutTime' =>  Carbon::parse($request->get('clockOutTime'))->format('Y-m-d H:i:s'),
            'date' => Carbon::parse($request->get('date'))->format('Y-m-d')
        ])->toArray());
        return redirect()->route('timecards.index')->with('success', __('employee::responses.created_successfully', ['name' => __('employee::main.timecard')]));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('employee::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('employee::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
