<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Modules\Employee\Traits\EmployeeValidateTrait;

class UpdateEmployeeRequest extends FormRequest
{

    use EmployeeValidateTrait;
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(Request $request): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return $this->getUpdateValidationRules($notAjaxValidate, $request, $this);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
