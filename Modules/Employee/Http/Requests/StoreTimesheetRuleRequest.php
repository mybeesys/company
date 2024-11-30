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
            'maximum_regular_hours_per_day' => ['required', 'string', $this->minHoursRule()],
            'maximum_overtime_hours_per_day' => ['required', 'string', $this->minHoursRule()],
            'overtime_rate_multiplier' => ['required', 'decimal:0,1'],
            // 'doubletime_rate_multiplier' => ['required', 'decimal:0,1'],
            'maximum_regular_hours_per_week' => ['required', 'string', $this->minHoursRule()],
            'allow_clockin_before_shift' => ['required', 'boolean'],
            'require_manager_approval_for_late_clockin' => ['required', 'boolean'],
            'prevent_employee_clockin_before_break_end' => ['required', 'boolean'],
            // 'consider_seventh_worked_day_as_overtime' => ['required', 'boolean'],
            // 'display_declared_tips_in_payroll' => ['required', 'boolean'],
            // 'display_payment_tips_in_payroll' => ['required', 'boolean'],
            'week_starts_on' => ['required', 'string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'off_days' => ['array', 'nullable'],
            'off_days.*' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            '12_hour_clock' => ['required', 'boolean'],
            'day_start_on_time' => ['required', 'string', 'date_format:H:i'],
            'enable_auto_clockout' => ['required', 'boolean'],
            // 'calculate_paid_unpaid_breaks_by_rules' => ['required', 'boolean'],
            'work_time_to_qualify_for_paid_break' => ['required', 'string', $this->minHoursRule()],
            'duration_of_paid_break' => ['required', 'string', $this->minHoursRule()],
            // 'employee_declares_break_type' => ['required', 'boolean'],
            'clock_in_before_shift_time_limit' => ['required_if:allow_clockin_before_shift,true', 'nullable', 'string', $this->minHoursRule()],
            'auto_clock_out_time' => ['required_if:enable_auto_clockout,true', 'nullable', 'string', 'date_format:H:i']
        ];
    }

    public function minHoursRule()
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (isset($value)) {
                $string = explode(':', $value);
                if ((int) $string[1] >= 60) {
                    $fail(__('employee::responses.minutes_cannot_be_above_60'));
                }
                if (!is_numeric($string[0]) || !is_numeric($string[1])) {
                    $fail('invalid input');
                }
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
