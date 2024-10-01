<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:employee_employees,email'],
            'phone_number' => ['required', 'digits_between:10,15'],
            'employment_start_date' => ['required', 'date'],
            'pin' => ['required', 'digits_between:6,10', 'numeric', 'unique:employee_employees,pin'],
            'image' => ['required', 'image', 'max:3072'],
            // 'wage' => ['nullable', ''],
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
