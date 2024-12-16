<?php

namespace Modules\Establishment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstablishmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'contact_details' => ['nullable', 'digits_between:10,15'],
            'logo' => ['nullable', 'image', 'max:3072'],
            'is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'logo_old' => [Rule::requiredIf($notAjaxValidate), 'boolean']
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
