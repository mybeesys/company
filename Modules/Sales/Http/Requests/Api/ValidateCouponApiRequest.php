<?php

namespace Modules\Sales\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCouponApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'contact_id' => ['required', 'integer', 'min:1'],
            'establishment_id' => ['required', 'integer', 'min:1'],
            'taxable_before' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_before_vat' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
