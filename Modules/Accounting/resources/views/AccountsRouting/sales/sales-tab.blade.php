<div class="row">
    @php
        $routingMapping = [
            // 'sales_client' => 'client',
            'sales_sales' => 'sales',
            'sales_vat_calculation' => 'vat_calculation',
            // 'sales_total_amount' => 'total_amount',
            // 'sales_amount_before_vat' => 'amount_before_vat',
            'sales_discount_allowed' => 'discount_allowed',
            'sales_sell_return' => 'sell_return',
        ];
    @endphp

    @foreach ($routingMapping as $type => $field)
        @php
            $selectedRouting = $accountsRoting->where('type', $type)->where('section', 'sales')->first();
            $fallbackAccountId = ($type === 'sales_discount_allowed') ? ($defaultDiscountAccountId ?? '') : '';
        @endphp

        <div class="col-6">
            <x-accounting::account-routing :section="'sales_routing'" :title="__('accounting::lang.' . $field)"
                :typeSelectId="'sales_' . $field . '_type_route'" :typeSelectName="'sales_' . $field . '_type_route'"
                :accountSelectId="'sales_' . $field . '_account'" :accountSelectName="'sales_' . $field . '_account'"
                :accounts="$accounts" :typeOptions="$options"
                :selectedType="optional($selectedRouting)->direction ?? ''"
                :selectedAccount="optional($selectedRouting)->account_id ?? $fallbackAccountId" />
        </div>
    @endforeach
</div>
