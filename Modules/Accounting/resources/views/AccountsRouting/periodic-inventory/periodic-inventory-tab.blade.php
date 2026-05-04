<div class="row">
    @if (! $isPeriodicInventoryPolicy)
        <div class="col-12 mb-4">
            <div class="alert alert-warning mb-0">
                <i class="fas fa-lock me-2"></i>
                @lang('accounting::lang.periodic_inventory_routing_locked')
            </div>
        </div>
    @endif
    @php
        $routingMapping = [
            'periodic_inventory_adjustment' => 'adjustment',
        ];
    @endphp

    @foreach ($routingMapping as $type => $field)
        @php
            $selectedRouting = $accountsRoting->where('type', $type)->where('section', 'periodic_inventory')->first();
        @endphp

        <div class="col-6 @if (! $isPeriodicInventoryPolicy) opacity-50 @endif" @if (! $isPeriodicInventoryPolicy) style="pointer-events: none;" @endif>
            <x-accounting::account-routing
                :section="'periodic_inventory_routing'"
                :title="__('accounting::lang.periodic_inventory_adjustment_account')"
                :typeSelectId="'periodic_inventory_' . $field . '_type_route'"
                :typeSelectName="'periodic_inventory_' . $field . '_type_route'"
                :accountSelectId="'periodic_inventory_' . $field . '_account'"
                :accountSelectName="'periodic_inventory_' . $field . '_account'"
                :accounts="$accounts"
                :typeOptions="$options"
                :selectedType="optional($selectedRouting)->direction ?? ''"
                :selectedAccount="optional($selectedRouting)->account_id ?? ''" />
        </div>
    @endforeach
</div>
