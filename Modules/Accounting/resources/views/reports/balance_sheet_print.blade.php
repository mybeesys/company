<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.balance_sheet') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
        .total-row td { font-weight: bold; background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.balance_sheet') }}</h2>
    <div><strong>{{ __('accounting::lang.bs_as_at') }}:</strong> {{ $end_date }}</div>
    <p style="font-size: 10px; color: #4b5563; margin: 6px 0 10px;">{{ __('accounting::lang.bs_position_explanation') }}</p>
    <div>{{ __('accounting::lang.from_date') }}: {{ $start_date }} | {{ __('accounting::lang.to_date') }}: {{ $end_date }}</div>
    <div class="summary">
        <strong>{{ __('accounting::lang.balance') }}:</strong> {{ $balance_status }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.difference') }}:</strong> {{ number_format((float) $difference, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.assets') }}</th>
                <th>{{ __('accounting::lang.liab_owners_capital') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="vertical-align: top;">
                    <table style="width:100%; border-collapse: collapse;">
                        @forelse($assets as $asset)
                            <tr>
                                <td style="border: 1px solid #e5e7eb;">{{ app()->getLocale() == 'ar' ? $asset->name_ar : $asset->name_en }}</td>
                                <td style="border: 1px solid #e5e7eb; text-align:end;">{{ number_format((float) $asset->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">{{ __('messages.no_data_found') }}</td></tr>
                        @endforelse
                    </table>
                </td>
                <td style="vertical-align: top;">
                    <table style="width:100%; border-collapse: collapse;">
                        @foreach($liabilities as $liability)
                            <tr>
                                <td style="border: 1px solid #e5e7eb;">{{ app()->getLocale() == 'ar' ? $liability->name_ar : $liability->name_en }}</td>
                                <td style="border: 1px solid #e5e7eb; text-align:end;">{{ number_format((float) $liability->balance, 2) }}</td>
                            </tr>
                        @endforeach
                        @foreach($equities as $equity)
                            <tr>
                                <td style="border: 1px solid #e5e7eb;">{{ app()->getLocale() == 'ar' ? $equity->name_ar : $equity->name_en }}</td>
                                <td style="border: 1px solid #e5e7eb; text-align:end;">{{ number_format((float) $equity->balance, 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
            <tr class="total-row">
                <td>{{ __('accounting::lang.total_assets') }}: {{ number_format((float) $total_assets, 2) }}</td>
                <td>{{ __('accounting::lang.total_liab_owners') }}: {{ number_format((float) $total_liab_owners, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>

