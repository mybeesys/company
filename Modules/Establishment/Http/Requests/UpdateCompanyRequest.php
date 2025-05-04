<?php

namespace Modules\Establishment\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        $notAjaxValidate = !str_contains(request()->url(), 'validate');
        return [
            'ceo_name' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'email' => [
                'required',
                'email',
                'unique:users,email'
            ],
            'phone' => ['nullable', 'digits_between:10,15'],
            'country_id' => [Rule::requiredIf($notAjaxValidate), 'exists:mysql.countries,id'],
            'state' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'city' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'zipcode' => [Rule::requiredIf($notAjaxValidate), 'digits:5'],
            'national_address' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'tax_name' => [Rule::requiredIf($notAjaxValidate), 'string'],
            'tax_number' => ['nullable']
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
