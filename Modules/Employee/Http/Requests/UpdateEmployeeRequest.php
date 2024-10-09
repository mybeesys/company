<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'name_en' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', Rule::unique('employee_employees', 'email')->ignore($this->email, 'email')],
            'phoneNumber' => ['nullable', 'digits_between:10,15'],
            'employmentStartDate' => ['required', 'date'],
            'PIN' => ['required', 'digits_between:4,5', 'numeric', Rule::unique('employee_employees', 'PIN')->ignore($this->PIN, 'PIN')],
            'image' => ['image', 'max:3072'],
            'isActive' => ['required', 'boolean'],
            'role' => ['nullable', 'exists:roles,id'],
            'active-managment-fields-btn' => ['boolean'],
            'password' => ['required_if_accepted:active-managment-fields-btn', 'nullable', Password::default()],
            'username' => ['required_if_accepted:active-managment-fields-btn', 'nullable', 'string', 'unique:employee_administrative_users,userName', 'max:50'],
            'userEmail' => ['required_if_accepted:active-managment-fields-btn', 'nullable', 'email', 'unique:users,email', 'max:255']
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
