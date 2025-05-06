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
            'name_en' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'region' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
            'contact_details' => ['nullable', 'digits_between:10,15'],
            'logo' => ['nullable', 'image', 'max:3072'],
            'is_active' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'parent_id' => ['nullable', Rule::exists('est_establishments', 'id')->where('is_main', true)->where('is_active', true)],
            'is_main' => ['nullable', 'in:0,1'],
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
