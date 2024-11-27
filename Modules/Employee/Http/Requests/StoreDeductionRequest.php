<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreDeductionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(Request $request): array
    {
        return [
            'deduction_repeater' => ['nullable', 'array'],
            'deduction_repeater.*.amount_type' => ['required', 'in:fixed,percent'],
            'deduction_repeater.*.adjustment_type' => ['required', 'exists:emp_payroll_adjustment_types,id'],
            'deduction_repeater.*.amount' => ['required', 'decimal:0,2', 'numeric'],
            'deduction_repeater.*.deduction_id' => ['nullable', 'exists:emp_payroll_adjustments,id',],
            'date' => ['required', 'date_format:Y-m'],
            'employee_id' => ['required', 'exists:emp_employees,id']
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
