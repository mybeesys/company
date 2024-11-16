<?php

namespace Modules\Employee\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Rules\EmployeeEstablishmentRule;
use Modules\Employee\Rules\WageTypeRequired;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(Request $request): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'validate' => ['boolean', 'nullable'],
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'name_en' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'email' => [Rule::requiredIf($notAjaxValidate), 'email', 'unique:employee_employees,email'],
            'phoneNumber' => ['nullable', 'digits_between:10,15'],
            'employmentStartDate' => [Rule::requiredIf($notAjaxValidate), 'date'],
            'PIN' => [Rule::requiredIf($notAjaxValidate), 'digits_between:4,5', 'numeric', 'unique:employee_employees,pin'],
            'image' => ['image', 'max:3072'],
            'isActive' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'role_wage_repeater' => [Rule::requiredIf($notAjaxValidate), 'array'],
            'role_wage_repeater.*.role' => [Rule::requiredIf($notAjaxValidate), 'exists:roles,id'],
            'role_wage_repeater.*.wage' => ['nullable', 'decimal:0,2', 'max_digits:12'],
            'role_wage_repeater.*.wage_type' => [new WageTypeRequired($request->input('role_wage_repeater.*.wage')), 'nullable', 'in:hourly,monthly'],
            'role_wage_repeater.*.establishment' => [Rule::requiredIf($notAjaxValidate), new EmployeeEstablishmentRule],
            'active_management_fields_btn' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'dashboard_role_repeater' => [Rule::requiredIf($notAjaxValidate), 'array'],
            'dashboard_role_repeater.*.dashboardRole' => ['required_if_accepted:active_management_fields_btn', 'nullable', 'exists:roles,id'],
            'dashboard_role_repeater.*.establishment' => ['required_if_accepted:active_management_fields_btn', 'nullable', 'exists:establishment_establishments,id'],
            'accountLocked' => ['required_if_accepted:active_management_fields_btn', 'nullable', 'boolean'],
            'password' => ['required_if_accepted:active_management_fields_btn', 'nullable', Password::default()],
            'username' => ['required_if_accepted:active_management_fields_btn', 'nullable', 'string', 'unique:employee_administrative_users,userName', 'max:50'],
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
