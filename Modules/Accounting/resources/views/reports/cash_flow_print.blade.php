<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.cash_flow_statement') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #181c32; }
        h2 { margin: 0 0 6px; font-size: 15px; }
        .muted { color: #78829d; margin-bottom: 8px; }
        .summary { padding: 8px; border: 1px solid #dbdfe9; margin-bottom: 10px; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #dbdfe9; padding: 4px 6px; }
        thead th { background: #e9f3ff; font-size: 8px; }
        .text-end { text-align: end; }
        .section { background: #f1f1f4; font-weight: bold; }
        .subtotal { background: #f9f9f9; font-weight: bold; }
        .grand { background: #e9f3ff; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ $company->name ?? '' }} — {{ __('accounting::lang.cash_flow_statement') }}</h2>
    <div class="muted">{{ __('accounting::lang.from_date') }}: {{ $startDate }} — {{ __('accounting::lang.to_date') }}: {{ $endDate }}</div>
    <div class="summary">
        {{ __('accounting::lang.cash_inflows') }}: {{ number_format((float) $cashInflows, 2) }}
        | {{ __('accounting::lang.cash_outflows') }}: {{ number_format((float) $cashOutflows, 2) }}
        | {{ __('accounting::lang.net_cash_flows') }}: {{ number_format((float) $netCashFlow, 2) }}
        | {{ __('accounting::lang.cf_opening_cash') }}: {{ number_format((float) $openingCash, 2) }}
        | {{ __('accounting::lang.cf_closing_cash') }}: {{ number_format((float) $closingCash, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.cf_line_item') }}</th>
                <th class="text-end">{{ __('accounting::lang.cash_inflows') }}</th>
                <th class="text-end">{{ __('accounting::lang.cash_outflows') }}</th>
                <th class="text-end">{{ __('accounting::lang.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statement ?? [] as $section)
                <tr class="section">
                    <td colspan="4">{{ $section->section_label }}</td>
                </tr>
                @foreach ($section->lines as $line)
                    <tr class="{{ $line->is_subtotal ? 'subtotal' : '' }}">
                        <td style="padding-inline-start: {{ $line->depth ? '12px' : '4px' }}">{{ $line->label }}</td>
                        <td class="text-end">{{ $line->inflows > 0 ? number_format($line->inflows, 2) : '—' }}</td>
                        <td class="text-end">{{ $line->outflows > 0 ? number_format($line->outflows, 2) : '—' }}</td>
                        <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
            <tr class="grand">
                <td>{{ __('accounting::lang.cf_net_change_cash') }}</td>
                <td class="text-end">{{ number_format((float) $cashInflows, 2) }}</td>
                <td class="text-end">{{ number_format((float) $cashOutflows, 2) }}</td>
                <td class="text-end">{{ number_format((float) $netCashFlow, 2) }}</td>
            </tr>
            <tr class="grand">
                <td>{{ __('accounting::lang.cf_opening_cash') }}</td>
                <td colspan="2"></td>
                <td class="text-end">{{ number_format((float) $openingCash, 2) }}</td>
            </tr>
            <tr class="grand">
                <td>{{ __('accounting::lang.cf_closing_cash') }}</td>
                <td colspan="2"></td>
                <td class="text-end">{{ number_format((float) $closingCash, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
