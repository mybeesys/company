<?php

namespace Modules\Zatca\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateZatcaOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auto_sync_mode' => ['required', 'in:disable,instant,daily'],
            'disable_discount' => ['sometimes', 'boolean'],
            'disable_order_tax' => ['sometimes', 'boolean'],
            'default_sales_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lock_synced_invoices' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'disable_discount' => $this->boolean('disable_discount'),
            'disable_order_tax' => $this->boolean('disable_order_tax'),
            'lock_synced_invoices' => $this->boolean('lock_synced_invoices'),
            'default_sales_discount' => $this->input('default_sales_discount', 0),
        ]);
    }
}
