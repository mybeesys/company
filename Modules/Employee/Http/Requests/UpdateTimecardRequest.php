<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimecardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employee_employees,id'],
            'hoursWorked' => ['required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'overtimeHours' => ['nullable', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'clockInTime' => ['required', 'date_format:Y/m/d h:i A'],
            'clockOutTime' => ['required', 'date_format:Y/m/d h:i A'],
            'date' => ['required', 'date']
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
