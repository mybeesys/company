<div class="row">
    <div class="col-12 mb-4">
        <div class="alert alert-light-primary border border-primary border-dashed">
            <div class="fw-bold mb-1">@lang('accounting::fiscal_close.routing_intro_title')</div>
            <div class="text-muted fs-7">@lang('accounting::fiscal_close.routing_intro_body')</div>
        </div>
    </div>

    @php
        $fiscalCloseMapping = [
            'fiscal_close_current_period_result' => 'current_period_result',
            'fiscal_close_retained_earnings' => 'retained_earnings',
        ];
    @endphp

    @foreach ($fiscalCloseMapping as $type => $field)
        @php
            $selectedRouting = $accountsRoting->where('type', $type)->where('section', 'fiscal_close')->first();
        @endphp

        <div class="col-md-6">
            <x-accounting::account-routing
                :section="'fiscal_close_routing'"
                :title="__('accounting::fiscal_close.' . $field)"
                :typeSelectId="'fiscal_close_' . $field . '_type_route'"
                :typeSelectName="'fiscal_close_' . $field . '_type_route'"
                :accountSelectId="'fiscal_close_' . $field . '_account'"
                :accountSelectName="'fiscal_close_' . $field . '_account'"
                :accounts="$equityAccounts ?? $accounts"
                :typeOptions="$options"
                :selectedType="optional($selectedRouting)->direction ?? ''"
                :selectedAccount="optional($selectedRouting)->account_id ?? ''"
            />
            <div class="text-muted fs-8 px-2 pb-3">@lang('accounting::fiscal_close.' . $field . '_help')</div>
        </div>
    @endforeach
</div>
