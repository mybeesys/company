<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Adjust this as needed based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:emp_employees,id'],
            'establishment_ids' => ['required', 'array', 'min:1'],
            'establishment_ids.*' => ['integer', 'exists:establishment_establishments,id'],
            'date' => ['required', 'date', 'date_format:Y-m'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'employee_ids' => $this->query('employee_ids') ? explode(',', $this->query('employee_ids')) : [],
            'establishment_ids' => $this->query('establishment_ids') ? explode(',', $this->query('establishment_ids')) : [],
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->route('schedules.payrolls.index')
                ->with('error', $validator->errors()->first())
        );
    }
}
