<?php

namespace Modules\Zatca\Services;

use Illuminate\Http\Request;
use Modules\Zatca\Models\ZatcaSetting;

/**
 * Applies ZATCA operations business rules onto sell create requests.
 */
class ZatcaSalesRulesApplier
{
    public function applyToRequest(Request $request): void
    {
        if (! config('zatca.show_in_menu', true)) {
            return;
        }

        $setting = ZatcaSetting::current();

        if ((bool) $setting->disable_discount) {
            $request->merge([
                'invoice_discount' => 0,
                'invoiced_discount' => 0,
                'invoiced_discount_type' => 'fixed',
                'coupon_code' => '',
            ]);
        } elseif (config('zatca.ops_rules.default_sales_discount', false)
            && (float) $setting->default_sales_discount > 0
            && ! $request->filled('invoice_discount')
            && ! $request->filled('invoiced_discount')) {
            $request->merge([
                'invoice_discount' => (float) $setting->default_sales_discount,
                'invoiced_discount' => (float) $setting->default_sales_discount,
                'invoiced_discount_type' => 'percentage',
            ]);
        }

        if (config('zatca.ops_rules.disable_order_tax', false)
            && (bool) $setting->disable_order_tax) {
            $request->merge([
                'service_fee_tax' => 0,
                'order_tax' => 0,
                'order_tax_id' => null,
            ]);
        }
    }
}
