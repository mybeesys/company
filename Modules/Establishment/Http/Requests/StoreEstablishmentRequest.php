<?php

namespace Modules\Establishment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Setting;

class StoreEstablishmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! Setting::isPerpetualInventory()) {
            $this->merge(['perpetual_inventory_account_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $notAjaxValidate = ! str_contains(request()->url(), 'validate');

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
            'logo_old' => [Rule::requiredIf($notAjaxValidate), 'boolean'],
            'perpetual_inventory_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounting_accounts', 'id')->where(function ($q) {
                    $parentIds = AccountingAccount::query()
                        ->whereNotNull('parent_account_id')
                        ->pluck('parent_account_id')
                        ->unique()
                        ->filter();

                    $q->where(function ($s) {
                        $s->whereNull('status')->orWhere('status', '')->orWhere('status', 'active');
                    })->where(function ($t) {
                        $t->whereNull('account_primary_type')
                            ->orWhereRaw('LOWER(account_primary_type) = ?', ['asset']);
                    });

                    if ($parentIds->isNotEmpty()) {
                        $q->whereNotIn('accounting_accounts.id', $parentIds);
                    }
                }),
            ],
            'internal_consumption_expense_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounting_accounts', 'id')->where(function ($q) {
                    $parentIds = AccountingAccount::query()
                        ->whereNotNull('parent_account_id')
                        ->pluck('parent_account_id')
                        ->unique()
                        ->filter();

                    $q->where(function ($s) {
                        $s->whereNull('status')->orWhere('status', '')->orWhere('status', 'active');
                    })->where(function ($t) {
                        $t->whereRaw('LOWER(account_primary_type) = ?', ['expenses'])
                            ->orWhereRaw('LOWER(account_primary_type) = ?', ['expense'])
                            ->orWhereRaw('LOWER(account_type) = ?', ['expenses'])
                            ->orWhereRaw('LOWER(account_type) = ?', ['expense'])
                            ->orWhereRaw("LEFT(REPLACE(gl_code, '.', ''), 1) = '5'");
                    });

                    if ($parentIds->isNotEmpty()) {
                        $q->whereNotIn('accounting_accounts.id', $parentIds);
                    }
                }),
            ],
            'cashier_payment_rows' => ['nullable', 'array'],
            'cashier_payment_rows.*.id' => ['nullable', 'integer', 'exists:est_establishment_payment_accounts,id'],
            'cashier_payment_rows.*.name_ar' => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.name_en' => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
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
