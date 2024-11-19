<?php


namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Models\TimeSheetRule;
use Modules\Employee\Services\ShiftFilters;
use Yajra\DataTables\DataTables;

class ShiftTable
{
    public function __construct(protected $table_type, protected $request)
    {
    }

    public static function getShiftColumns()
    {
        return [
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "id"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_hours"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_wages"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "role"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "establishment"],
        ];
    }

    public static function getShiftFooters()
    {
        $generateRow = function (string $text) {
            $row = [
                ['class' => 'border text-end px-5', 'colspan' => '3', 'text' => $text],
            ];
            for ($i = 0; $i < 11; $i++) {
                $row[] = ['class' => '', 'colspan' => '1', 'text' => ''];
            }
            return $row;
        };
        return [
            ['class' => 'd-none table-wages-footer total-wage', 'th' => $generateRow(__('employee::fields.total_wages'))],
            ['class' => 'd-none table-wages-footer forecasted-sales', 'th' => $generateRow(__('employee::fields.forecasted_sales'))],
            ['class' => 'd-none table-wages-footer mean-sales', 'th' => $generateRow(__('employee::fields.mean_sales'))],
            ['class' => 'd-none table-wages-footer forecasted-labor-cost', 'th' => $generateRow(__('employee::fields.forecasted_labor_cost'))],
            ['class' => 'd-none table-wages-footer mean-labor-cost', 'th' => $generateRow(__('employee::fields.mean_labor_cost'))],
            ['class' => 'd-none table-breaks-footer', 'th' => $generateRow(__('employee::fields.breaks_total'))],
            ['class' => 'd-none table-hours-footer', 'th' => $generateRow(__('employee::fields.total_hours'))],
        ];
    }

    public function getShiftTable()
    {
        $start_date = Carbon::createFromFormat('Y-m-d', $this->request->input('start_date'));
        $end_date = Carbon::createFromFormat('Y-m-d', $this->request->input('end_date'));
        $schedules_ids = Schedule::where('start_date', '<=', $start_date->format('Y-m-d'))->where('end_date', '>=', $end_date->format('Y-m-d'))->pluck('id')->toArray();
        $employees = Employee::with(['timecards', 'shifts', 'allRoles', 'establishments', 'wages']);

        $filters = new ShiftFilters(['filter_role', 'filter_establishment', 'filter_employee_status']);
        $filters->applyFilters($this->request, $employees);

        $employeeData = $this->getEmployeeData($employees->get(['id', 'name', 'name_en']), $start_date, $end_date, $schedules_ids);

        return DataTables::of($employeeData)->rawColumns($employeeData->first() ? array_keys($employeeData->first()) : [])->make(true);
    }

    public function getEmployeeData($employees, $start_date, $end_date, $schedules_ids)
    {
        $day_times = $this->getStartEndDayTime();

        $start_of_day_time = $day_times['start_of_day'];
        $end_of_day = $day_times['end_of_day'];

        return $employees->map(function ($employee) use ($start_date, $end_date, $schedules_ids, $start_of_day_time, $end_of_day) {

            $shifts = $employee->shifts->whereIn('schedule_id', $schedules_ids)->select('id', 'role_id', 'date', 'startTime', 'endTime', 'break_duration')->groupBy('date')->toArray();

            $employee_name = session()->get('locale') === 'ar' ? $employee->name : $employee->name_en;

            for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {

                $formatted_date = $date->format('Y-m-d');

                $shiftHtml = "<div class='add-schedule-shift-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee_name' data-date='$formatted_date'";

                isset($shifts[$formatted_date]) ? $shifts[$formatted_date] = $this->createDataShiftHtml($shiftHtml, $shifts, $formatted_date) :
                    $shifts[$formatted_date] = $this->generateShiftHtml(($start_of_day_time && $end_of_day) ? $this->getFieldByType($start_of_day_time, $end_of_day) : '-', $shiftHtml);
            }
            $essentialColumns = $this->getEssentialColumns($employee);
            return array_merge($essentialColumns, $shifts);
        });
    }

    public function getFieldByType(Carbon $first_time, Carbon $second_time, $break_duration = null)
    {
        $divElement = match ($this->table_type) {
            'default' => $first_time->format('H:i') . ' - ' . $second_time->format('H:i'),
            'hours' => $first_time->diffInMinutes($second_time),
            'breaks' => $break_duration ? $second_time->format('H:i') . '-' . $second_time->addMinutes($break_duration)->format('H:i') . ' (' . ($this->request->format === 'hours_minutes' ? self::convertToHoursMinutesHelper($break_duration) : round($break_duration / 60, 2)) . ')' : '-',
            'wage' => '-',
            default => '',
        };
        return $divElement;
    }

    public function createDataShiftHtml($shiftHtml, $shifts, $formatted_date)
    {
        foreach ($shifts[$formatted_date] as $key => $item) {
            $break_duration = $item['break_duration'] ?? 'false';
            $startTime = Carbon::parse($item['startTime'])->format('H:i');
            $endTime = Carbon::parse($item['endTime'])->format('H:i');

            $shiftHtml .= ' data-schedule-shift-id-' . $key . '="' . $item['id'] . '"';
            $shiftHtml .= ' data-role-id-' . $key . '="' . $item['role_id'] . '"';
            $shiftHtml .= " data-break-duration-$key=$break_duration ";
            $shiftHtml .= "data-start-time-$key=$startTime ";
            $shiftHtml .= "data-end-time-$key=$endTime ";
        }
        $shiftHtml .= '>';
        $divElement = [];

        $divElement = [];
        foreach ($shifts[$formatted_date] as $key => $item) {
            $startTime = Carbon::parse($item['startTime']);
            $endTime = Carbon::parse($item['endTime']);
            $break_duration = isset($item['break_duration']) ? $item['break_duration'] : null;
            $divElement[] = $this->getFieldByType($startTime, $endTime, $break_duration);
        }
        $shiftHtml = $this->generateShiftHtml($divElement, $shiftHtml);
        $shiftHtml .= '</div>';

        return $shiftHtml;
    }

    public function generateShiftHtml($divElement, $shiftHtml)
    {
        $isArray = is_array($divElement);
        $element = $isArray ? array_sum(array_filter($divElement, 'is_numeric')) : $divElement;
        if (!($isArray ? str_contains($divElement[0], '-') : str_contains($divElement, '-'))) {
            if (!$isArray) {
                $shiftHtml .= "data-schedule-shift-id=null>";
            }
            if ($this->request->format === 'hours_minutes') {
                return $shiftHtml .= "<div> " . (is_numeric($element) ? self::convertToHoursMinutesHelper($element) : $element) . " </div>";
            }
            return $shiftHtml .= "<div> " . (is_numeric($element) ? round($element / 60, 2) : $element) . " </div>";

        } else {
            if ($isArray) {
                if (count(array_filter($divElement, fn($item) => $item !== "-")) === 0) {
                    // If the array contains only dashes ("-")
                    $divElement = ["-"];  // Keep only one "-"
                } else {
                    // If the array contains other items, remove all dashes
                    $divElement = array_filter($divElement, fn($item) => $item !== "-");
                }

                return $shiftHtml .= implode('', array_map(function ($element) {
                    return "<div> $element </div>";
                }, $divElement));
            }
            return $shiftHtml .= "data-schedule-shift-id=null> $divElement </div>";
        }
    }

    public function getEssentialColumns($employee)
    {
        return [
            'id' => $employee->id,
            'employee' => session()->get('locale') === 'ar' ? $employee->name : $employee->name_en,
            'total_hours' => $employee->timecards->sum('hours_worked'),
            'total_wages' => $employee->wages->sum('rate'),
            'role' => implode('<br>', $employee->allRoles->pluck('name')->toArray()),
            'establishment' => implode('<br>', $employee->getEmployeeEstablishmentsWithAllOption()),
            'select' => '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input data-employee-id="' . $employee->id . '" class="form-check-input shift_select" type="checkbox" value="1" />
                        </div>'
        ];
    }

    public static function getStartEndDayTime()
    {
        $workingHours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value;
        $startOfDay = TimeSheetRule::firstWhere('rule_name', 'day_start_on_time')?->rule_value;

        if ($workingHours && $startOfDay) {
            $workingMinutes = explode(':', $workingHours);
            $totalMinutes = $workingMinutes[0] * 60 + $workingMinutes[1];

            $startOfDayTime = Carbon::parse($startOfDay);
            return [
                'start_of_day' => $startOfDayTime,
                'end_of_day' => $startOfDayTime->copy()->addMinutes($totalMinutes)
            ];
        }

        return ['start_of_day' => null, 'end_of_day' => null];
    }

    public static function convertToHoursMinutesHelper($totalMinutes)
    {
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}