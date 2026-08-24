<?php

namespace Modules\Zatca\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZatcaSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = (string) $this->input('zatca_environment', 'local');

        return [
            'zatca_environment' => ['required', Rule::in(['local', 'simulation', 'production'])],
            'zatca_app_key' => [
                Rule::requiredIf($environment === 'production'),
                'nullable',
                'string',
                'max:255',
            ],
            'seller_name' => ['required', 'string', 'max:191'],
            'vat_number' => ['required', 'regex:/^3\d{13}3$/'],
            'commercial_registration_number' => ['required', 'string', 'max:32'],
            'organization_unit' => ['required', 'regex:/^\d{10}$/'],
            'organization_name' => ['required', 'string', 'max:191'],
            'country_code' => ['required', 'string', 'size:2'],
            'city' => ['required', 'string', 'max:191'],
            'building_number' => ['required', 'string', 'max:16'],
            'postal_code' => ['required', 'string', 'max:16'],
            'district' => ['required', 'string', 'max:191'],
            'street_name' => ['nullable', 'string', 'max:191'],
            'plot_identification' => ['nullable', 'string', 'max:32'],
            'email_address' => ['required', 'email', 'max:191'],
            'otp' => ['required', 'string', 'max:32'],
            'common_name' => ['nullable', 'string', 'max:191'],
            'business_category' => ['required', 'string', 'max:191'],
            'egs_serial_number' => ['nullable', 'string', 'max:191'],
            'invoice_type' => ['required', Rule::in(['0100', '1000', '1100'])],
            'generate_certificates' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_number.regex' => __('zatca::lang.vat_number_invalid'),
            'organization_unit.regex' => __('zatca::lang.organization_unit_invalid'),
            'zatca_app_key.required' => __('zatca::lang.app_key_required_production'),
            'otp.required' => __('zatca::lang.otp_required'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $vat = preg_replace('/\D+/', '', (string) $this->input('vat_number', ''));
        $ou = preg_replace('/\D+/', '', (string) $this->input('organization_unit', ''));

        $this->merge([
            'vat_number' => $vat,
            'organization_unit' => $ou,
            'country_code' => strtoupper((string) ($this->input('country_code') ?: 'SA')),
            'generate_certificates' => $this->boolean('generate_certificates'),
        ]);
    }
}
