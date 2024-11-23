<?php

namespace Modules\Employee\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Rules\EmployeeEstablishmentRule;
use Modules\Employee\Rules\WageTypeRequired;

trait EmployeeValidateTrait
{

    protected function getCreateValidationRules($notAjaxValidate, $request)
    {
        return $this->getCommonValidationRules($notAjaxValidate, $request) + [
            'email' => [Rule::requiredIf($notAjaxValidate), 'email', 'unique:emp_employees,email'],
            'PIN' => [Rule::requiredIf($notAjaxValidate), 'digits_between:4,5', 'numeric', 'unique:emp_employees,pin'],
            'password' => ['required_if_accepted:ems_access', 'nullable', Password::default()],
        ];
    }

    protected function getUpdateValidationRules($notAjaxValidate, $request, $employee)
    {
        return $this->getCommonValidationRules($notAjaxValidate, $request) + [
            'email' => [Rule::requiredIf($notAjaxValidate), 'email', Rule::unique('emp_employees', 'email')->ignore($employee->email, 'email')],
            'PIN' => [Rule::requiredIf($notAjaxValidate), 'digits_between:4,5', 'numeric', Rule::unique('emp_employees', 'PIN')->ignore($employee->PIN, 'PIN')],
            'password' => ['nullable', Password::default()],
            'employment_end_date' => ['nullable', 'date'],
        ];
    }

    protected function getCommonValidationRules($notAjaxValidate, $request)
    {
        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'name_en' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'phone_number' => ['nullable', 'digits_between:10,15'],
            'employment_start_date' => [Rule::requiredIf($notAjaxValidate), 'date'],
            'image' => ['image', 'max:3072'],
            'pos_is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'role_wage_repeater' => ['nullable', 'array'],
            'role_wage_repeater.*.posRole' => ['nullable', 'exists:roles,id'],
            'role_wage_repeater.*.wage' => ['nullable', 'decimal:0,2', 'numeric'],
            'role_wage_repeater.*.wage_type' => [new WageTypeRequired($request->input('role_wage_repeater..wage')), 'nullable', 'in:hourly,monthly,fixed'],
            'role_wage_repeater.*.establishment' => [Rule::requiredIf($notAjaxValidate), new EmployeeEstablishmentRule],
            'allowance_repeater' => ['nullable', 'array'],
            'allowance_repeater.*.amount_type' => [Rule::when($notAjaxValidate, 'required_with:allowance_repeater'), 'in:fixed,percent'],
            'allowance_repeater.*.allowance_type' => [Rule::when($notAjaxValidate, 'required_with:allowance_repeater'), 'exists:emp_payroll_adjustment_types,id'],
            'allowance_repeater.*.amount' => [Rule::when($notAjaxValidate, 'required_with:allowance_repeater'), 'decimal:0,2', 'numeric'],
            'allowance_repeater.*.applicable_date' => [Rule::when($notAjaxValidate, 'required_with:allowance_repeater'), 'date_format:Y-m'],
            'ems_access' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'dashboard_role_repeater' => ['required_if_accepted:ems_access', 'array'],
            'dashboard_role_repeater.*.dashboardRole' => ['required_if_accepted:ems_access', 'nullable', 'exists:roles,id'],
            'dashboard_role_repeater.*.wage' => ['nullable', 'decimal:0,2', 'numeric'],
            'dashboard_role_repeater.*.wage_type' => [new WageTypeRequired($request->input('dashboard_role_repeater..wage')), 'nullable', 'in:hourly,monthly,fixed'],
            'user_name' => ['required_if_accepted:ems_access', 'nullable', 'string', 'max:50'],
        ];
    }

}

