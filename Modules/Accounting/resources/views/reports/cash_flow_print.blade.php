<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.cash_flow_statement') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.cash_flow_statement') }}</h2>
    <div>{{ __('accounting::lang.from_date') }}: {{ $startDate }} | {{ __('accounting::lang.to_date') }}: {{ $endDate }}</div>

    <div class="summary">
        <strong>{{ __('accounting::lang.cash_inflows') }}:</strong> {{ number_format((float) $cashInflows, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.cash_outflows') }}:</strong> {{ number_format((float) $cashOutflows, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.net_cash_flows') }}:</strong> {{ number_format((float) $netCashFlow, 2) }}
    </div>
    <div class="summary">
        <strong>{{ __('accounting::lang.operating_activities') }}:</strong> {{ number_format((float) ($sectionSummaries['operating']['net'] ?? 0), 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.investing_activities') }}:</strong> {{ number_format((float) ($sectionSummaries['investing']['net'] ?? 0), 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.financing_activities') }}:</strong> {{ number_format((float) ($sectionSummaries['financing']['net'] ?? 0), 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.activity_section') }}</th>
                <th>{{ __('accounting::lang.operation_date') }}</th>
                <th>{{ __('accounting::lang.transaction_number') }}</th>
                <th>{{ __('accounting::lang.transaction_type') }}</th>
                <th>{{ __('accounting::lang.movement_type') }}</th>
                <th>{{ __('accounting::lang.cost_center') }}</th>
                <th>{{ __('employee::fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['section'] }}</td>
                    <td>{{ $row['operation_date'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $row['transaction_type'] }}</td>
                    <td>{{ $row['movement_type'] }}</td>
                    <td>{{ $row['cost_center'] }}</td>
                    <td>{{ number_format((float) $row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

