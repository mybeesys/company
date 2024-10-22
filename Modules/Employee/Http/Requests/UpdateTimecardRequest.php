<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimecardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'employee_id' => [Rule::requiredIf($notAjaxValidate), 'exists:employee_employees,id'],
            'hoursWorked' => [Rule::requiredIf($notAjaxValidate), 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'overtimeHours' => ['nullable', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'clockInTime' => [Rule::requiredIf($notAjaxValidate), 'date_format:Y/m/d h:i A'],
            'clockOutTime' => [Rule::requiredIf($notAjaxValidate), 'date_format:Y/m/d h:i A'],
            'date' => [Rule::requiredIf($notAjaxValidate), 'date']
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
