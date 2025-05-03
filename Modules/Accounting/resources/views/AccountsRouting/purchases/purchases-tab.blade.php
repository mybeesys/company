<div class="row">
    @php
        $routingMapping = [
            'purchases_suppliers' => 'suppliers',
            'purchases_purchase' => 'purchase',
            'purchases_vat_calculation' => 'vat_calculation',
            // 'purchases_total_amount' => 'total_amount',
            // 'purchases_amount_before_vat' => 'amount_before_vat',
            'purchases_discount_calculation' => 'discount_calculation',
            'purchases_purchase_return' => 'purchase_return',
        ];
    @endphp

    @foreach ($routingMapping as $type => $field)
        @php
            $selectedRouting = $accountsRoting->where('type', $type)->where('section', 'purchases')->first();
        @endphp

        <div class="col-6">
            <x-accounting::account-routing :section="'purchases_routing'" :title="__('accounting::lang.' . $field)" :typeSelectId="'purchases_' . $field . '_type_route'" :typeSelectName="'purchases_' . $field . '_type_route'"
                :accountSelectId="'purchases_' . $field . '_account'" :accountSelectName="'purchases_' . $field . '_account'" :accounts="$accounts" :typeOptions="$options" :selectedType="optional($selectedRouting)->direction ?? ''"
                :selectedAccount="optional($selectedRouting)->account_id ?? ''" />
        </div>
    @endforeach
</div>
