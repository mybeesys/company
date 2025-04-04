<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\TimeSheetRule;

class WageCalculationService
{
    public function __construct(public TimeSheetRuleService $timeSheetRuleService)
    {
    }

    public function calculateMonthlyWage($timecards, $basicWage, Carbon $carbonMonth, Employee $employee): float
    {
        $monthWeeks = $this->getMonthWeeksWithManualShifts($carbonMonth, $employee);

        $wageCalculationParams = $this->prepareWageCalculationParameters($basicWage, $carbonMonth);

        $totalWage = $this->calculateWageForWeeks($monthWeeks['weeks_with_shifts'], $carbonMonth, $employee, $timecards, $wageCalculationParams, 'calculateDailyWageWithShifts');

        $totalWage = $this->calculateWageForWeeks($monthWeeks['weeks_without_shifts'], $carbonMonth, $employee, $timecards, $wageCalculationParams, 'calculateDailyWageWithoutShifts');

        return round($totalWage, 2);
    }

    private function calculateWageForWeeks($weeks, Carbon $carbonMonth, Employee $employee, $timecards, array $wageParams, $function): float
    {
        $totalWage = 0;
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();
        $off_days = TimeSheetRule::firstWhere('rule_name', 'off_days')?->rule_value ?? [];

        foreach ($weeks as &$week) {
            $week['start'] = max($week['start'], $startOfMonth);
            $week['end'] = min($week['end'], $endOfMonth);

            $current = $week['start'];
            while ($current->lte($week['end'])) {
                if (in_array(strtolower($current->format('l')), $off_days)) {
                    $current->addDay();
                    continue;
                }

                $totalWage += $this->{$function}($current, $employee, $timecards, $wageParams);
                $current->addDay();
            }
        }
        return $totalWage;
    }


    private function prepareWageCalculationParameters($basicWage, Carbon $carbonMonth): array
    {
        return [
            'paid_break_minutes' => $this->timeSheetRuleService->getPaidBreakDuration(minutes: true),
            'minutes_to_qualify_to_paid_break' => $this->timeSheetRuleService->getMinutesToQualifyForPaidBreak(minutes: true),
            'regular_work_minutes' => $this->timeSheetRuleService->getRegularWorkHours(minutes: true),
            'hour_basic_wage' => $this->calculateHourlyBasicWage($basicWage, $carbonMonth, $this->timeSheetRuleService->getOffDaysCount($carbonMonth)),
            'specified_day_basic_wage' => $this->calculateDailyBasicWage($basicWage, $carbonMonth, $this->timeSheetRuleService->getOffDaysCount($carbonMonth)),
            'basic_wage_rate' => $basicWage->rate,
            'overTime_rate_multiplier' => $this->timeSheetRuleService->getOvertimeRateMultiplier(),
            'maximum_overtime_minutes' => $this->timeSheetRuleService->getMaximumOvertime(minutes: true),
        ];
    }

    // This week has custom shifts
    private function calculateDailyWageWithShifts(Carbon $currentDate, Employee $employee, $timecards, array $wageParams): float
    {
        $shifts = ShiftService::getEmployeeShiftsForDate($employee->id, $currentDate);

        $totalShiftMinutes = $this->calculateTotalShiftMinutes($shifts);

        $hourWage = $this->calculateHourWageForShifts($totalShiftMinutes, $wageParams['specified_day_basic_wage']);

        $formattedDate = $currentDate->format('Y-m-d');

        $totalTimecardMinutes = $this->calculateTimecardsMinutes($timecards, $formattedDate, $employee->id, $wageParams, true);

        //Determine if qualify for paid break
        if ($totalTimecardMinutes > $wageParams['minutes_to_qualify_to_paid_break']) {
            $adjustedTimecardMinutes = $this->adjustTimecardMinutesForBreak($totalTimecardMinutes, $totalShiftMinutes, $wageParams['paid_break_minutes']);
        } else {
            $adjustedTimecardMinutes = $totalTimecardMinutes;
        }
        return ($adjustedTimecardMinutes / 60) * $hourWage;
    }

    // This week is normal week
    private function calculateDailyWageWithoutShifts(Carbon $currentDate, Employee $employee, $timecards, array $wageParams): float
    {
        $formattedDate = $currentDate->format('Y-m-d');
        $totalTimecardMinutes = $this->calculateTimecardsMinutes($timecards, $formattedDate, $employee->id, $wageParams, true);

        $regularWorkMinutes = $wageParams['regular_work_minutes'];

        //Determine if qualify for paid break
        if ($totalTimecardMinutes > $wageParams['minutes_to_qualify_to_paid_break']) {
            $adjustedTimecardMinutes = $this->adjustTimecardMinutesForBreak($totalTimecardMinutes, $regularWorkMinutes, $wageParams['paid_break_minutes']);
        } else {
            $adjustedTimecardMinutes = $totalTimecardMinutes;
        }

        return ($adjustedTimecardMinutes / 60) * $wageParams['hour_basic_wage'];
    }

    private function calculateTotalShiftMinutes($shifts): float
    {
        return $shifts->reduce(function ($total, $shift) {
            return $total + Carbon::parse($shift->startTime)->diffInMinutes(Carbon::parse($shift->endTime));
        }, 0);
    }

    private function calculateHourWageForShifts($totalShiftMinutes, $specified_day_basic_wage): float
    {
        return $totalShiftMinutes ? ($specified_day_basic_wage * 60) / $totalShiftMinutes : 0;
    }

    private function calculateHourlyBasicWage($basicWage, Carbon $carbonMonth, $off_days_count): float
    {
        $regularWorkHours = $this->timeSheetRuleService->getRegularWorkHours(minutes: false);

        return floatval($basicWage->rate) / (($carbonMonth->daysInMonth - $off_days_count) * floatval($regularWorkHours));
    }

    private function calculateDailyBasicWage($basicWage, Carbon $carbonMonth, $off_days_count): float
    {
        return floatval($basicWage->rate) / ($carbonMonth->daysInMonth - $off_days_count);
    }

    private function adjustTimecardMinutesForBreak(float $totalTimecardMinutes, float $totalWorkingMinutes, float $paidBreakHours): float
    {
        $paidBreakMinutes = $paidBreakHours;
        // Check if total timecard minutes is less than total working time minutes and totalWorkingMinutes - totalTimecardMinutes is larger or equal than paid break
        if ($totalTimecardMinutes < $totalWorkingMinutes && (($totalWorkingMinutes - $totalTimecardMinutes) >= $paidBreakMinutes)) {
            $totalTimecardMinutes += $paidBreakMinutes;
        }
        return $totalTimecardMinutes;
    }

    public function calculateTimecardsMinutes($timecards, string $formattedCurrentDate, int $employeeId, $wageParams, $with_over_time): float
    {
        return $timecards->clone()
            ->where('employee_id', $employeeId)
            ->where(fn($query) => $query->whereDate('clock_in_time', $formattedCurrentDate)
                ->orWhereDate('clock_out_time', $formattedCurrentDate))
            ->get()
            ->map(function ($timecard) use ($formattedCurrentDate, $with_over_time, $wageParams) {
                return $this->calculateTimecardMinutesForDate($timecard, $formattedCurrentDate, $with_over_time, $wageParams['overTime_rate_multiplier'], $wageParams['regular_work_minutes'], $wageParams['maximum_overtime_minutes']);
            })
            ->sum();
    }

    private function calculateTimecardMinutesForDate($timecard, string $formattedCurrentDate, $with_over_time, $overTime_rate_multiplier, $regular_work_minutes, $maximum_overtime_minutes): int
    {
        $clockInTime = Carbon::parse($timecard->clock_in_time);
        $clockOutTime = $timecard->clock_out_time ? Carbon::parse($timecard->clock_out_time) : $clockInTime->copy()->addMinutes($regular_work_minutes);

        $startTime = $this->determineStartTime($clockInTime, $formattedCurrentDate);
        $endTime = $this->determineEndTime($clockOutTime, $formattedCurrentDate);

        $total_minutes = $startTime->diffInMinutes($endTime);
        if ($with_over_time) {
            $overtime = $total_minutes > $regular_work_minutes ? $total_minutes - $regular_work_minutes : 0;
            $overtime = $overtime > $maximum_overtime_minutes ? $maximum_overtime_minutes : $overtime;
            $added_minutes = $overtime * $overTime_rate_multiplier - $overtime;
            $total_minutes += $added_minutes;
        }
        return $total_minutes;
    }

    private function determineStartTime(Carbon $clockInTime, string $formattedCurrentDate): Carbon
    {
        //If start time is from the past day then make it at 00
        return $clockInTime->format('Y-m-d') != $formattedCurrentDate ? Carbon::parse("{$formattedCurrentDate} 00:00:00") : $clockInTime;
    }

    private function determineEndTime(Carbon $clockOutTime, string $formattedCurrentDate): Carbon
    {
        //If the end time goes to the next day then make it at the end of the day
        return $clockOutTime->format('Y-m-d') != $formattedCurrentDate ? Carbon::parse("{$formattedCurrentDate} 23:59:59") : $clockOutTime;
    }

    private function getMonthWeeksWithManualShifts(Carbon $carbonMonth, Employee $employee): array
    {
        $startOfMonth = $carbonMonth->copy()->startOfMonth();
        $endOfMonth = $carbonMonth->copy()->endOfMonth();

        $startOfFirstWeek = $startOfMonth->copy()->startOfWeek();
        $endOfLastWeek = $endOfMonth->copy()->endOfWeek();

        $weeks = [];
        $current = $startOfFirstWeek;

        while ($current->lte($endOfLastWeek)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek();

            $employeeShifts = $employee->shifts()
                ->whereNull('type')
                ->whereHas('schedule', fn($query) => $query->where('start_date', '<=', $weekStart->format('Y-m-d'))->where('end_date', $weekEnd->format('Y-m-d')))
                ->get();

            $weeks[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
                'has_shifts' => $employeeShifts->isNotEmpty()
            ];

            $current->addWeek();
        }

        return [
            'weeks_with_shifts' => collect($weeks)->where('has_shifts', true),
            'weeks_without_shifts' => collect($weeks)->where('has_shifts', false)
        ];
    }
}
