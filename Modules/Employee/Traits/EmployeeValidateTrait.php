<?php

namespace Modules\Employee\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Employee\Rules\EmployeeEstablishmentRule;

trait EmployeeValidateTrait
{

    protected function getCreateValidationRules($notAjaxValidate, $request)
    {
        return $this->getCommonValidationRules($notAjaxValidate, $request) + [
            'email' => [Rule::requiredIf($notAjaxValidate), 'email', 'unique:emp_employees,email'],
            'pin' => [Rule::requiredIf($notAjaxValidate), 'digits_between:4,5', 'numeric', 'unique:emp_employees,pin'],
            'password' => ['required_if_accepted:ems_access', 'nullable', Password::default()],
        ];
    }

    protected function getUpdateValidationRules($notAjaxValidate, $request, $employee)
    {
        return $this->getCommonValidationRules($notAjaxValidate, $request) + [
            'email' => [Rule::requiredIf($notAjaxValidate), 'email', Rule::unique('emp_employees', 'email')->ignore($employee->email, 'email')],
            'pin' => [Rule::requiredIf($notAjaxValidate), 'digits_between:4,5', 'numeric', Rule::unique('emp_employees', 'pin')->ignore($employee->pin, 'pin')],
            'password' => ['nullable', Password::default()],
            'image_old' => [Rule::requiredIf($notAjaxValidate), 'boolean']
        ];
    }

    protected function getCommonValidationRules($notAjaxValidate, $request)
    {
        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'name_en' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50'],
            'phone_number' => ['nullable', 'digits_between:10,15'],
            'employment_start_date' => [Rule::requiredIf($notAjaxValidate), 'date'],
            'employment_end_date' => ['nullable', 'date'],
            'image' => ['image', 'max:3072'],
            'pos_is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'establishment_id' => [Rule::requiredIf($notAjaxValidate), 'exists:est_establishments,id'],

            'pos_role_repeater' => ['nullable', 'array'],
            'pos_role_repeater.*.posRole' => [Rule::requiredIf($notAjaxValidate), 'exists:roles,id'],
            'pos_role_repeater.*.establishment' => [Rule::requiredIf($notAjaxValidate), new EmployeeEstablishmentRule],

            'wage_amount' => ['nullable', 'decimal:0,2', 'numeric'],
            'wage_type' => ['required_with:wage_amount', 'nullable', 'in:variable,fixed'],

            'allowance_repeater' => ['nullable', 'array'],
            'allowance_repeater.*.amount_type' => [Rule::requiredIf($notAjaxValidate), 'in:fixed,percent'],
            'allowance_repeater.*.adjustment_type' => [Rule::requiredIf($notAjaxValidate), 'exists:emp_payroll_adjustment_types,id'],
            'allowance_repeater.*.amount' => [Rule::requiredIf($notAjaxValidate), 'decimal:0,2', 'numeric'],
            'allowance_repeater.*.applicable_date' => [Rule::requiredIf($notAjaxValidate), 'date_format:Y-m'],

            'ems_access' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'dashboard_role_repeater' => ['nullable', 'array'],
            'dashboard_role_repeater.*.dashboardRole' => [Rule::requiredIf($notAjaxValidate), 'exists:roles,id'],
            'user_name' => ['required_if_accepted:ems_access', 'nullable', 'string', 'max:50'],

        ];
    }

}

