<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}</h2>
    <div>{{ __('accounting::lang.name') }}: {{ $contact->name }}</div>
    <div>{{ __('accounting::lang.from_date') }}: {{ $start_date }} | {{ __('accounting::lang.to_date') }}: {{ $end_date }}</div>

    <div class="summary">
        <strong>{{ __('accounting::lang.balance') }}:</strong> {{ number_format((float) $current_bal, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.debit') }}:</strong> {{ number_format((float) $period_debit, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.credit') }}:</strong> {{ number_format((float) $period_credit, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.number') }}</th>
                <th>{{ __('accounting::lang.operation_date') }}</th>
                <th>{{ __('accounting::lang.transaction') }}</th>
                <th>{{ __('accounting::lang.cost_center') }}</th>
                <th>{{ __('employee::general.notes') }}</th>
                <th>{{ __('accounting::lang.added_by') }}</th>
                <th>{{ __('accounting::lang.debit') }}</th>
                <th>{{ __('accounting::lang.credit') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $row['operation_date'] }}</td>
                    <td>{{ $row['transaction'] }}</td>
                    <td>{{ $row['cost_center'] }}</td>
                    <td>{{ $row['note'] }}</td>
                    <td>{{ $row['added_by'] }}</td>
                    <td>{{ number_format((float) $row['debit'], 2) }}</td>
                    <td>{{ number_format((float) $row['credit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

