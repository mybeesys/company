<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Employee\Models\Employee;

class StoreTimecardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'employee_id' => [Rule::requiredIf($notAjaxValidate), 'exists:emp_employees,id'],
            'establishment_id' => [Rule::requiredIf($notAjaxValidate), 'exists:establishment_establishments,id'],
            'clock_in_time' => [Rule::requiredIf($notAjaxValidate), 'date_format:Y/m/d h:i A', 'before:clock_out_time'],
            'clock_out_time' => [Rule::requiredIf($notAjaxValidate), 'date_format:Y/m/d h:i A', 'after:clock_in_time'],
            'hours_worked' => [Rule::requiredIf($notAjaxValidate), 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'overtime_hours' => ['nullable', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'date' => [Rule::requiredIf($notAjaxValidate), 'date']
        ];
    }

    public function withValidator($validator)
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        if($notAjaxValidate){
            $validator->after(function ($validator) {
                $employee_id = $this->input('employee_id');
                $employee_establishments_ids = Employee::with('wageEstablishments')->findOrFail($employee_id)->wageEstablishments->pluck('id')->toArray();
                if (!in_array($this->input('establishment_id'), $employee_establishments_ids)) {
                    $validator->errors()->add("establishment_id", 'The employee you selected does not have the selected role');
                }
            });
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
