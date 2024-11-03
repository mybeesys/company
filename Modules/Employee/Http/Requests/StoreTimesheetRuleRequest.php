<?php

namespace Modules\Employee\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimesheetRuleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'maximum_regular_hours_per_day' => ['string', $this->minHoursRule()],
            'maximum_overtime_hours_per_day' => ['string', $this->minHoursRule()],
            'overtime_rate_multiplier' => ['decimal:0,1'],
            'doubletime_rate_multiplier' => ['decimal:0,1'],
            'maximum_regular_hours_per_week' => ['string', $this->minHoursRule()],
            'allow_clockin_before_shift' => ['boolean'],
            'require_manager_approval_for_late_clockin' => ['boolean'],
            'prevent_employee_clockin_before_break_end' => ['boolean'],
            'consider_seventh_worked_day_as_overtime' => ['boolean'],
            'display_declared_tips_in_payroll' => ['boolean'],
            'display_payment_tips_in_payroll' => ['boolean'],
            'weak_starts_on' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            '12_hour_clock' => ['boolean'],
            'day_start_on_time' => ['string', 'date_format:H:i'],
            'enable_auto_clockout' => ['boolean'],
            'calculate_paid_unpaid_breaks_by_rules' => ['boolean'],
            'work_time_to_qualify_for_paid_break' => ['string', $this->minHoursRule()],
            'duration_of_paid_break' => ['string', $this->minHoursRule()],
            'employee_declares_break_type' => ['boolean'],
            'clock_in_before_shift_time_limit' => ['string', $this->minHoursRule()],
            'auto_clock_out_time' => ['string', 'date_format:H:i']
        ];
    }

    public function minHoursRule()
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $string = explode(':', $value);
            if ((int) $string[1] >= 60) {
                $fail(__('employee::responses.minutes_cannot_be_above_60'));
            }
            if(!is_numeric($string[0]) || !is_numeric($string[1])){
                $fail('invalid input');
            }
        };
    }
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
