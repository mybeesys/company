<?php

namespace Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{

    public function prepareForValidation()
    {
        $this->merge([
            'establishments_ids' => is_array(explode(',', $this->establishments_ids)) ? explode(',', $this->establishments_ids) : [explode(',', $this->establishments_ids)],
            'products_ids' => is_array(explode(',', $this->products_ids)) ? explode(',', $this->products_ids) : [explode(',', $this->products_ids)],
            'categories_ids' => is_array(explode(',', $this->categories_ids)) ? explode(',', $this->categories_ids) : [explode(',', $this->categories_ids)],
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer', 'exists:sales_coupons,id'],
            'establishments_ids' => ['required', 'array'],
            'establishments_ids.*' => ['required', 'integer', 'exists:est_establishments,id'],
            'name' => ['required', 'string'],
            'code' => ['nullable', 'string', Rule::unique('sales_coupons', 'code')->ignore($this->id ? $this->code : '', 'code'), 'regex:/^\S*$/u'],
            'discount_apply_to' => ['required', 'in:all,product,category'],
            'products_ids' => ['required_if:discount_apply_to,product', 'array'],
            'products_ids.*' => ['required_if:discount_apply_to,product', 'nullable', 'integer', 'exists:product_products,id'],
            'categories_ids' => ['required_if:discount_apply_to,category', 'array'],
            'categories_ids.*' => ['required_if:discount_apply_to,category', 'nullable', 'integer', 'exists:product_categories,id'],
            'start_date' => ['required', 'date_format:Y-m-d H:i'],
            'end_date' => ['nullable', 'date_format:Y-m-d H:i'],
            'coupon_count' => ['nullable', 'integer'],
            'person_use_time_count' => ['nullable', 'integer'],
            'value' => ['required', 'decimal:0,2'],
            'value_type' => ['required', 'in:fixed,percent', 'string'],
            'apply_to_clients_groups' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean']
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
