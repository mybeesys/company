<?php

namespace Modules\Establishment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = ! str_contains(request()->url(), 'validate');
        $companyId = $this->route('id');
        $userId = $companyId
            ? DB::connection('mysql')->table('companies')->where('id', $companyId)->value('user_id')
            : null;

        return [
            'name' => [Rule::requiredIf($notAjaxValidate), 'string', 'min:2', 'max:255'],
            'ceo_name' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'business_type' => [
                Rule::requiredIf($notAjaxValidate),
                Rule::in(['contractors', 'e-commerce', 'restaurant-cafe', 'services', 'general']),
            ],
            'phone' => ['nullable', 'digits_between:10,15'],
            'country_id' => [Rule::requiredIf($notAjaxValidate), 'exists:mysql.countries,id'],
            'state' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'city' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'zipcode' => [Rule::requiredIf($notAjaxValidate), 'digits:5'],
            'national_address' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'tax_name' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'tax_number' => ['nullable'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'menu_cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('establishment::fields.name'),
            'ceo_name' => __('establishment::fields.ceo_name'),
            'email' => __('establishment::fields.email'),
            'business_type' => __('establishment::fields.business_type'),
            'phone' => __('establishment::fields.phone'),
            'country_id' => __('establishment::fields.country'),
            'state' => __('establishment::fields.country_state'),
            'city' => __('establishment::fields.city'),
            'zipcode' => __('establishment::fields.zipcode'),
            'tax_name' => __('establishment::fields.tax_name'),
        ];
    }
}
