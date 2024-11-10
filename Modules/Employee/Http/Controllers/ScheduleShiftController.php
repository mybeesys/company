<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Modules\Employee\Classes\ScheduleShiftTable;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Role;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Models\ScheduleShift;
use Modules\Employee\Models\TimeSheetRule;

class ScheduleShiftController extends Controller
{
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return ScheduleShiftTable::getScheduleShiftTable($request);
        }
        $roles = Role::get(['id', 'name']);
        $timeSheet_rules = TimeSheetRule::all();
        $columns = ScheduleShiftTable::getScheduleShiftColumns();
        return view('employee::schedules.shift-schedules.index', compact('columns', 'roles', 'timeSheet_rules'));
    }


    public function getShiftSchedule(Request $request)
    {
        $employee_id = $request->employee_id;
        $employee = Employee::with(['roles', 'establishmentRoles'])->findOrFail($employee_id);
        $roles = array_merge($employee->roles->pluck('id', 'name')->toArray(),$employee->establishmentRoles->pluck('id', 'name')->toArray()); 
        return response()->json(['data' => $roles]);
    }


    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $employee_id = $request->employee_id;
            $startOfWeek = Carbon::parse($request->date)->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::parse($request->date)->endOfWeek()->format('Y-m-d');
            $schedule_id = Schedule::updateOrCreate(['start_date' => $startOfWeek], [
                ['end_date' => $endOfWeek]
            ])->id;
            $ids = [];
            foreach ($request->schedule_shift_repeater as $item) {
                $startTime = $request->date . ' ' . $item['startTime'];
                $endTime = $request->date . ' ' . $item['endTime'];
                $shift_id = $item['shift_id'];
                $end_status = $item['end_status'];
                $break_duration = $end_status === 'break' ? (Carbon::parse($item['startTime'])->diffInMinutes($item['endTime'])) : null;
                $ids[] = ScheduleShift::updateOrCreate(['id' => $shift_id], [
                    'startTime' => $startTime,
                    'endTime' => $endTime,
                    'employee_id' => $employee_id,
                    'schedule_id' => $schedule_id,
                    'role_id' => $item['role'],
                    'break_duration' => $break_duration
                ])->id;
            }
            ScheduleShift::where('employee_id', $employee_id)->whereDate('startTime', $request->date)->whereNotIn('id', $ids)->delete();
        });

        return response()->json(['message' => __('employee::responses.opreation_success')]);
    }

}
