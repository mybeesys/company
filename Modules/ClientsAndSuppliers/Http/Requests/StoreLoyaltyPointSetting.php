<?php

namespace Modules\ClientsAndSuppliers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoyaltyPointSetting extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'key' => ['nullable', 'array'],
            'key.*' => ['nullable', 'string'],
            'key.points_expiration_period_type' => ['nullable', 'string', 'in:month,year'],
            'key.points_expiration_period' => ['nullable', 'numeric'],
            'key.minimum_points' => ['nullable', 'numeric'],
            'key.maximum_redeem_point_per_order' => ['nullable', 'numeric'],
            'key.minimum_order_payment_to_redeem_points' => ['nullable', 'numeric'],
            'key.redeemed_amount_for_each_point' => ['nullable', 'numeric'],
            'key.maximum_order_points' => ['nullable', 'numeric'],
            'key.minimum_order_payment_to_earn_points' => ['nullable', 'numeric'],
            'key.amount_to_pay_to_earn_point' => ['nullable', 'numeric'],
            'key.loyalty_points_settings_active' => ['nullable', 'in:0,1'],
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
