<?php

namespace Modules\Employee\Services\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Employee\Models\TimeCard;
use Modules\Employee\Services\TimeSheetRuleService;

class TimeCardService
{

    public function __construct(protected TimeSheetRuleService $timeSheetRuleService)
    {
    }

    public function storeClockInTimecard($employee_id, $establishment_id, $clock_in_time, $date)
    {
        try {
            $perviousTimecard = TimeCard::whereDate('clock_in_time', $date)->where('employee_id', $employee_id)->where('establishment_id', $establishment_id)->latest('id')->first();
            if ($perviousTimecard) {
                if (!$perviousTimecard->clock_out_time) {
                    return ['status' => false, 'id' => $perviousTimecard->id, 'message' => __('employee::ApiResponses.employee_has_previous_clock_in'), 'status_code' => 409];
                }
            }
            $conflict = $this->clockInConflictCheck($employee_id, $establishment_id, $clock_in_time, $date);
            if($conflict){
                return ['status' => false, 'message' => __('employee::ApiResponses.timecards_conflict'), 'status_code' => 409]; 
            }

            $timecard_id = TimeCard::create([
                'employee_id' => $employee_id,
                'establishment_id' => $establishment_id,
                'clock_in_time' => $clock_in_time,
                'date' => $date
            ])->id;
            return ['status' => true, 'id' => $timecard_id];
        } catch (\Throwable $e) {
            Log::error('Timecard creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['status' => false, 'message' => __('employee::ApiResponses.server_error'), 'status_code' => 500];
        }
    }

    public function clockInConflictCheck($employee_id, $establishment_id, $clock_in_time, $date)
    {
        $conflicted_timecards = TimeCard::whereDate('clock_in_time', $date)->where('employee_id', $employee_id)->where('clock_in_time', '<=', $clock_in_time)->where('clock_out_time', '>=', $clock_in_time)->get();
        return $conflicted_timecards->isNotEmpty();
    }

    public function storeClockOutTimeCard($timecard_id, Carbon $clock_out_time)
    {
        $timecard = TimeCard::find($timecard_id);
        $clock_in_time = Carbon::parse($timecard->clock_in_time);
        $regular_hours = $this->timeSheetRuleService->getRegularWorkHours(true);
        $maximum_overtime = $this->timeSheetRuleService->getMaximumOvertime(true);

        $minutes_worked = $clock_in_time->diffInMinutes($clock_out_time);
        $hours_worked = round($minutes_worked / 60, 2);

        $overtimeMinutes = $minutes_worked - $regular_hours;

        if ($overtimeMinutes > 0) {
            if ($overtimeMinutes > $maximum_overtime) {
                $overtimeMinutes = $maximum_overtime;
            }
        } else {
            $overtimeMinutes = 0;
        }
        $overtime_hours = round($overtimeMinutes / 60, 2);

        $timecard = TimeCard::findOrFail($timecard_id);

        $updated = $timecard->update([
            'clock_out_time' => $clock_out_time,
            'hours_worked' => $hours_worked,
            'overtime_hours' => $overtime_hours
        ]);
        return $updated;
    }
}