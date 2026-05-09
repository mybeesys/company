<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';

    $isPurchase =
        ($transaction->transaction?->type == 'purchases') ||
        ($transaction->transaction?->type == 'purchase') ||
        ($transaction->transaction?->type == 'purchases-order') ||
        ($transaction->transaction?->type == 'sell-return');

    $contactLabel = $isPurchase ? __('purchases::general.supplier') : __('sales::fields.client');
    $lines = $isPurchase ? ($transaction->transaction?->purchases_lines ?? []) : ($transaction->transaction?->sell_lines ?? []);
    $logoPath = public_path('assets/media/logos/1-14.png');
@endphp
<html dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $transaction->payment_ref_no }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; color: #111827; margin: 0; padding: 18px; }
        .muted { color: #6B7280; }
        .h1 { font-size: 20px; font-weight: 700; margin: 0 0 6px 0; }
        .h2 { font-size: 14px; font-weight: 700; margin: 0 0 6px 0; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; }
        .sep { height: 16px; }
        .card { border: 1px solid #E5E7EB; }
        .card-h { background: #F9FAFB; padding: 8px 10px; font-weight: 700; }
        .card-b { padding: 10px; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 4px 0; }
        .label { color: #6B7280; font-size: 11px; }
        .value { font-weight: 600; }
        table.tbl { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .tbl th { border: 1px solid #E5E7EB; background: #F9FAFB; padding: 6px; text-align: center; font-size: 11px; }
        .tbl td { border: 1px solid #E5E7EB; padding: 6px; font-size: 11px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table class="grid">
        <tr>
            <td style="width:40%;">
                <div style="padding:10px;">
                    <div class="h1">{{ $title }} <span class="muted">{{ $transaction->payment_ref_no }}</span></div>
                    <div class="muted">{{ $company->name ?? '' }}</div>
                    <div class="muted">{{ ($company->state ?? '') }} - {{ ($company->city ?? '') }}</div>
                    <div class="muted">@lang('menuItemLang.tel'): {{ $company->phone ?? '' }}</div>
                </div>
            </td>
            <td style="width:20%;"></td>
            <td style="width:40%; text-align:center;">
                <div style="padding:10px;">
                    @if (is_file($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" style="height:70px;">
                    @endif
                    <div class="muted" style="margin-top:6px;">VAT: {{ $company->tax_number ?? '' }}</div>
                    <div class="muted">@lang('sales::fields.paid_on') {{ $transaction->paid_on ?? '--' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="sep"></div>

    <table class="grid">
        <tr>
            <td style="width:34%; padding-right:10px;">
                <div class="card">
                    <div class="card-h">{{ $contactLabel }}</div>
                    <div class="card-b">
                        <table class="kv">
                            <tr>
                                <td class="label">@lang('sales::fields.name')</td>
                                <td class="value">{{ $transaction->client->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('general::lang.Address')</td>
                                <td class="value">
                                    {{ $transaction?->client?->billingAddress?->city ? ($transaction?->client?->billingAddress?->city.' - '.$transaction?->client?->billingAddress?->street_name) : 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="label">@lang('clientsandsuppliers::fields.email')</td>
                                <td class="value">{{ $transaction->client->email ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('clientsandsuppliers::fields.mobile_number')</td>
                                <td class="value">{{ $transaction->client->mobile_number ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="width:66%; padding-left:10px;">
                <div class="card">
                    <div class="card-h">@lang('general::lang.invoice_info')</div>
                    <div class="card-b">
                        <table class="kv">
                            <tr>
                                <td class="label">@lang('sales::fields.ref_no')</td>
                                <td class="value">{{ $transaction->transaction->ref_no ?? 'N/A' }}</td>
                                <td class="label">@lang('report::fields.transaction_date')</td>
                                <td class="value">{{ $transaction->transaction->transaction_date ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('sales::lang.total_before_vat')</td>
                                <td class="value">{{ $transaction->transaction->total_before_tax ?? 'N/A' }}</td>
                                <td class="label">@lang('general::fields.tax_amount')</td>
                                <td class="value">{{ $transaction->transaction->tax_amount ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('general::lang.discount_type')</td>
                                <td class="value">{{ $transaction->transaction->discount_type ?? 'N/A' }}</td>
                                <td class="label">@lang('report::fields.discount_amount')</td>
                                <td class="value">{{ $transaction->transaction->discount_amount ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('employee::fields.gross_total')</td>
                                <td class="value">{{ $transaction->transaction->final_total ?? 'N/A' }}</td>
                                <td class="label">@lang('sales::fields.payment_status')</td>
                                <td class="value">{{ $transaction->transaction?->payment_status ? __('general::lang.' . $transaction->transaction->payment_status) : '--' }}</td>
                            </tr>
                            <tr>
                                <td class="label">@lang('sales::lang.paid_amount')</td>
                                <td class="value">{{ number_format((float) ($transaction->amount ?? 0), 2) }}</td>
                                <td class="label">@lang('sales::fields.paid_on')</td>
                                <td class="value">{{ $transaction->paid_on ?? '--' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="sep"></div>

    <div class="card">
        <div class="card-h">@lang('sales::fields.Line Items')</div>
        <div class="card-b">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>@lang('sales::lang.product')</th>
                        <th style="width:70px;">@lang('sales::lang.qty')</th>
                        <th style="width:110px;">@lang('sales::lang.unit_price')</th>
                        <th style="width:90px;">@lang('sales::lang.discount')</th>
                        <th style="width:110px;">@lang('sales::lang.total_before_vat')</th>
                        <th style="width:70px;">@lang('sales::lang.vat_percentage')</th>
                        <th style="width:90px;">@lang('sales::lang.vat_value')</th>
                        <th style="width:120px;">@lang('sales::lang.total_with_tax')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $index => $line)
                        @if ($line->product)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $line->product->SKU . ' / ' . $line->product->name_ar }}</td>
                                <td class="text-center">{{ $line->qyt }} {{ $line?->unitTransfer?->unit1 }}</td>
                                <td class="text-center">{{ $line->unit_price_before_discount }}</td>
                                <td class="text-center">{{ $line->discount_amount ?? 0 }}</td>
                                <td class="text-center">{{ $line->total_before_vat }}</td>
                                <td class="text-center">{{ $line->tax_id }} %</td>
                                <td class="text-center">{{ $line->tax_value }}</td>
                                <td class="text-center">{{ $line->unit_price_inc_tax }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="sep"></div>

    <table class="grid">
        <tr>
            <td style="width:70%;">
                <div class="muted" style="font-size:11px;">
                    {{ $transaction->note ?? '' }}
                </div>
            </td>
            <td style="width:30%; text-align:center;">
                @if (!empty($qrCode))
                    <div style="display:inline-block; width:150px; height:150px;">{!! $qrCode !!}</div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>

