@extends('layouts.app')

@section('title', __('report::fields.register_details'))

@section('css')
<style>
    .rrd-wrap {
        --rrd-radius: 16px;
        --rrd-border: #eef1f6;
        --rrd-brand: var(--bs-primary);
        --rrd-brand-light: var(--bs-primary-light);
        --rrd-brand-subtle: var(--bs-primary-bg-subtle, #f8efcf);
        --rrd-brand-border: var(--bs-primary-border-subtle, #eed592);
        --rrd-brand-dark: var(--bs-text-primary, #c99a19);
    }

    .rrd-hero {
        background: linear-gradient(135deg, #ffffff 0%, var(--rrd-brand-light) 55%, var(--rrd-brand-subtle) 100%);
        border: 1px solid var(--rrd-brand-border);
        border-radius: var(--rrd-radius);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
    }

    .rrd-section {
        background: #fff;
        border: 1px solid var(--rrd-border);
        border-radius: var(--rrd-radius);
        margin-bottom: 1.25rem;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
    }

    .rrd-section-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--rrd-border);
        background: var(--rrd-brand-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .rrd-section-body {
        padding: 1.25rem;
    }

    .rrd-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    @media (max-width: 1199px) {
        .rrd-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 575px) {
        .rrd-kpi-grid { grid-template-columns: 1fr; }
    }

    .rrd-kpi {
        border: 1px solid var(--rrd-border);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        background: #fff;
    }

    .rrd-kpi-label {
        font-size: .78rem;
        font-weight: 600;
        color: #a1a5b7;
        margin-bottom: .35rem;
    }

    .rrd-kpi-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #181c32;
        font-variant-numeric: tabular-nums;
    }

    .rrd-kpi--accent .rrd-kpi-value {
        color: var(--rrd-brand-dark);
    }

    .rrd-section-body--flush {
        padding: 0;
    }

    .rrd-section-body--flush .table-responsive {
        padding: 0.35rem 0.65rem 0.85rem;
    }

    .rrd-section-body--table {
        padding: 0.5rem 1.15rem 1.15rem;
    }

    .rrd-detail-table {
        margin-bottom: 0 !important;
    }

    .rrd-detail-table thead th {
        background: var(--rrd-brand-light) !important;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
        color: #5e6278 !important;
        white-space: nowrap;
        vertical-align: middle;
        padding: 0.8rem 1.1rem !important;
        border-color: #eff2f5 !important;
    }

    .rrd-detail-table tbody td,
    .rrd-detail-table tbody th,
    .rrd-detail-table tfoot th,
    .rrd-detail-table tfoot td {
        font-variant-numeric: tabular-nums;
        vertical-align: middle;
        padding: 0.75rem 1.1rem !important;
        border-color: #eff2f5;
    }

    .rrd-detail-table tfoot th,
    .rrd-detail-table tfoot td {
        background: var(--rrd-brand-subtle);
        font-weight: 700;
        border-top: 2px solid var(--rrd-brand-border);
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
    }

    .rrd-detail-table thead th:first-child,
    .rrd-detail-table tbody td:first-child,
    .rrd-detail-table tbody th:first-child,
    .rrd-detail-table tfoot th:first-child,
    .rrd-detail-table tfoot td:first-child {
        padding-inline-start: 1.25rem !important;
    }

    .rrd-detail-table thead th:last-child,
    .rrd-detail-table tbody td:last-child,
    .rrd-detail-table tbody th:last-child,
    .rrd-detail-table tfoot th:last-child,
    .rrd-detail-table tfoot td:last-child {
        padding-inline-end: 1.25rem !important;
    }

    .rrd-meta-list {
        display: grid;
        gap: .65rem;
    }

    .rrd-meta-item {
        display: flex;
        gap: .5rem;
        font-size: .92rem;
    }

    .rrd-meta-item strong {
        min-width: 120px;
        color: #5e6278;
    }

    .rrd-tx-type {
        display: inline-flex;
        align-items: center;
        padding: .2rem .55rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        background: var(--rrd-brand-subtle);
        color: var(--rrd-brand-dark);
    }

    .rrd-tx-type.is-refund {
        background: #fff5f8;
        color: #f1416c;
    }

    .rrd-tx-type.is-expense {
        background: #fff8dd;
        color: #946f11;
    }

    @media print {
        .rrd-no-print { display: none !important; }
        .rrd-section { break-inside: avoid; box-shadow: none; }
    }
</style>
@stop

@section('content')
@php
    $register = $register_details;
    $isOpen = ($register->status ?? '') === 'open';
    $openLabel = \Carbon\Carbon::parse($register->open_time)->format('d/m/Y h:i A');
    $closeLabel = ! empty($register->closed_at)
        ? \Carbon\Carbon::parse($register->closed_at)->format('d/m/Y h:i A')
        : \Carbon\Carbon::parse($close_time)->format('d/m/Y h:i A');

    $txTypeLabels = [
        'sell' => __('report::fields.sale'),
        'sell-return' => __('report::fields.refund'),
        'purchases' => __('report::fields.expense'),
        'refund' => __('report::fields.refund'),
        'initial' => __('report::fields.cash_in_hand'),
    ];

    $payMethodLabels = $payment_types->mapWithKeys(function ($method) {
        return [$method->name_en => app()->getLocale() === 'ar' ? $method->name_ar : $method->name_en];
    });
@endphp

<div class="rrd-wrap">
    <div class="rrd-hero">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <h2 class="fs-3 fw-bold text-gray-900 mb-0">@lang('report::fields.register_details')</h2>
                    <span class="badge {{ $isOpen ? 'badge-light-success' : 'badge-light-secondary' }}">
                        {{ $isOpen ? __('report::fields.open') : __('report::fields.close') }}
                    </span>
                </div>
                <p class="text-muted fs-7 mb-1">
                    {{ $openLabel }} — {{ $closeLabel }}
                </p>
                <p class="text-gray-700 fs-7 mb-0">
                    <i class="bi bi-geo-alt me-1"></i>{{ $register->location_name }}
                    <span class="mx-2 text-muted">|</span>
                    <i class="bi bi-person me-1"></i>{{ $register->user_name }}
                </p>
            </div>
            <div class="d-flex gap-2 rrd-no-print">
                <a href="{{ url('Register-Report') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i>@lang('accounting::lang.back')
                </a>
                <a href="{{ route('register-report.print', $register->id) }}" target="_blank" rel="noopener" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>@lang('report::fields.print')
                </a>
            </div>
        </div>
    </div>

    <div class="rrd-kpi-grid">
        <div class="rrd-kpi rrd-kpi--accent">
            <div class="rrd-kpi-label">@lang('report::fields.cash_in_hand')</div>
            <div class="rrd-kpi-value">@format_currency($register->cash_in_hand ?? 0)</div>
        </div>
        <div class="rrd-kpi">
            <div class="rrd-kpi-label">@lang('report::fields.total_sales')</div>
            <div class="rrd-kpi-value">@format_currency($register->total_sale ?? 0)</div>
        </div>
        <div class="rrd-kpi">
            <div class="rrd-kpi-label">@lang('report::fields.total_refund')</div>
            <div class="rrd-kpi-value">@format_currency($register->total_refund ?? 0)</div>
        </div>
        <div class="rrd-kpi">
            <div class="rrd-kpi-label">@lang('report::fields.total_expense')</div>
            <div class="rrd-kpi-value">@format_currency($register->total_expense ?? 0)</div>
        </div>
    </div>

    <div class="rrd-section">
        <div class="rrd-section-header">
            <h3 class="fs-5 fw-bold mb-0">@lang('report::fields.register_transactions')</h3>
            <span class="badge badge-light-primary">{{ $register_transactions->count() }}</span>
        </div>
        <div class="rrd-section-body rrd-section-body--flush">
            <div class="table-responsive">
                <table class="table table-row-bordered table-hover align-middle rr-detail-table">
                    <thead>
                        <tr>
                            <th>@lang('report::fields.transaction_date')</th>
                            <th>@lang('report::fields.transaction_type')</th>
                            <th>@lang('report::fields.payment_method')</th>
                            <th class="text-end">@lang('report::fields.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($register_transactions as $tx)
                            @php
                                $txType = strtolower((string) ($tx->transaction_type ?? ''));
                                $typeClass = in_array($txType, ['refund', 'sell-return'], true)
                                    ? 'is-refund'
                                    : (in_array($txType, ['purchases'], true) ? 'is-expense' : '');
                                $typeLabel = $txTypeLabels[$txType]
                                    ?? (\Illuminate\Support\Facades\Lang::has('accounting::lang.'.$txType)
                                        ? __('accounting::lang.'.$txType)
                                        : ($tx->transaction_type ?: '—'));
                                $payKey = strtolower((string) ($tx->pay_method ?? ''));
                                $payLabel = app()->getLocale() === 'ar' && ! empty($tx->pay_method_ar)
                                    ? $tx->pay_method_ar
                                    : ($payMethodLabels[$payKey] ?? ($tx->pay_method ?: '—'));
                                $invoiceRef = ! empty($tx->invoice_no) ? ' · '.$tx->invoice_no : '';
                            @endphp
                            <tr>
                                <td>{{ $tx->created_at ? \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y h:i A') : '—' }}</td>
                                <td><span class="rrd-tx-type {{ $typeClass }}">{{ $typeLabel }}{{ $invoiceRef }}</span></td>
                                <td>{{ $payLabel }}</td>
                                <td class="text-end fw-semibold">@format_currency($tx->amount ?? 0)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">@lang('report::fields.no_register_transactions')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="rrd-section h-100">
                <div class="rrd-section-header">
                    <h3 class="fs-5 fw-bold mb-0">@lang('report::fields.payment_method')</h3>
                </div>
                <div class="rrd-section-body rrd-section-body--flush">
                    @include('report::report.payment_details', [
                        'details' => $details,
                        'register_details' => $register_details,
                        'payment_types' => $payment_types,
                    ])
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="rrd-section h-100">
                <div class="rrd-section-header">
                    <h3 class="fs-5 fw-bold mb-0">@lang('report::fields.summary')</h3>
                </div>
                <div class="rrd-section-body">
                    <div class="rrd-meta-list mb-4">
                        <div class="rrd-meta-item"><strong>@lang('report::fields.user')</strong><span>{{ $register->user_name }}</span></div>
                        <div class="rrd-meta-item"><strong>@lang('report::fields.email')</strong><span>{{ $register->email ?: '—' }}</span></div>
                        <div class="rrd-meta-item"><strong>@lang('report::fields.business_location')</strong><span>{{ $register->location_name }}</span></div>
                        @if (! empty($register->closing_note))
                            <div class="rrd-meta-item"><strong>@lang('report::fields.closing_note')</strong><span>{{ $register->closing_note }}</span></div>
                        @endif
                    </div>
                    <div class="table-responsive">
                    <table class="table table-row-bordered align-middle rr-detail-table">
                        <tbody>
                            <tr>
                                <th>@lang('report::fields.total_sales')</th>
                                <td class="text-end">@format_currency($details['transaction_details']->total_sales ?? 0)</td>
                            </tr>
                            <tr>
                                <th>@lang('report::fields.total_tax')</th>
                                <td class="text-end">@format_currency($details['transaction_details']->total_tax ?? 0)</td>
                            </tr>
                            <tr>
                                <th>@lang('report::fields.total_discount')</th>
                                <td class="text-end">@format_currency($details['transaction_details']->total_discount ?? 0)</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! empty($register->denominations))
        @php $denomTotal = 0; @endphp
        <div class="rrd-section">
            <div class="rrd-section-header">
                <h3 class="fs-5 fw-bold mb-0">@lang('report::fields.cash_denominations')</h3>
            </div>
            <div class="rrd-section-body rrd-section-body--flush">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-hover align-middle rr-detail-table">
                        <thead>
                            <tr>
                                <th>@lang('report::fields.denomination')</th>
                                <th class="text-center">@lang('report::fields.count')</th>
                                <th class="text-end">@lang('report::fields.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($register->denominations as $key => $value)
                                @php $denomTotal += ($key * $value); @endphp
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td class="text-center">{{ $value ?? 0 }}</td>
                                    <td class="text-end">@format_currency($key * $value)</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">@lang('report::fields.total')</th>
                                <th class="text-end">@format_currency($denomTotal)</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if (! empty($details['product_details']) && $details['product_details']->count() > 0)
        <div class="rrd-section">
            <div class="rrd-section-header">
                <h3 class="fs-5 fw-bold mb-0">@lang('report::fields.products')</h3>
            </div>
            <div class="rrd-section-body rrd-section-body--flush">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-hover align-middle rr-detail-table">
                        <thead>
                            <tr>
                                <th>@lang('report::fields.product')</th>
                                <th class="text-center">@lang('report::fields.quantity')</th>
                                <th class="text-end">@lang('report::fields.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($details['product_details'] as $product)
                                <tr>
                                    <td>
                                        {{ app()->getLocale() === 'ar' ? $product->product_name_ar : $product->product_name_en }}
                                    </td>
                                    <td class="text-center">{{ $product->total_quantity }}</td>
                                    <td class="text-end">@format_currency($product->total_amount)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
