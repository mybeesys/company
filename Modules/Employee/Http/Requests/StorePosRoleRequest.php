<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'max:50', 'unique:roles,name'],
            'department' => ['nullable', 'string', 'max:50'],
            'rank' => [Rule::requiredIf($notAjaxValidate), 'numeric', 'max_digits:3'],
            'is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'pos_permissions' => ['array', 'nullable'],
            'pos_permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('type', 'pos')]
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
