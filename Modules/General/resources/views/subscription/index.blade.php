@extends('layouts.app')

@section('title', __('general.subscriptions'))

@php
    $isAr = app()->getLocale() === 'ar' || session('locale') === 'ar';
    $statusLabels = [
        'active' => $isAr ? 'نشط' : 'Active',
        'expiring' => $isAr ? 'ينتهي قريباً' : 'Expiring soon',
        'expired' => $isAr ? 'منتهٍ' : 'Expired',
        'cancelled' => $isAr ? 'ملغى' : 'Cancelled',
        'none' => $isAr ? 'لا يوجد' : 'None',
        'paid' => $isAr ? 'مدفوع' : 'Paid',
    ];
    $statusBadge = [
        'active' => 'sub-pill sub-pill--ok',
        'expiring' => 'sub-pill sub-pill--warn',
        'expired' => 'sub-pill sub-pill--danger',
        'cancelled' => 'sub-pill sub-pill--danger',
        'none' => 'sub-pill',
        'paid' => 'sub-pill sub-pill--ok',
    ];
    $statusHint = match ($status ?? 'none') {
        'active' => __('general::general.subscription_status_active_hint'),
        'expiring' => __('general::general.subscription_status_expiring_hint'),
        'expired' => __('general::general.subscription_status_expired_hint'),
        default => __('general::general.subscription_status_none_hint'),
    };
    $periodLabel = function ($period) use ($isAr) {
        return match ($period) {
            'Year', 'year' => $isAr ? 'سنوي' : 'Yearly',
            'Month', 'month' => $isAr ? 'شهري' : 'Monthly',
            default => $period ?: '—',
        };
    };
    $money = function ($amount, $currency = 'SAR') {
        return number_format((float) $amount, 0).' '.($currency === 'SAR' ? __('general::general.sar') : $currency);
    };
    $activeTab = $active_tab ?? 'current';
    $currentInvoices = $current_invoices ?? collect($invoices)->where('is_current', true)->values();
    $pastInvoices = $past_invoices ?? collect($invoices)->where('is_current', false)->values();
    $pastTotal = $past_invoices_total ?? (is_countable($pastInvoices) ? count($pastInvoices) : 0);
@endphp

@section('content')
<style>
    .sub-hub {
        --sub-ink: #1a1a1a;
        --sub-muted: #6b7280;
        --sub-line: rgba(26, 26, 26, 0.08);
        --sub-soft: #faf6eb;
        --sub-accent: #ebb81e;
        --sub-accent-2: #b88912;
        --sub-ok: #15803d;
        --sub-warn: #b45309;
        --sub-danger: #b91c1c;
        --sub-shadow: 0 16px 40px rgba(26, 26, 26, 0.07);
        color: var(--sub-ink);
    }
    .sub-hub .sub-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--sub-line);
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at 12% 20%, rgba(235, 184, 30, 0.28), transparent 42%),
            radial-gradient(circle at 88% 10%, rgba(184, 137, 18, 0.14), transparent 36%),
            linear-gradient(160deg, #fffdf8 0%, #f7f1e3 100%);
        box-shadow: var(--sub-shadow);
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.25rem;
    }
    .sub-hub .sub-hero::after {
        content: "";
        position: absolute;
        inset-inline-end: -2%;
        top: -18%;
        width: 160px;
        height: 160px;
        background: rgba(235, 184, 30, 0.16);
        clip-path: polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%);
        pointer-events: none;
    }
    .sub-hub .sub-hero__row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }
    .sub-hub .sub-kicker {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        font-weight: 700;
        color: var(--sub-accent-2);
        margin-bottom: .35rem;
    }
    .sub-hub .sub-title {
        font-size: clamp(1.45rem, 2vw, 1.9rem);
        font-weight: 800;
        margin: 0 0 .35rem;
        line-height: 1.25;
    }
    .sub-hub .sub-subtitle {
        color: var(--sub-muted);
        margin: 0;
        max-width: 42rem;
        font-size: .95rem;
    }
    .sub-hub .sub-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        align-items: center;
    }
    .sub-hub .sub-btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: .85rem;
        padding: .65rem 1rem;
        font-weight: 700;
        font-size: .9rem;
        border: 1px solid transparent;
        text-decoration: none !important;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .sub-hub .sub-btn:hover { transform: translateY(-1px); }
    .sub-hub .sub-btn--primary {
        background: linear-gradient(135deg, var(--sub-accent), var(--sub-accent-2));
        color: #1a1a1a !important;
        box-shadow: 0 8px 18px rgba(235, 184, 30, 0.28);
    }
    .sub-hub .sub-btn--ghost {
        background: rgba(255,255,255,.72);
        border-color: var(--sub-line);
        color: var(--sub-ink) !important;
    }
    .sub-hub .sub-panel {
        border: 1px solid var(--sub-line);
        border-radius: 1.15rem;
        background: #fff;
        box-shadow: var(--sub-shadow);
        margin-bottom: 1.1rem;
        overflow: hidden;
        width: 100%;
    }
    .sub-hub .sub-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--sub-line);
        background: linear-gradient(180deg, #fffefb, #fbf7ee);
    }
    .sub-hub .sub-panel__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
    }
    .sub-hub .sub-panel__body { padding: 1.15rem 1.25rem 1.35rem; }
    .sub-hub .sub-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .28rem .7rem;
        font-size: .75rem;
        font-weight: 800;
        background: #f3f4f6;
        color: #374151;
    }
    .sub-hub .sub-pill--ok { background: #dcfce7; color: var(--sub-ok); }
    .sub-hub .sub-pill--warn { background: #ffedd5; color: var(--sub-warn); }
    .sub-hub .sub-pill--danger { background: #fee2e2; color: var(--sub-danger); }
    .sub-hub .sub-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }
    @media (min-width: 992px) {
        .sub-hub .sub-stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .sub-hub .sub-stat {
        border: 1px dashed rgba(26,26,26,.12);
        border-radius: .95rem;
        padding: .85rem .9rem;
        background: var(--sub-soft);
    }
    .sub-hub .sub-stat__label {
        color: var(--sub-muted);
        font-size: .75rem;
        font-weight: 700;
        margin-bottom: .2rem;
    }
    .sub-hub .sub-stat__value {
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.3;
    }
    .sub-hub .sub-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }
    .sub-hub .sub-chip {
        border-radius: .7rem;
        background: rgba(235, 184, 30, 0.14);
        color: #7a5a08;
        font-weight: 700;
        font-size: .8rem;
        padding: .4rem .7rem;
    }
    .sub-hub .sub-quota {
        border: 1px solid var(--sub-line);
        border-radius: .95rem;
        padding: .85rem;
        background: #fff;
        height: 100%;
    }
    .sub-hub .sub-quota__value {
        font-size: 1.45rem;
        font-weight: 800;
    }
    .sub-hub .sub-hint {
        margin-top: .85rem;
        padding: .75rem .9rem;
        border-radius: .85rem;
        background: rgba(235, 184, 30, 0.12);
        color: #6b520c;
        font-size: .85rem;
        font-weight: 600;
    }
    .sub-hub .sub-tabs {
        display: inline-flex;
        gap: .35rem;
        padding: .25rem;
        border-radius: .9rem;
        background: #f3eee1;
        margin-bottom: 1rem;
    }
    .sub-hub .sub-tab {
        display: inline-flex;
        align-items: center;
        border-radius: .7rem;
        padding: .5rem 1rem;
        font-weight: 800;
        font-size: .85rem;
        color: var(--sub-muted);
        text-decoration: none !important;
        border: 0;
        background: transparent;
    }
    .sub-hub .sub-tab.is-active {
        background: #fff;
        color: var(--sub-ink);
        box-shadow: 0 4px 12px rgba(26,26,26,.06);
    }
    .sub-hub .sub-invoice {
        width: 100%;
        border: 1px solid var(--sub-line);
        border-radius: 1rem;
        padding: 1.15rem 1.25rem;
        background: #fff;
        margin-bottom: .85rem;
    }
    .sub-hub .sub-invoice:last-child { margin-bottom: 0; }
    .sub-hub .sub-invoice__top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .sub-hub .sub-invoice__id {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .85rem;
        color: var(--sub-muted);
        direction: ltr;
    }
    .sub-hub .sub-invoice__label {
        font-weight: 800;
        font-size: 1.05rem;
        margin-top: .15rem;
    }
    .sub-hub .sub-invoice__meta {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem 1rem;
        margin-top: .65rem;
        color: var(--sub-muted);
        font-size: .82rem;
        font-weight: 600;
    }
    .sub-hub .sub-invoice__amount {
        font-size: 1.25rem;
        font-weight: 800;
    }
    .sub-hub .sub-lines {
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px dashed var(--sub-line);
        display: grid;
        grid-template-columns: 1fr;
        gap: .35rem;
    }
    @media (min-width: 768px) {
        .sub-hub .sub-lines { grid-template-columns: 1fr 1fr; gap: .35rem 1.5rem; }
    }
    .sub-hub .sub-line {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        font-size: .84rem;
        padding: .15rem 0;
    }
    .sub-hub .sub-empty {
        color: var(--sub-muted);
        padding: 1.25rem .25rem;
        font-weight: 600;
    }
    .sub-hub .sub-pager {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: 1.15rem;
        padding-top: 1rem;
        border-top: 1px solid var(--sub-line);
    }
    .sub-hub .sub-pager__meta {
        color: var(--sub-muted);
        font-size: .82rem;
        font-weight: 700;
    }
    .sub-hub .sub-pager__list {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .sub-hub .sub-pager__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.15rem;
        height: 2.15rem;
        padding: 0 .55rem;
        border-radius: .65rem;
        border: 1px solid var(--sub-line);
        background: #fff;
        color: var(--sub-ink);
        font-weight: 800;
        font-size: .85rem;
        text-decoration: none !important;
        line-height: 1;
    }
    .sub-hub .sub-pager__btn.is-active {
        background: var(--sub-accent);
        border-color: var(--sub-accent);
        color: #1a1a1a;
    }
    .sub-hub .sub-pager__btn.is-disabled {
        opacity: .45;
        pointer-events: none;
    }
    .sub-hub .sub-invoice-list {
        display: flex;
        flex-direction: column;
        gap: .85rem;
        width: 100%;
    }
    .sub-hub .sub-invoice { margin-bottom: 0; }
</style>

<div class="sub-hub">
    <div class="sub-hero">
        <div class="sub-hero__row">
            <div>
                <div class="sub-kicker">
                    <i class="fas fa-cube"></i>
                    @lang('general::general.subscription_hub_kicker')
                </div>
                <h1 class="sub-title">@lang('general::general.subscription_details')</h1>
                <p class="sub-subtitle">
                    @lang('general::general.subscription_hub_subtitle')
                    <span class="d-block mt-1">
                        {{ $company->name }}
                        @if ($owner?->email)
                            · <span dir="ltr">{{ $owner->email }}</span>
                        @endif
                        · <strong>{{ $money($display_price, $display_currency) }}</strong>
                    </span>
                </p>
            </div>
            <div class="sub-hero-actions">
                @if ($entitlement)
                    <a href="{{ $addon_url }}" class="sub-btn sub-btn--ghost">
                        <i class="fas fa-plus"></i>
                        @lang('general::general.buy_extra_quotas')
                    </a>
                @endif
                <a href="{{ $manage_url }}" class="sub-btn sub-btn--primary">
                    <i class="fas fa-sync-alt"></i>
                    @lang('general::general.renew_or_upgrade')
                </a>
            </div>
        </div>
    </div>

    <div class="sub-panel">
        <div class="sub-panel__head">
            <h2 class="sub-panel__title">@lang('general::general.current_plan')</h2>
            <span class="{{ $statusBadge[$status] ?? 'sub-pill' }}">
                {{ $statusLabels[$status] ?? $status }}
            </span>
        </div>
        <div class="sub-panel__body">
            @if (! $current_subscription && ! $entitlement)
                <div class="sub-empty">@lang('general::general.no_active_subscription')</div>
            @else
                <div class="sub-stat-grid mb-6">
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.subscribed_to_plan')</div>
                        <div class="sub-stat__value">{{ $plan_name }}</div>
                    </div>
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.subscription_type')</div>
                        <div class="sub-stat__value">{{ $periodLabel($display_period) }}</div>
                    </div>
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.subscription_fees')</div>
                        <div class="sub-stat__value">{{ $money($display_price, $display_currency) }}</div>
                    </div>
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.start_subscription_date')</div>
                        <div class="sub-stat__value">
                            {{ $current_subscription?->started_at ? \Illuminate\Support\Carbon::parse($current_subscription->started_at)->format('Y-m-d') : '—' }}
                        </div>
                    </div>
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.end_subscription_date')</div>
                        <div class="sub-stat__value">
                            {{ $current_subscription?->expired_at ? \Illuminate\Support\Carbon::parse($current_subscription->expired_at)->format('Y-m-d') : '—' }}
                        </div>
                    </div>
                    <div class="sub-stat">
                        <div class="sub-stat__label">@lang('general::general.days_remaining')</div>
                        <div class="sub-stat__value">
                            @if ($days_remaining === null)
                                —
                            @elseif ($days_remaining < 0)
                                <span class="text-danger">{{ abs($days_remaining) }} {{ $isAr ? 'يوم متأخر' : 'days overdue' }}</span>
                            @else
                                {{ $days_remaining }} {{ $isAr ? 'يوم' : 'days' }}
                            @endif
                        </div>
                    </div>
                </div>

                @if ($entitlement && ! empty($entitlement['coupon_code']))
                    <div class="mb-5">
                        <span class="sub-pill sub-pill--ok">
                            @lang('general::general.coupon_applied'): {{ $entitlement['coupon_code'] }}
                            @if (($entitlement['discount'] ?? 0) > 0)
                                (−{{ $money($entitlement['discount'], $entitlement['currency']) }})
                            @endif
                        </span>
                    </div>
                @endif

                <div class="mb-6">
                    <h4 class="fs-6 fw-bold mb-3">@lang('general::general.included_modules')</h4>
                    @if (count($modules))
                        <div class="sub-chips">
                            @foreach ($modules as $module)
                                <span class="sub-chip">{{ $module['name'] }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="sub-empty py-2">@lang('general::general.no_modules_listed')</div>
                    @endif
                </div>

                @if (count($quotas))
                    <div>
                        <h4 class="fs-6 fw-bold mb-3">@lang('general::general.quotas')</h4>
                        <div class="row g-3">
                            @foreach ($quotas as $quota)
                                <div class="col-md-4 col-lg-3">
                                    <div class="sub-quota">
                                        <div class="sub-stat__label">{{ $quota['name'] }}</div>
                                        <div class="sub-quota__value">{{ $quota['value'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="sub-hint">{{ $statusHint }}</div>
            @endif
        </div>
    </div>

    <div class="sub-panel">
        <div class="sub-panel__head">
            <h2 class="sub-panel__title">@lang('general::general.subscription_timeline')</h2>
        </div>
        <div class="sub-panel__body">
            <div class="sub-tabs" role="tablist">
                <a href="{{ route('subscription', ['tab' => 'current']) }}"
                   class="sub-tab {{ $activeTab === 'current' ? 'is-active' : '' }}">
                    @lang('general::general.subscription_view_current')
                    <span class="ms-1">({{ $currentInvoices->count() }})</span>
                </a>
                <a href="{{ route('subscription', ['tab' => 'past', 'page' => 1]) }}"
                   class="sub-tab {{ $activeTab === 'past' ? 'is-active' : '' }}">
                    @lang('general::general.subscription_view_history')
                    <span class="ms-1">({{ $pastTotal }})</span>
                </a>
            </div>

            @if ($activeTab === 'current')
                @if ($currentInvoices->isEmpty())
                    <div class="sub-empty">@lang('general::general.no_invoices_yet')</div>
                @else
                    <div class="sub-invoice-list">
                        @foreach ($currentInvoices as $invoice)
                            @include('general::subscription.partials.invoice-card', compact('invoice', 'periodLabel', 'money', 'statusBadge', 'statusLabels'))
                        @endforeach
                    </div>
                @endif
            @else
                @if ($pastTotal === 0)
                    <div class="sub-empty">@lang('general::general.no_previous_subscriptions')</div>
                @else
                    <div class="sub-invoice-list">
                        @foreach ($pastInvoices->items() as $invoice)
                            @include('general::subscription.partials.invoice-card', compact('invoice', 'periodLabel', 'money', 'statusBadge', 'statusLabels'))
                        @endforeach
                    </div>

                    @include('general::subscription.partials.pagination', ['paginator' => $pastInvoices])
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
