<!DOCTYPE html>
@php
    $localeAr = app()->getLocale() === 'ar';
    $dir = $localeAr ? 'rtl' : 'ltr';
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

    $paymentRows = [];
    foreach ($payment_types as $method) {
        $saleField = \Modules\Report\Support\RegisterShiftReport::PAYMENT_FIELD_MAP[$method->name_en] ?? null;
        $refundField = $saleField ? $saleField.'_refund' : null;
        $paymentRows[] = [
            'label' => app()->getLocale() === 'ar' ? $method->name_ar : $method->name_en,
            'sale' => $saleField ? (float) ($register->$saleField ?? 0) : 0.0,
            'refund' => $refundField ? (float) ($register->$refundField ?? 0) : 0.0,
        ];
    }

    $companyName = (string) config('app.name');
    $companyNameAr = $companyName;
    $companyLogoUrl = '';
    $companyTaxNumber = '';

    if (function_exists('get_company_id') && get_company_id()) {
        $row = \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('companies')
            ->find(get_company_id());
        if ($row) {
            $companyName = (string) ($row->name ?? $companyName);
            $companyNameAr = (string) ($row->name_ar ?? $row->name ?? $companyName);
            $companyTaxNumber = trim((string) ($row->tax_number ?? ''));
            foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
                if (! empty($row->{$col})) {
                    $path = (string) $row->{$col};
                    $companyLogoUrl = function_exists('central_public_storage_url_for_path')
                        ? central_public_storage_url_for_path($path)
                        : $path;
                    break;
                }
            }
        }
    }

    $denomTotal = 0;
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('report::fields.register_details') — #{{ $register->id }}</title>
    <style>
        * {
            font-family: DejaVu Sans, 'Segoe UI', Tahoma, sans-serif !important;
            box-sizing: border-box;
        }

        body {
            font-size: 11px;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 14px 16px 28px;
            background: #fff;
            text-align: {{ $localeAr ? 'right' : 'left' }};
        }

        @page {
            size: A4 portrait;
            margin: 12mm 10mm 16mm;
        }

        .rrd-print-header {
            border-bottom: 2px solid #c99a19;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .rrd-print-header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .rrd-print-header-brand,
        .rrd-print-header-meta {
            display: table-cell;
            vertical-align: top;
        }

        .rrd-print-header-meta {
            text-align: {{ $localeAr ? 'left' : 'right' }};
            font-size: 10px;
            color: #64748b;
            white-space: nowrap;
        }

        .rrd-print-logo {
            max-height: 48px;
            max-width: 110px;
            object-fit: contain;
            vertical-align: middle;
            margin-{{ $localeAr ? 'left' : 'right' }}: 10px;
        }

        .rrd-print-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px;
        }

        .rrd-print-subtitle {
            font-size: 11px;
            color: #475569;
        }

        .rrd-print-session {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 14px;
        }

        .rrd-print-session-grid {
            display: table;
            width: 100%;
        }

        .rrd-print-session-cell {
            display: table-cell;
            width: 25%;
            vertical-align: top;
            padding: 0 6px;
        }

        .rrd-print-session-cell:first-child {
            padding-{{ $localeAr ? 'right' : 'left' }}: 0;
        }

        .rrd-print-session-cell:last-child {
            padding-{{ $localeAr ? 'left' : 'right' }}: 0;
        }

        .rrd-print-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .rrd-print-value {
            font-size: 11px;
            font-weight: 600;
            color: #0f172a;
        }

        .rrd-print-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            color: #475569;
        }

        .rrd-print-status.is-open {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .rrd-print-kpi {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .rrd-print-kpi td {
            width: 25%;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: center;
            vertical-align: top;
        }

        .rrd-print-kpi .rrd-print-label {
            margin-bottom: 4px;
        }

        .rrd-print-kpi .rrd-print-value {
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }

        .rrd-print-kpi td:first-child .rrd-print-value {
            color: #946f11;
        }

        .rrd-print-section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .rrd-print-section-title {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            padding: 6px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 6px;
        }

        .rrd-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .rrd-print-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            padding: 7px 8px;
            border: 1px solid #e2e8f0;
        }

        .rrd-print-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: middle;
            font-variant-numeric: tabular-nums;
        }

        .rrd-print-table tfoot th,
        .rrd-print-table tfoot td {
            background: #f8fafc;
            font-weight: 700;
            border-top: 2px solid #cbd5e1;
        }

        .text-end {
            text-align: {{ $localeAr ? 'left' : 'right' }} !important;
        }

        .text-center {
            text-align: center !important;
        }

        .rrd-print-two-col {
            display: table;
            width: 100%;
            border-spacing: 12px 0;
            margin: 0 -12px 14px;
        }

        .rrd-print-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .rrd-print-meta {
            margin-bottom: 8px;
        }

        .rrd-print-meta-row {
            padding: 4px 0;
            border-bottom: 1px dotted #e2e8f0;
            font-size: 10px;
        }

        .rrd-print-meta-row strong {
            color: #64748b;
            font-weight: 600;
            min-width: 90px;
            display: inline-block;
        }

        .rrd-print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            padding: 6px;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }

        .rrd-print-toolbar {
            margin-bottom: 12px;
            text-align: center;
        }

        .rrd-print-toolbar button {
            font-family: inherit;
            padding: 6px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            cursor: pointer;
            font-size: 11px;
        }

        @media print {
            .rrd-no-print {
                display: none !important;
            }

            .rrd-print-footer {
                position: fixed;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        };

        window.onafterprint = function() {
            window.location.href = '{{ url('Register-Report/'.$register->id) }}';
        };
    </script>
</head>

<body>
    <div class="rrd-print-toolbar rrd-no-print">
        <button type="button" onclick="window.print()">@lang('report::fields.print')</button>
        <button type="button" onclick="window.location.href='{{ url('Register-Report/'.$register->id) }}'">@lang('accounting::lang.back')</button>
    </div>

    <header class="rrd-print-header">
        <div class="rrd-print-header-top">
            <div class="rrd-print-header-brand">
                @if ($companyLogoUrl)
                    <img src="{{ $companyLogoUrl }}" alt="" class="rrd-print-logo">
                @endif
                <span class="rrd-print-subtitle">{{ $localeAr ? $companyNameAr : $companyName }}</span>
                @if ($companyTaxNumber)
                    <span class="rrd-print-subtitle"> · {{ $companyTaxNumber }}</span>
                @endif
            </div>
            <div class="rrd-print-header-meta">
                <div>{{ __('accounting::lang.printed_at') }}: {{ now()->format('d/m/Y H:i') }}</div>
                <div>#{{ $register->id }}</div>
            </div>
        </div>
        <h1 class="rrd-print-title">@lang('report::fields.register_details')</h1>
        <div class="rrd-print-subtitle">{{ $openLabel }} — {{ $closeLabel }}</div>
    </header>

    <div class="rrd-print-session">
        <div class="rrd-print-session-grid">
            <div class="rrd-print-session-cell">
                <div class="rrd-print-label">@lang('report::fields.user')</div>
                <div class="rrd-print-value">{{ $register->user_name }}</div>
            </div>
            <div class="rrd-print-session-cell">
                <div class="rrd-print-label">@lang('report::fields.business_location')</div>
                <div class="rrd-print-value">{{ $register->location_name }}</div>
            </div>
            <div class="rrd-print-session-cell">
                <div class="rrd-print-label">@lang('report::fields.email')</div>
                <div class="rrd-print-value">{{ $register->email ?: '—' }}</div>
            </div>
            <div class="rrd-print-session-cell">
                <div class="rrd-print-label">@lang('report::fields.status')</div>
                <div class="rrd-print-value">
                    <span class="rrd-print-status {{ $isOpen ? 'is-open' : '' }}">
                        {{ $isOpen ? __('report::fields.open') : __('report::fields.close') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <table class="rrd-print-kpi">
        <tr>
            <td>
                <div class="rrd-print-label">@lang('report::fields.cash_in_hand')</div>
                <div class="rrd-print-value">@format_currency($register->cash_in_hand ?? 0)</div>
            </td>
            <td>
                <div class="rrd-print-label">@lang('report::fields.total_sales')</div>
                <div class="rrd-print-value">@format_currency($register->total_sale ?? 0)</div>
            </td>
            <td>
                <div class="rrd-print-label">@lang('report::fields.total_refund')</div>
                <div class="rrd-print-value">@format_currency($register->total_refund ?? 0)</div>
            </td>
            <td>
                <div class="rrd-print-label">@lang('report::fields.total_expense')</div>
                <div class="rrd-print-value">@format_currency($register->total_expense ?? 0)</div>
            </td>
        </tr>
    </table>

    <div class="rrd-print-section">
        <div class="rrd-print-section-title">
            @lang('report::fields.register_transactions')
            <span style="font-weight:400;color:#64748b;">({{ $register_transactions->count() }})</span>
        </div>
        <table class="rrd-print-table" dir="{{ $dir }}">
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
                        <td>{{ $typeLabel }}{{ $invoiceRef }}</td>
                        <td>{{ $payLabel }}</td>
                        <td class="text-end">@format_currency($tx->amount ?? 0)</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="color:#94a3b8;padding:14px;">@lang('report::fields.no_register_transactions')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="rrd-print-two-col">
        <div class="rrd-print-col">
            <div class="rrd-print-section">
                <div class="rrd-print-section-title">@lang('report::fields.payment_method')</div>
                <table class="rrd-print-table" dir="{{ $dir }}">
                    <thead>
                        <tr>
                            <th>@lang('report::fields.payment_method')</th>
                            <th class="text-end">@lang('report::fields.sale')</th>
                            <th class="text-end">@lang('report::fields.refund')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paymentRows as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">@format_currency($row['sale'])</td>
                                <td class="text-end">@format_currency($row['refund'])</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>@lang('report::fields.total')</th>
                            <th class="text-end">@format_currency($register->total_sale ?? 0)</th>
                            <th class="text-end">@format_currency($register->total_refund ?? 0)</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="rrd-print-col">
            <div class="rrd-print-section">
                <div class="rrd-print-section-title">@lang('report::fields.summary')</div>
                <div class="rrd-print-meta">
                    @if (! empty($register->closing_note))
                        <div class="rrd-print-meta-row">
                            <strong>@lang('report::fields.closing_note')</strong>
                            <span>{{ $register->closing_note }}</span>
                        </div>
                    @endif
                </div>
                <table class="rrd-print-table" dir="{{ $dir }}">
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

    @if (! empty($denominations) && is_iterable($denominations))
        <div class="rrd-print-section">
            <div class="rrd-print-section-title">@lang('report::fields.cash_denominations')</div>
            <table class="rrd-print-table" dir="{{ $dir }}">
                <thead>
                    <tr>
                        <th>@lang('report::fields.denomination')</th>
                        <th class="text-center">@lang('report::fields.count')</th>
                        <th class="text-end">@lang('report::fields.subtotal')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($denominations as $key => $value)
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
    @endif

    @if (! empty($details['product_details']) && $details['product_details']->count() > 0)
        <div class="rrd-print-section">
            <div class="rrd-print-section-title">@lang('report::fields.products')</div>
            <table class="rrd-print-table" dir="{{ $dir }}">
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
                            <td>{{ app()->getLocale() === 'ar' ? $product->product_name_ar : $product->product_name_en }}</td>
                            <td class="text-center">{{ $product->total_quantity }}</td>
                            <td class="text-end">@format_currency($product->total_amount)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="rrd-print-footer">
        {{ $localeAr ? $companyNameAr : $companyName }} · @lang('report::fields.register_details') · #{{ $register->id }}
    </div>
</body>

</html>
