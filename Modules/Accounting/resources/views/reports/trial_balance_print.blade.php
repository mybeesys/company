<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.trial_balance') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 10px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: center; }
        thead th { background: #e5e7eb; }
        .summary { margin-top: 8px; padding: 8px; border: 1px solid #d1d5db; }
        .total-row td { font-weight: bold; background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ __('accounting::lang.trial_balance') }}</h2>
        <div class="muted">
            {{ __('accounting::lang.from_date') }}: {{ $start_date }} |
            {{ __('accounting::lang.to_date') }}: {{ $end_date }}
        </div>
        <div class="summary">
            <strong>{{ __('accounting::lang.balance') }}:</strong> {{ $balance_status }}
            <span style="margin: 0 8px;">|</span>
            <strong>{{ __('accounting::lang.difference') }}:</strong> {{ number_format((float) $difference, 2) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.number') }}</th>
                <th>{{ __('accounting::lang.name') }}</th>
                <th>{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.opening_balance') }})</th>
                <th>{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.opening_balance') }})</th>
                <th>{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.accounting_transactions') }})</th>
                <th>{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.accounting_transactions') }})</th>
                <th>{{ __('accounting::lang.debit') }} ({{ __('accounting::lang.closing_balance') }})</th>
                <th>{{ __('accounting::lang.credit') }} ({{ __('accounting::lang.closing_balance') }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['gl_code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['debit_opening_balance'] }}</td>
                    <td>{{ $row['credit_opening_balance'] }}</td>
                    <td>{{ $row['debit_balance'] }}</td>
                    <td>{{ $row['credit_balance'] }}</td>
                    <td>{{ $row['closing_debit_balance'] }}</td>
                    <td>{{ $row['closing_credit_balance'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">{{ __('accounting::lang.total') }}</td>
                <td>{{ number_format((float) $totals['debit_opening'], 2) }}</td>
                <td>{{ number_format((float) $totals['credit_opening'], 2) }}</td>
                <td>{{ number_format((float) $totals['debit'], 2) }}</td>
                <td>{{ number_format((float) $totals['credit'], 2) }}</td>
                <td>{{ number_format((float) $totals['closing_debit'], 2) }}</td>
                <td>{{ number_format((float) $totals['closing_credit'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>

