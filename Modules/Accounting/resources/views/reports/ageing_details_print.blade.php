<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
    </style>
</head>
<body>
    <h3>{{ $title }}</h3>
    <p>{{ __('accounting::lang.to_date') }}: {{ $as_of_date }}</p>
    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.current_or_overdue') }}</th>
                <th>{{ __('reports.date') }}</th>
                <th>{{ __('accounting::lang.transaction_type') }}</th>
                <th>{{ __('sales::fields.ref_no') }}</th>
                <th>{{ $contact_header }}</th>
                <th>{{ __('sales::fields.due_date') }}</th>
                <th>{{ __('report::general.cash_due') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['bucket'] }}</td>
                    <td>{{ $row['transaction_date'] }}</td>
                    <td>{{ $row['transaction_type'] }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $row['contact_name'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td>{{ number_format((float) $row['due'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('messages.no_data_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

