<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.income_list') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
        .section-title { margin-top: 14px; font-weight: 700; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.income_list') }}</h2>
    <div>{{ __('accounting::lang.from_date') }}: {{ $start_date }} | {{ __('accounting::lang.to_date') }}: {{ $end_date }}</div>

    <div class="summary">
        <strong>{{ __('accounting::lang.Revenues') }}:</strong> {{ number_format((float) ($data['revenue_net'] ?? 0), 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.account_types.expenses') }}:</strong> {{ number_format((float) ($data['total_expense'] ?? 0), 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.net_profit') }}:</strong> {{ number_format((float) (($data['income_before_tax'] ?? 0) - ($data['tax_amount'] ?? 0)), 2) }}
    </div>

    <div class="section-title">{{ __('accounting::lang.Revenues') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.account_name') }}</th>
                <th>{{ __('employee::fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revenueAccounts as $account)
                <tr>
                    <td>{{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}</td>
                    <td>{{ number_format((float) $account->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
            <tr>
                <td><strong>{{ __('accounting::lang.total') }}</strong></td>
                <td><strong>{{ number_format((float) ($data['revenue_net'] ?? 0), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">{{ __('accounting::lang.account_types.expenses') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.account_name') }}</th>
                <th>{{ __('employee::fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenseAccounts as $account)
                <tr>
                    <td>{{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}</td>
                    <td>{{ number_format((float) $account->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">{{ __('messages.no_data_found') }}</td>
                </tr>
            @endforelse
            <tr>
                <td><strong>{{ __('accounting::lang.total') }}</strong></td>
                <td><strong>{{ number_format((float) ($data['total_expense'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>{{ __('report::general.gross_profit') }}</strong></td>
                <td><strong>{{ number_format((float) ($data['gross_profit'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>{{ __('accounting::lang.income_before_tax') }}</strong></td>
                <td><strong>{{ number_format((float) ($data['income_before_tax'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>{{ __('accounting::lang.tax_amount') }}</strong></td>
                <td><strong>{{ number_format((float) ($data['tax_amount'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td><strong>{{ __('accounting::lang.net_profit') }}</strong></td>
                <td><strong>{{ number_format((float) (($data['income_before_tax'] ?? 0) - ($data['tax_amount'] ?? 0)), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>

