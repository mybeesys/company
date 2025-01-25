<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StorePayrollRequest extends FormRequest
{

    public function prepareForValidation()
    {
        $this->merge([
            'establishment_ids' => is_array(explode(',', $this->establishment_ids)) ? explode(',', $this->establishment_ids) : [explode(',', $this->establishment_ids)],
            'employee_ids' => explode(',', $this->employee_ids)
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(Request $request): array
    {
        return [
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['required', 'exists:emp_employees,id'],
            'establishment_ids' => ['required', 'array'],
            'establishment_ids.*' => ['required', 'exists:est_establishments,id'],
            'date' => ['required', 'date_format:Y-m'],
            'payroll_group_name' => ['required', 'string', 'max:30'],
            'payroll_group_state' => ['required', 'in:final,draft'],
            'payroll_group_id' => ['nullable', 'exists:sch_payroll_groups,id']
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
