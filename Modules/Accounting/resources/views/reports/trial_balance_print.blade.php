<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.trial_balance') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #181c32; }
        h2 { margin: 0 0 6px; font-size: 15px; }
        .muted { color: #78829d; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #dbdfe9; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #dbdfe9; padding: 4px 5px; }
        thead th { background: #e9f3ff; font-size: 8px; }
        .text-end { text-align: end; }
        .total-row td, .total-row th { font-weight: bold; background: #f1f1f4; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.trial_balance') }}</h2>
    <div class="muted">
        {{ __('accounting::lang.from_date') }}: {{ $start_date }}
        — {{ __('accounting::lang.to_date') }}: {{ $end_date }}
    </div>
    <div class="summary">
        <strong>{{ __('accounting::lang.balance') }}:</strong> {{ $balance_status }}
        | <strong>{{ __('accounting::lang.difference') }}:</strong> {{ number_format((float) $difference, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.number') }}</th>
                <th>{{ __('accounting::lang.name') }}</th>
                <th class="text-end">{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.opening_balance') }})</th>
                <th class="text-end">{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.opening_balance') }})</th>
                <th class="text-end">{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.accounting_transactions') }})</th>
                <th class="text-end">{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.accounting_transactions') }})</th>
                <th class="text-end">{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.closing_balance') }})</th>
                <th class="text-end">{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.closing_balance') }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['gl_code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-end">{{ $row['debit_opening_balance'] }}</td>
                    <td class="text-end">{{ $row['credit_opening_balance'] }}</td>
                    <td class="text-end">{{ $row['debit_balance'] }}</td>
                    <td class="text-end">{{ $row['credit_balance'] }}</td>
                    <td class="text-end">{{ $row['closing_debit_balance'] }}</td>
                    <td class="text-end">{{ $row['closing_credit_balance'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8">{{ __('messages.no_data_found') }}</td></tr>
            @endforelse
            <tr class="total-row">
                <th colspan="2">{{ __('accounting::lang.total') }}</th>
                <th class="text-end">{{ number_format((float) $totals['debit_opening'], 2) }}</th>
                <th class="text-end">{{ number_format((float) $totals['credit_opening'], 2) }}</th>
                <th class="text-end">{{ number_format((float) $totals['debit'], 2) }}</th>
                <th class="text-end">{{ number_format((float) $totals['credit'], 2) }}</th>
                <th class="text-end">{{ number_format((float) $totals['closing_debit'], 2) }}</th>
                <th class="text-end">{{ number_format((float) $totals['closing_credit'], 2) }}</th>
            </tr>
        </tbody>
    </table>
</body>
</html>
