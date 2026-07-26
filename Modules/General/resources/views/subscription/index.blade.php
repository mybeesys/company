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
        'active' => 'badge-light-success',
        'expiring' => 'badge-light-warning',
        'expired' => 'badge-light-danger',
        'cancelled' => 'badge-light-danger',
        'none' => 'badge-light-secondary',
        'paid' => 'badge-light-success',
    ];
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
@endphp

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-8">
        <div>
            <h1 class="fs-2hx fw-bold text-gray-900 mb-1">@lang('general::general.subscription_details')</h1>
            <div class="text-gray-600">
                {{ $company->name }}
                @if ($owner?->email)
                    · <span class="text-gray-500" dir="ltr">{{ $owner->email }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $manage_url }}" target="_blank" rel="noopener" class="btn btn-warning">
                <i class="fas fa-sync-alt me-1"></i>
                @lang('general::general.renew_or_upgrade')
            </a>
        </div>
    </div>

    <div class="row g-5 g-xl-8">
        <div class="col-xl-8">
            <div class="card card-flush mb-5">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <h2 class="fw-bold mb-0">@lang('general::general.current_plan')</h2>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge {{ $statusBadge[$status] ?? 'badge-light' }} fs-7 fw-bold">
                            {{ $statusLabels[$status] ?? $status }}
                        </span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @if (! $current_subscription && ! $entitlement)
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
                            <div class="fw-semibold">
                                @lang('general::general.no_active_subscription')
                            </div>
                        </div>
                    @else
                        <div class="row g-5 mb-8">
                            <div class="col-md-6">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.subscribed_to_plan')</div>
                                <div class="fs-3 fw-bold text-gray-900">{{ $plan_name }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.subscription_type')</div>
                                <div class="fs-5 fw-bold text-gray-800">
                                    {{ $periodLabel($display_period) }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.subscription_fees')</div>
                                <div class="fs-5 fw-bold text-gray-800">
                                    {{ $money($display_price, $display_currency) }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.start_subscription_date')</div>
                                <div class="fs-6 fw-semibold text-gray-800">
                                    {{ $current_subscription?->started_at ? \Illuminate\Support\Carbon::parse($current_subscription->started_at)->format('Y-m-d') : '—' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.end_subscription_date')</div>
                                <div class="fs-6 fw-semibold text-gray-800">
                                    {{ $current_subscription?->expired_at ? \Illuminate\Support\Carbon::parse($current_subscription->expired_at)->format('Y-m-d') : '—' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-gray-500 fs-7 mb-1">@lang('general::general.days_remaining')</div>
                                <div class="fs-6 fw-semibold text-gray-800">
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
                            <div class="mb-8">
                                <span class="badge badge-light-success fs-7">
                                    @lang('general::general.coupon_applied'): {{ $entitlement['coupon_code'] }}
                                    @if (($entitlement['discount'] ?? 0) > 0)
                                        (−{{ $money($entitlement['discount'], $entitlement['currency']) }})
                                    @endif
                                </span>
                            </div>
                        @endif

                        <div class="mb-8">
                            <h4 class="fs-5 fw-bold text-gray-900 mb-4">@lang('general::general.included_modules')</h4>
                            @if (count($modules))
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($modules as $module)
                                        <span class="badge badge-light-primary fs-7 fw-bold px-4 py-3">
                                            {{ $module['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted">@lang('general::general.no_modules_listed')</div>
                            @endif
                        </div>

                        @if (count($quotas))
                            <div>
                                <h4 class="fs-5 fw-bold text-gray-900 mb-4">@lang('general::general.quotas')</h4>
                                <div class="row g-4">
                                    @foreach ($quotas as $quota)
                                        <div class="col-md-4">
                                            <div class="border border-dashed border-gray-300 rounded p-5">
                                                <div class="text-gray-500 fs-7 mb-1">{{ $quota['name'] }}</div>
                                                <div class="fs-2 fw-bold text-gray-900">{{ $quota['value'] }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card card-flush mb-5">
                <div class="card-header py-5">
                    <h2 class="card-title fw-bold mb-0">@lang('general::general.invoices')</h2>
                </div>
                <div class="card-body pt-0">
                    @if (count($invoices) === 0)
                        <div class="text-muted py-6">@lang('general::general.no_invoices_yet')</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4 mb-0">
                                <thead class="border-bottom border-gray-200 text-start text-gray-500 fw-bold fs-7 text-uppercase">
                                    <tr>
                                        <th>@lang('general::general.invoice_id')</th>
                                        <th>@lang('general::general.invoice_package')</th>
                                        <th>@lang('general::general.subscription_type')</th>
                                        <th>@lang('general::general.amount')</th>
                                        <th>@lang('employee::fields.status')</th>
                                        <th>@lang('general::general.start_subscription_date')</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td dir="ltr">{{ $invoice['id'] }}</td>
                                            <td>{{ $invoice['label'] }}</td>
                                            <td>{{ $periodLabel($invoice['period'] ?? null) }}</td>
                                            <td>{{ $money($invoice['amount'], $invoice['currency'] ?? 'SAR') }}</td>
                                            <td>
                                                <span class="badge {{ $statusBadge[$invoice['status']] ?? 'badge-light' }}">
                                                    {{ $statusLabels[$invoice['status']] ?? $invoice['status'] }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $invoice['date'] ? \Illuminate\Support\Carbon::parse($invoice['date'])->format('Y-m-d') : '—' }}
                                            </td>
                                        </tr>
                                        @if (! empty($invoice['line_items']))
                                            <tr>
                                                <td colspan="6" class="bg-light">
                                                    <div class="px-2 py-2">
                                                        <div class="text-gray-500 fs-8 fw-bold mb-2">@lang('general::general.invoice_breakdown')</div>
                                                        <div class="d-flex flex-column gap-1">
                                                            @foreach ($invoice['line_items'] as $line)
                                                                <div class="d-flex justify-content-between fs-7">
                                                                    <span>{{ $line['label'] ?? ($line['key'] ?? '—') }}</span>
                                                                    <span class="fw-bold {{ (($line['total'] ?? 0) < 0) ? 'text-success' : '' }}">
                                                                        {{ $money($line['total'] ?? 0, $invoice['currency'] ?? 'SAR') }}
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card card-flush mb-5">
                <div class="card-header py-5">
                    <h2 class="card-title fw-bold mb-0">@lang('general::general.previous_subscriptions')</h2>
                </div>
                <div class="card-body pt-0">
                    @if (count($history) === 0)
                        <div class="text-muted py-6">@lang('general::general.no_previous_subscriptions')</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-6 gy-4 mb-0">
                                <thead class="border-bottom border-gray-200 text-start text-gray-500 fw-bold fs-7 text-uppercase">
                                    <tr>
                                        <th>@lang('employee::fields.plan')</th>
                                        <th>@lang('general::general.start_subscription_date')</th>
                                        <th>@lang('general::general.end_subscription_date')</th>
                                        <th>@lang('employee::fields.status')</th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-700">
                                    @foreach ($history as $row)
                                        <tr>
                                            <td>
                                                {{ $row['plan_name'] }}
                                                @if ($row['is_current'])
                                                    <span class="badge badge-light-primary ms-2">@lang('general::general.current')</span>
                                                @endif
                                            </td>
                                            <td>{{ $row['started_at'] ? \Illuminate\Support\Carbon::parse($row['started_at'])->format('Y-m-d') : '—' }}</td>
                                            <td>{{ $row['expired_at'] ? \Illuminate\Support\Carbon::parse($row['expired_at'])->format('Y-m-d') : '—' }}</td>
                                            <td>
                                                @if ($row['suppressed_at'])
                                                    <span class="badge badge-light-danger">{{ $statusLabels['cancelled'] }}</span>
                                                @elseif ($row['is_current'])
                                                    <span class="badge {{ $statusBadge[$status] ?? 'badge-light-success' }}">{{ $statusLabels[$status] ?? $status }}</span>
                                                @else
                                                    <span class="badge badge-light-secondary">{{ $statusLabels['expired'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header py-5">
                    <h2 class="card-title fw-bold mb-0">@lang('general::general.summary')</h2>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center mb-7">
                        <div class="symbol symbol-50px symbol-circle me-4 bg-light-warning">
                            <span class="symbol-label fs-3 fw-bold text-warning">
                                {{ mb_substr($company->name ?? 'C', 0, 1) }}
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fs-5 fw-bold text-gray-900">{{ $company->name }}</span>
                            @if ($owner?->email)
                                <span class="text-gray-600" dir="ltr">{{ $owner->email }}</span>
                            @endif
                            @if ($owner->phone_number ?? $company->owner_phone_number ?? $company->phone ?? null)
                                <span class="text-gray-500" dir="ltr">{{ $owner->phone_number ?? $company->owner_phone_number ?? $company->phone }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="separator separator-dashed mb-7"></div>

                    <div class="mb-7">
                        <div class="text-gray-500 fs-7 mb-1">@lang('general::general.subscription_price')</div>
                        <div class="fs-2hx fw-bold text-gray-900">
                            {{ $money($display_price, $display_currency) }}
                        </div>
                        @if ($entitlement)
                            <div class="text-gray-500 fs-7 mt-1">
                                {{ $money($entitlement['monthly_subtotal'], $entitlement['currency']) }}
                                / @lang('general::general.per_month')
                            </div>
                        @endif
                    </div>

                    <div class="separator separator-dashed mb-7"></div>

                    <a href="{{ $manage_url }}" target="_blank" rel="noopener" class="btn btn-warning w-100 mb-3">
                        @lang('general::general.renew_or_upgrade')
                    </a>
                    <div class="text-muted fs-8 text-center">
                        @lang('general::general.renew_hint')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
