<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use Modules\Employee\Models\TimeSheetRule;

class TimeSheetRuleService
{
    public function getPaidBreakDuration(bool $minutes): float
    {
        $paidBreak = TimeSheetRule::firstWhere('rule_name', 'duration_of_paid_break')?->rule_value ?? "00:00";
        return convertToDecimalFormatHelper($paidBreak, $minutes);
    }

    public function getRegularWorkHours(bool $minutes): float
    {
        $regularWorkHours = TimeSheetRule::firstWhere('rule_name', 'maximum_regular_hours_per_day')?->rule_value ?? "08:00";
        return convertToDecimalFormatHelper($regularWorkHours, $minutes);
    }

    public function getOvertimeRateMultiplier()
    {
        return floatval(TimeSheetRule::firstWhere('rule_name', 'overtime_rate_multiplier')?->rule_value) ?? 1;
    }

    public function getMaximumOvertime($minutes)
    {
        $maximum_overtime_hours_per_day = TimeSheetRule::firstWhere('rule_name', 'maximum_overtime_hours_per_day')?->rule_value ?? "00:00";
        return convertToDecimalFormatHelper($maximum_overtime_hours_per_day, $minutes);
    }

    public function getMinutesToQualifyForPaidBreak($minutes)
    {
        $hours_to_qualify_to_paid_break = TimeSheetRule::firstWhere('rule_name', 'work_time_to_qualify_for_paid_break')?->rule_value ?? 0;
        return convertToDecimalFormatHelper($hours_to_qualify_to_paid_break, $minutes);
    }

    public function getOffDaysDates(Carbon $month): array
    {
        $offDays = $this->getOffDays();
        $dates = [];

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $current = $startOfMonth;
        while ($current->lte($endOfMonth)) {
            if (in_array(strtolower($current->format('l')), $offDays)) {
                $dates[] = $current->copy()->format('Y-m-d');
            }
            $current->addDay();
        }

        return $dates;
    }

    public function getOffDays()
    {
        return TimeSheetRule::firstWhere('rule_name', 'off_days')?->rule_value ?? [];
    }

    public function getOffDaysCount($carbonMonth)
    {
        $carbonMonth = $carbonMonth->startOfMonth();
        $off_days = TimeSheetRule::firstWhere('rule_name', 'off_days')?->rule_value ?? [];
        $totalOffDays = 0;

        $daysInMonth = $carbonMonth->daysInMonth;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDay = strtolower($carbonMonth->copy()->day($day)->format('l')); // Get the day name (e.g., "Monday")

            if (in_array($currentDay, $off_days)) {
                $totalOffDays++;
            }
        }
        return $totalOffDays;
    }
}