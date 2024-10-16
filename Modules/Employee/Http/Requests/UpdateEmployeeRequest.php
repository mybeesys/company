<?php

namespace Modules\Employee\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Rules\EmployeeEstablishmentRule;

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
            'employmentEndDate' => ['nullable', 'date'],
            'PIN' => ['required', 'digits_between:4,5', 'numeric', Rule::unique('employee_employees', 'PIN')->ignore($this->PIN, 'PIN')],
            'image' => ['image', 'max:3072'],
            'isActive' => ['required', 'boolean'],
            'role_wage_repeater' => ['required', 'array'],
            'role_wage_repeater.*.role' => ['required', 'exists:roles,id'],
            'role_wage_repeater.*.wage' => ['nullable', 'decimal:0,2', 'numeric'],
            'role_wage_repeater.*.establishment' => ['required', new EmployeeEstablishmentRule],
            'active_managment_fields_btn' => ['boolean'],
            'dashboard_role_repeater' => ['required', 'array'],
            'dashboard_role_repeater.*.dashboardRole' => ['required_if_accepted:active_managment_fields_btn', 'nullable', 'exists:roles,id'],
            'dashboard_role_repeater.*.establishment' => ['required_if_accepted:active_managment_fields_btn', 'nullable', 'exists:establishment_establishments,id'],
            'accountLocked' => ['required_if_accepted:active_managment_fields_btn', 'nullable', 'boolean'],
            'password' => ['nullable', Password::default()],
            'username' => ['required_if_accepted:active_managment_fields_btn', 'nullable', 'string', Rule::unique('employee_administrative_users', 'userName')->ignore($this->username, 'userName'), 'max:50'],
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
