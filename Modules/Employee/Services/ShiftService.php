<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use Modules\Employee\Models\PayrollAdjustment;
use Modules\Employee\Models\Shift;
use Modules\Employee\Models\TimeSheetRule;


class ShiftService
{
    protected $lang;

    public function __construct(protected $table_type, protected $request, protected $establishment_id)
    {
        $this->lang = session('locale');
    }

    public static function getStartEndDayTime()
    {
        $workingHours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value;
        $startOfDay = TimeSheetRule::firstWhere('rule_name', 'day_start_on_time')?->rule_value;

        if ($workingHours && $startOfDay) {
            $totalMinutes = convertToDecimalFormatHelper($workingHours, minutes: true);

            $startOfDayTime = Carbon::parse($startOfDay);
            return [
                'start_of_day' => $startOfDayTime,
                'end_of_day' => $startOfDayTime->copy()->addMinutes($totalMinutes)
            ];
        }
        return ['start_of_day' => null, 'end_of_day' => null];
    }

    public function getEmployeeData($employees, $start_date, $end_date, $schedules_ids)
    {
        return $employees->map(function ($employee) use ($start_date, $end_date, $schedules_ids) {
            $shifts_query = $employee->shifts->where('establishment_id', $this->establishment_id)->whereIn('schedule_id', $schedules_ids);
            $shifts = $shifts_query->select('id', 'establishment_id', 'date', 'startTime', 'endTime', 'break_duration')->groupBy('date')->toArray();
            $shifts_count = $shifts_query->count();
            $employee_name = $this->lang === 'ar' ? $employee->name : $employee->name_en;

            for ($date = $start_date->copy(); $date->lte($end_date); $date->addDay()) {

                $formatted_date = $date->format('Y-m-d');

                $shiftHtml = "<div class='add-schedule-shift-button d-flex flex-column text-nowrap' data-employee-id='$employee->id' data-employee-name='$employee_name' data-date='$formatted_date'";

                // checking if the this shift is set or it is a default shift
                if (isset($shifts[$formatted_date])) {
                    $shifts[$formatted_date] = $this->createDataShiftHtml($shiftHtml, $shifts, $formatted_date);
                } elseif ($shifts_count === 0) {
                    $defaultShift = [$formatted_date => Shift::whereIn('type', ['general_break', 'general_working_hours'])->get(['id', 'establishment_id', 'startTime', 'endTime', 'break_duration'])->toArray()];
                    $shifts[$formatted_date] = $this->createDataShiftHtml($shiftHtml, $defaultShift, $formatted_date, false);
                } else {
                    $shiftHtml .= '>-</div>';
                    $shifts[$formatted_date] = $shiftHtml;
                }
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
            'breaks' => $break_duration ? $second_time->copy()->addMinutes($break_duration)->format('H:i') . '-' . $second_time->format('H:i') . ' (' . ($this->request->format === 'hours_minutes' ? convertToHoursMinutesHelper($break_duration) : round($break_duration / 60, 2)) . ')' : '-',
            'wage' => '-',
            default => '',
        };
        return $divElement;
    }

    public function createDataShiftHtml($shiftHtml, $shifts, $formatted_date, $updatable = true)
    {
        foreach ($shifts[$formatted_date] as $key => $item) {
            $break_duration = $item['break_duration'] ?? 'false';
            $startTime = Carbon::parse($item['startTime'])->format('H:i');
            $endTime = Carbon::parse($item['endTime'])->format('H:i');

            $updatable && $shiftHtml .= ' data-schedule-shift-id-' . $key . '="' . $item['id'] . '"';
            $shiftHtml .= ' data-establishment-id-' . $key . '="' . $item['establishment_id'] . '"';
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
        $shiftHtml = $this->generateShiftHtml($divElement, $shiftHtml, $updatable);
        $shiftHtml .= '</div>';

        return $shiftHtml;
    }

    public function generateShiftHtml($divElement, $shiftHtml, $updatable)
    {
        $element = array_sum(array_filter($divElement, 'is_numeric'));
        if ($divElement) {
            if (!(str_contains($divElement[0], '-'))) {

                if ($this->request->format === 'hours_minutes') {
                    return $shiftHtml .= "<div> " . (is_numeric($element) ? convertToHoursMinutesHelper($element) : $element) . " </div>";
                }
                return $shiftHtml .= "<div> " . (is_numeric($element) ? round($element / 60, 2) : $element) . " </div>";
            } else {
                if (count(array_filter($divElement, fn($item) => $item !== "-")) === 0) {
                    // If the array contains only dashes ("-")
                    $divElement = ["-"];  // Keep only one "-"
                } else {
                    // If the array contains other items, remove all dashes
                    $divElement = array_filter($divElement, fn($item) => $item !== "-");
                }
                return $shiftHtml .= implode('', array_map(function ($element) use ($updatable) {
                    return "<div class='" . ($updatable ? 'fw-bolder' : '') . "'>$element</div>";
                }, $divElement));
            }
        }
        return null;
    }

    public function getEssentialColumns($employee)
    {
        return [
            'id' => $employee->id,
            'employee' => $this->lang === 'ar' ? $employee->name : $employee->name_en,
            'total_hours' => round($employee->timecards->sum('hours_worked'), 2),
            'basic_wage' => $employee->wage?->rate,
            'total_wage' => $employee->wage?->rate + PayrollAdjustment::where('employee_id', $employee->id)->always()->get()->sum('amount'),
            'role' => implode('<br>', $employee->allRoles->pluck('name')->toArray()),
            'establishment' => $employee?->defaultEstablishment?->name,
            'select' => '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input data-employee-id="' . $employee->id . '" class="form-check-input shift_select" type="checkbox" value="1" />
                        </div>'
        ];
    }

    public static function storeDefaultShifts()
    {
        $day_working_time = ShiftService::getStartEndDayTime();

        $brake_time = TimeSheetRule::firstWhere('rule_name', 'work_time_to_qualify_for_paid_break')?->rule_value;
        $brake_duration = TimeSheetRule::firstWhere('rule_name', 'duration_of_paid_break')?->rule_value;

        if ($brake_time && $brake_duration) {
            $brake_start_time = $day_working_time['start_of_day']->copy()->addMinutes(convertToDecimalFormatHelper($brake_time, minutes: true));
            Shift::updateOrCreate(['type' => 'general_break'], [
                'startTime' => $day_working_time['start_of_day']->format('Y-m-d H:i:s'),
                'endTime' => $brake_start_time->copy()->format('Y-m-d H:i:s'),
                'break_duration' => intval(convertToDecimalFormatHelper($brake_duration, minutes: true))
            ]);

            Shift::updateOrCreate(['type' => 'general_working_hours'], [
                'startTime' => $brake_start_time->copy()->addMinutes(convertToDecimalFormatHelper($brake_duration, minutes: true))->format('Y-m-d H:i:s'),
                'endTime' => $day_working_time['end_of_day']->format('Y-m-d H:i:s'),
                'break_duration' => null
            ]);

        } else {
            Shift::updateOrCreate(['type' => 'general_working_hours'], [
                'startTime' => $day_working_time['start_of_day']->format('Y-m-d H:i:s'),
                'endTime' => $day_working_time['end_of_day']->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function getEmployeeShiftsForDate(int $employeeId, Carbon $date)
    {
        return Shift::where('employee_id', $employeeId)
            ->whereDate('startTime', $date)
            ->get(['startTime', 'endTime']);
    }
}