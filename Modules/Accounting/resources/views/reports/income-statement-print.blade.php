<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.income_list') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #e9b71f; padding-bottom: 8px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-variant-numeric: tabular-nums; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; }
        thead th { background: #e5e7eb; font-size: 10px; text-transform: uppercase; }
        td.amount { text-align: {{ app()->getLocale() == 'ar' ? 'left' : 'right' }}; font-family: DejaVu Sans Mono, monospace; white-space: nowrap; }
        td.amount.neg { color: #F8285A; }
        tr.section td { background: #F9F9F9; font-weight: 700; font-size: 10px; color: #e9b71f; border-top: 2px solid #DBDFE9; }
        tr.subtotal td { background: #F1F1F4; font-weight: 600; }
        tr.grand td { background: #E9F3FF; font-weight: 700; }
        tr.profit td { background: #d1f4dd; font-weight: 700; }
        tr.loss td { background: #fed4de; font-weight: 700; }
        .indent { display: inline-block; }
        .gl { color: #64748b; font-size: 9px; margin-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }}: 6px; }
        .summary-box { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; font-size: 10px; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        .vat-note { font-size: 9px; color: #64748b; margin-top: 10px; }
    </style>
</head>
<body>
@php
    use App\Helpers\CurrencyHelper;
    $netProfit = (float) ($data['net_profit'] ?? 0);
    $taxPercent = (float) ($data['tax_percent'] ?? 0);
    $fmtAmount = function ($amount) {
        $neg = CurrencyHelper::is_negative_amount($amount);
        $text = CurrencyHelper::format_accounting_amount($amount, false);
        return ['text' => $text, 'neg' => $neg];
    };
    $renderAccountRows = function ($accounts) use ($fmtAmount) {
        $localeAr = app()->getLocale() === 'ar';
        foreach ($accounts as $account) {
            $f = $fmtAmount($account->amount);
            $depth = (int) ($account->depth ?? 0);
            echo '<tr>';
            echo '<td><span class="indent" style="width:'.($depth * 8).'px"></span>';
            echo '<span class="gl">'.$account->gl_code.'</span>';
            echo e($localeAr ? $account->name_ar : $account->name_en);
            echo '</td>';
            echo '<td class="amount'.($f['neg'] ? ' neg' : '').'">'.$f['text'].'</td>';
            echo '</tr>';
        }
    };
    $renderSummary = function ($label, $amount, $class = 'subtotal') use ($fmtAmount) {
        $f = $fmtAmount($amount);
        echo '<tr class="'.$class.'"><td>'.$label.'</td><td class="amount'.($f['neg'] ? ' neg' : '').'">'.$f['text'].'</td></tr>';
    };
@endphp

    <div class="header">
        <h1>{{ $company->name ?? '' }}</h1>
        <div style="font-size:13px;font-weight:700;">{{ __('accounting::lang.income_list') }}</div>
        <div class="muted">{{ __('accounting::lang.income_statement_period', ['from' => $start_date, 'to' => $end_date]) }}</div>
    </div>

    <div class="summary-box">
        <strong>{{ __('accounting::lang.income_statement_net_sales') }}:</strong> {{ CurrencyHelper::format_accounting_amount($data['net_sales'] ?? 0, false) }}
        &nbsp;|&nbsp;
        <strong>{{ __('report::general.gross_profit') }}:</strong> {{ CurrencyHelper::format_accounting_amount($data['gross_profit'] ?? 0, false) }}
        &nbsp;|&nbsp;
        <strong>{{ __('accounting::lang.net_profit') }}:</strong> {{ CurrencyHelper::format_accounting_amount($netProfit, false) }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:58%">{{ __('accounting::lang.account_name') }}</th>
                <th style="width:42%">{{ __('employee::fields.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_gross_revenue') }}</td></tr>
            @php $renderAccountRows($grossRevenueAccounts ?? collect()); @endphp
            @php $renderSummary(__('accounting::lang.total').' '.__('accounting::lang.income_statement_gross_revenue'), $data['gross_revenue'] ?? 0); @endphp

            @if(($salesReturnAccounts ?? collect())->isNotEmpty())
                <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_sales_returns') }}</td></tr>
                @php $renderAccountRows($salesReturnAccounts); @endphp
                @php $renderSummary(__('accounting::lang.total').' '.__('accounting::lang.income_statement_sales_returns'), -1 * abs($data['sales_returns'] ?? 0)); @endphp
            @endif

            @php $renderSummary(__('accounting::lang.income_statement_net_sales'), $data['net_sales'] ?? 0, 'grand'); @endphp

            <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_cost_of_revenue') }}</td></tr>
            @php $renderAccountRows($cogsAccounts ?? collect()); @endphp
            @php $renderSummary(__('accounting::lang.income_statement_total_cost_of_revenue'), $data['cost_of_revenue'] ?? 0); @endphp
            @php $renderSummary(__('report::general.gross_profit'), $data['gross_profit'] ?? 0, 'profit'); @endphp

            <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_operating_expenses') }}</td></tr>
            @php $renderAccountRows($expenseAccounts ?? collect()); @endphp
            @php $renderSummary(__('accounting::lang.income_statement_total_operating_expenses'), $data['total_expense'] ?? 0); @endphp
            @php $renderSummary(__('accounting::lang.income_statement_operating_profit'), $data['operating_profit'] ?? 0, 'grand'); @endphp

            @if(($otherIncomeAccounts ?? collect())->isNotEmpty())
                <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_other_income') }}</td></tr>
                @php $renderAccountRows($otherIncomeAccounts); @endphp
                @php $renderSummary(__('accounting::lang.income_statement_total_other_income'), $data['total_other_income'] ?? 0); @endphp
            @endif

            @if(($otherExpenseAccounts ?? collect())->isNotEmpty())
                <tr class="section"><td colspan="2">{{ __('accounting::lang.income_statement_other_expenses') }}</td></tr>
                @php $renderAccountRows($otherExpenseAccounts); @endphp
                @php $renderSummary(__('accounting::lang.income_statement_total_other_expenses'), $data['total_other_expense'] ?? 0); @endphp
            @endif

            @php $renderSummary(__('accounting::lang.income_before_tax'), $data['income_before_tax'] ?? 0); @endphp
            @php $renderSummary(__('accounting::lang.tax_amount').' ('.number_format($taxPercent, 0).'%)', -1 * abs($data['tax_amount'] ?? 0)); @endphp
            @php $renderSummary(__('accounting::lang.net_profit'), $netProfit, $netProfit >= 0 ? 'profit' : 'loss'); @endphp
        </tbody>
    </table>

    <p class="vat-note">@lang('accounting::lang.income_statement_vat_note', ['percent' => number_format($taxPercent, 0)])</p>

    <div class="footer">
        @lang('accounting::lang.income_statement_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
