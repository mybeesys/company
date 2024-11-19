<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDashboardRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50', 'unique:roles,name'],
            'is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'rank' => [Rule::requiredIf($notAjaxValidate), 'numeric', 'max_digits:3'],
            'dashboard_permissions' => ['array', 'nullable'],
            'dashboard_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'ems')]
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
