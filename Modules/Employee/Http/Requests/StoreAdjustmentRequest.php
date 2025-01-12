<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'exists:emp_payroll_adjustments,id'],
            'adjustment_type' => ['required', 'exists:emp_payroll_adjustment_types,id'],
            'type' => ['required', 'in:allowance,deduction'],
            'employee_id' => ['required', 'exists:emp_employees,id'],
            'amount' => ['required', 'decimal:0,2', 'numeric'],
            'amount_type' => ['required', 'in:fixed,percent'],
            'applicable_date' => ['required', 'date_format:Y-m'],
            'apply_once' => ['nullable', 'boolean']
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
