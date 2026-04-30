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
                <th>{{ $name_header }}</th>
                <th>{{ __('accounting::lang.current') }}</th>
                <th>{{ __('accounting::lang.1_30_days') }}</th>
                <th>{{ __('accounting::lang.31_60_days') }}</th>
                <th>{{ __('accounting::lang.61_90_days') }}</th>
                <th>{{ __('accounting::lang.91_and_over') }}</th>
                <th>{{ __('accounting::lang.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ number_format((float) $row['<1'], 2) }}</td>
                    <td>{{ number_format((float) $row['1_30'], 2) }}</td>
                    <td>{{ number_format((float) $row['31_60'], 2) }}</td>
                    <td>{{ number_format((float) $row['61_90'], 2) }}</td>
                    <td>{{ number_format((float) $row['>90'], 2) }}</td>
                    <td>{{ number_format((float) $row['total_due'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('messages.no_data_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

