<?php

namespace Modules\Employee\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Rules\EmployeeEstablishmentRule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // dd(request());
        return [
            'name' => ['required', 'string', 'max:50'],
            'name_en' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:employee_employees,email'],
            'phoneNumber' => ['nullable', 'digits_between:10,15'],
            'employmentStartDate' => ['required', 'date'],
            'PIN' => ['required', 'digits_between:4,5', 'numeric', 'unique:employee_employees,pin'],
            'image' => ['image', 'max:3072'],
            'isActive' => ['required', 'boolean'],
            'role_wage_repeater' => ['nullable', 'array'],
            'role_wage_repeater.*.role' => ['required_with:role_wage_repeater.*.wage', 'nullable', 'exists:roles,id'],
            'role_wage_repeater.*.wage' => ['nullable', 'decimal:0,2', 'max_digits:12'],
            'role_wage_repeater.*.establishment' => ['required', new EmployeeEstablishmentRule],
            'active_managment_fields_btn' => ['required', 'boolean'],
            'accountLocked' => ['required', 'boolean'],
            'password' => ['required_if_accepted:active_managment_fields_btn', 'nullable', Password::default()],
            'username' => ['required_if_accepted:active_managment_fields_btn', 'nullable', 'string', 'unique:employee_administrative_users,userName', 'max:50'],
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
