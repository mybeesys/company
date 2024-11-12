<?php


namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Models\TimeSheetRule;
use Yajra\DataTables\DataTables;

class ShiftTable
{

    public static function getShiftColumns()
    {
        return [
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "id"],
            ["class" => "text-start min-w-175px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            ["class" => "text-start min-w-100px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_hours"],
            ["class" => "text-start min-w-100px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_wages"],
            ["class" => "text-start min-w-175px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "role"],
            ["class" => "text-start min-w-175px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "establishment"],
        ];
    }

    public static function getShiftTable($request)
    {
        $start_date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        $end_date = Carbon::createFromFormat('Y-m-d', $request->input('end_date'));
        $schedules_ids = Schedule::where('start_date', '<=', $start_date->format('Y-m-d'))->where('end_date', '>=', $end_date->format('Y-m-d'))->pluck('id')->toArray();
        $employees = Employee::with(['timecards', 'shifts', 'roles', 'establishmentRoles', 'establishments', 'wages']);

        self::applyFilters($request, $employees);

        $employeeData = self::getEmployeeData($employees->get(['id', 'name', 'name_en']), $start_date, $end_date, $schedules_ids);

        return DataTables::of($employeeData)->rawColumns($employeeData->first() ? array_keys($employeeData->first()) : [])->make(true);
    }

    public static function getStartEndDayTime()
    {
        $working_hours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value;
        $start_of_day = TimeSheetRule::firstWhere('rule_name', 'day_start_on_time')?->rule_value;
        if ($working_hours && $start_of_day) {
            $working_hours_array = explode(':', $working_hours);
            $working_hourse_in_mintues = (int) $working_hours_array[0] * 60 + (int) $working_hours_array[1];
            $start_of_day_instance = Carbon::parse($start_of_day);
            $start_of_day_formatted = $start_of_day_instance->copy()->format('H:i'); 
            $end_of_day = $start_of_day_instance->copy()->addMinutes($working_hourse_in_mintues)->format('H:i');
            return ['start_of_day' => $start_of_day_formatted, 'end_of_day' => $end_of_day];
        }
        $start_of_day_instance = null;
        $end_of_day = null;
        return ['start_of_day' => $start_of_day_instance, 'end_of_day' => $end_of_day];
    }

    public static function applyFilters($request, $employees)
    {
        if ($request->has('filter_role_id') && !empty($request->filter_role_id)) {
            $request->filter_role_id === 'all' ? $employees : $employees->where(function ($query) use ($request) {
                $query->whereHas('roles', fn($query) => $query->where('role_id', $request->filter_role_id))
                    ->orWhereHas('establishmentRoles', fn($query) => $query->where('role_id', $request->filter_role_id));
            });
        }
        if ($request->has('filter_establishment_id') && !empty($request->filter_establishment_id)) {
            $request->filter_establishment_id === 'all' ? $employees : $employees->whereHas('establishments', fn($query) => $query->where('establishment_id', $request->filter_establishment_id));
        }
        if ($request->has('filter_employee_status') && isset($request->filter_employee_status)) {
            $request->filter_employee_status === 'all' ? $employees : $employees->where('isActive', $request->filter_employee_status);
        }
    }

    public static function getEmployeeData($employees, $start_date, $end_date, $schedules_ids)
    {
        $day_times = self::getStartEndDayTime();
        $start_of_day_time = $day_times['start_of_day'];
        $end_of_day = $day_times['end_of_day'];

        return $employees->map(function ($employee) use ($start_date, $end_date, $schedules_ids, $start_of_day_time, $end_of_day) {
            $shifts = $employee->shifts->whereIn('schedule_id', $schedules_ids)->select('id', 'role_id', 'date', 'startTime', 'endTime', 'break_duration')->groupBy('date')->toArray();
            $employee_name = session()->get('locale') === 'ar' ? $employee->name : $employee->name_en;
            for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {
                if (!array_key_exists($date->format('Y-m-d'), $shifts)) {
                    $shifts[$date->format('Y-m-d')] = '<div class="add-schedule-shift-button d-flex flex-column" data-schedule-shift-id=null data-employee-id="' . $employee->id . '" data-employee-name="' . $employee_name . '" data-date="' . $date->format('Y-m-d') . '">' . $start_of_day_time . ' - ' . $end_of_day . '</div>';
                } else {
                    $newArray = '<div class="add-schedule-shift-button d-flex flex-column" data-employee-id="' . $employee->id . '" data-employee-name="' . $employee_name . '" data-date=' . $date->format('Y-m-d');
                    foreach ($shifts[$date->format('Y-m-d')] as $key => $item) {
                        if (!array_key_exists('break_duration', $item))
                            $item['break_duration'] = 'false';

                        $newArray .= ' data-schedule-shift-id-' . $key . '=' . $item['id'] .
                            ' data-role-id-' . $key . '=' . $item['role_id'] .
                            ' data-break-duration-' . $key . '=' . $item['break_duration'] .
                            ' data-start-time-' . $key . '=' . Carbon::createFromFormat('Y-m-d H:i:s', $item['startTime'])->format('H:i') .
                            ' data-end-time-' . $key . '=' . Carbon::createFromFormat('Y-m-d H:i:s', $item['endTime'])->format('H:i');
                    }
                    $newArray .= '>';
                    foreach ($shifts[$date->format('Y-m-d')] as $item) {
                        $newArray .= '<div>' . Carbon::createFromFormat('Y-m-d H:i:s', $item['startTime'])->format('H:i') . ' - ' . Carbon::createFromFormat('Y-m-d H:i:s', $item['endTime'])->format('H:i') . '</div>';
                    }
                    $newArray .= '</div>';
                    $shifts[$date->format('Y-m-d')] = $newArray;
                }
            }
            return array_merge([
                'id' => $employee->id,
                'employee' => session()->get('locale') === 'ar' ? $employee->name : $employee->name_en,
                'total_hours' => $employee->timecards->sum('hoursWorked'),
                'total_wages' => $employee->wages->sum('rate'),
                'role' => array_merge($employee->roles->pluck('name')->toArray(), $employee->establishmentRoles->pluck('name')->toArray()),
                'establishment' => $employee->roles->isNotEmpty() ? array_merge($employee->establishments->pluck('name')->toArray(), [__('employee::fields.all_establishments')]) : $employee->establishments->pluck('name')->toArray()
            ], $shifts);
        });
    }
}