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
            'key' => ['required', 'array'],
            'key.*' => ['string'],
            'key.points_expiration_period_type' => ['required', 'string', 'in:month,year'],
            'key.points_expiration_period' => ['required', 'numeric'],
            'key.minimum_points' => ['required', 'numeric'],
            'key.maximum_redeem_point_per_order' => ['required', 'numeric'],
            'key.minimum_order_payment_to_redeem_points' => ['required', 'numeric'],
            'key.redeemed_amount_for_each_point' => ['required', 'numeric'],
            'key.maximum_order_points' => ['required', 'numeric'],
            'key.minimum_order_payment_to_earn_points' => ['required', 'numeric'],
            'key.amount_to_pay_to_earn_point' => ['required', 'numeric'],
            'key.loyalty_points_settings_active' => ['required', 'in:0,1'],
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
