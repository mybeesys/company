<?php


namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Schedule;
use Yajra\DataTables\DataTables;

class ScheduleShiftTable
{

    public static function getScheduleShiftColumns()
    {
        return [
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "id"],
            ["class" => "text-start min-w-175px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            ["class" => "text-start min-w-100px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_hours"],
        ];
    }

    public static function getScheduleShiftTable($request)
    {
        $start_date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        $end_date = Carbon::createFromFormat('Y-m-d', $request->input('end_date'));
        $schedules_ids = Schedule::where('start_date', '<=', $start_date->format('Y-m-d'))->where('end_date', '>=', $end_date->format('Y-m-d'))->pluck('id')->toArray();
        $employees = Employee::with(['timecards', 'scheduleshifts', 'roles'])->get(['id', 'name', 'name_en']);

        $employeeData = $employees->map(function ($employee) use ($start_date, $end_date, $schedules_ids) {
            $scheduleshifts = $employee->scheduleShifts->whereIn('schedule_id', $schedules_ids)->select('id', 'role_id', 'date', 'startTime', 'endTime', 'break_duration')->groupBy('date')->toArray();
            for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
                if (!array_key_exists($date->format('Y-m-d'), $scheduleshifts)) {
                    $scheduleshifts[$date->format('Y-m-d')] = '<div class="add-schedule-shift-button d-flex flex-column" data-schedule-shift-id=null data-employee-id="' . $employee->id . '" data-date="' . $date->format('Y-m-d') . '">--</div>';
                } else {
                    $newArray = '<div class="add-schedule-shift-button d-flex flex-column" data-employee-id="' . $employee->id . '" data-date=' . $date->format('Y-m-d');
                    foreach ($scheduleshifts[$date->format('Y-m-d')] as $key => $item) {
                        if (!array_key_exists('break_duration', $item))
                            $item['break_duration'] = 'false';

                        $newArray .= ' data-schedule-shift-id-' . $key . '=' . $item['id'] .
                            ' data-role-id-' . $key . '=' . $item['role_id'] .
                            ' data-break-duration-' . $key . '=' . $item['break_duration'] .
                            ' data-start-time-' . $key . '=' . Carbon::createFromFormat('Y-m-d H:i:s', $item['startTime'])->format('H:i') .
                            ' data-end-time-' . $key . '=' . Carbon::createFromFormat('Y-m-d H:i:s', $item['endTime'])->format('H:i');
                    }
                    $newArray .= '>';
                    foreach ($scheduleshifts[$date->format('Y-m-d')] as $item) {
                        $newArray .= '<div>' . Carbon::createFromFormat('Y-m-d H:i:s', $item['startTime'])->format('H:i') . ' - ' . Carbon::createFromFormat('Y-m-d H:i:s', $item['endTime'])->format('H:i') . '</div>';
                    }
                    $newArray .= '</div>';
                    $scheduleshifts[$date->format('Y-m-d')] = $newArray;
                }
            }
            return array_merge([
                'id' => $employee->id,
                'employee' => session()->get('locale') === 'ar' ? $employee->name : $employee->name_en,
                'total_hours' => $employee->timecards->sum('hoursWorked'),
            ], $scheduleshifts);
        });
        return DataTables::of($employeeData)->rawColumns(array_keys($employeeData->first()))->make(true);
    }
}