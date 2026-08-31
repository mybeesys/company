@php
    $displayName = app()->getLocale() === 'ar' ? $account->name_ar : $account->name_en;
    $displayBalance = (float) ($account->coa_display_balance ?? $account->balance ?? 0);
    $isAggregated = (bool) ($account->coa_balance_is_aggregated ?? false);
    $isViolation = (bool) ($account->coa_is_structure_violation ?? false);
@endphp

<span class="coa-node-label-inner">
    <span class="coa-node-title">
        @if (app()->getLocale() == 'ar')
            @if (!empty($account->gl_code))
                ({{ $account->gl_code }}) -
            @endif
            {{ $displayName }}
        @else
            @if (!empty($account->gl_code))
                ({{ $account->gl_code }})
            @endif
            - {{ $displayName }}
        @endif
    </span>
    <span class="coa-balance-chip {{ $isAggregated ? 'coa-balance-aggregated' : 'coa-balance-direct' }}"
        dir="ltr"
        @if ($isAggregated) title="@lang('accounting::lang.coa_aggregated_balance_tooltip')" @endif>
        @format_currency($displayBalance)
    </span>
    @if ($isViolation)
        <i class="fas fa-exclamation-circle text-warning coa-violation-icon"
            title="@lang('accounting::lang.coa_structure_violation_tooltip', ['gl' => $account->gl_code ?? $account->id])"></i>
    @endif
    @if ($account->status == 'active')
        <span class="coa-status-badge coa-status-badge--active" title="@lang('accounting::lang.active')">
            <i class="fas fa-check-circle coa-status-icon coa-status-icon--active" aria-hidden="true"></i>
        </span>
    @elseif($account->status == 'inactive')
        <span class="coa-status-badge coa-status-badge--inactive" title="@lang('lang_v1.inactive')">
            <i class="fas fa-ban coa-status-icon coa-status-icon--inactive" aria-hidden="true"></i>
        </span>
    @endif
</span>
