<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.balance_sheet') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #e9b71f; padding-bottom: 8px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 10px; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #DBDFE9; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; font-variant-numeric: tabular-nums; }
        th, td { border: 1px solid #DBDFE9; padding: 5px 7px; }
        thead th { background: #E9F3FF; color: #4B5675; font-size: 10px; text-transform: uppercase; }
        td.amount { text-align: {{ app()->getLocale() == 'ar' ? 'left' : 'right' }}; font-family: DejaVu Sans Mono, monospace; white-space: nowrap; }
        td.amount.neg { color: #F8285A; }
        tr.main-section td { background: #e9b71f; color: #fff; font-weight: 700; }
        tr.subsection td { background: #F9F9F9; color: #e9b71f; font-weight: 700; border-top: 2px solid #DBDFE9; }
        tr.group-header td { background: #F1F1F4; font-weight: 600; }
        tr.subtotal td { background: #F1F1F4; font-weight: 600; }
        tr.grand td { background: #E9F3FF; font-weight: 700; }
        tr.equation td { background: #d1f4dd; font-weight: 700; }
        .footer { margin-top: 14px; text-align: center; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
@php
    use App\Helpers\CurrencyHelper;
    $m = $metrics ?? [];
    $fmt = function ($amount) {
        $neg = CurrencyHelper::is_negative_amount($amount);
        return ['text' => CurrencyHelper::format_accounting_amount($amount, false), 'neg' => $neg];
    };
    $localeAr = app()->getLocale() === 'ar';
    $name = fn ($a) => $localeAr ? $a->name_ar : $a->name_en;
@endphp

    <div class="header">
        <h1>{{ $company->name ?? '' }}</h1>
        <div style="font-size:13px;font-weight:700;">{{ __('accounting::lang.balance_sheet') }}</div>
        <div class="muted">@lang('accounting::lang.bs_as_at'): {{ $end_date }}</div>
    </div>

    <div class="summary">
        <strong>@lang('accounting::lang.balance'):</strong> {{ $balance_status }}
        | <strong>@lang('accounting::lang.total_assets'):</strong> {{ CurrencyHelper::format_accounting_amount($total_assets ?? 0, false) }}
        | <strong>@lang('accounting::lang.bs_total_liab_equity'):</strong> {{ CurrencyHelper::format_accounting_amount($total_liab_owners ?? 0, false) }}
        | <strong>@lang('accounting::lang.bs_current_ratio'):</strong> {{ isset($m['current_ratio']) ? number_format($m['current_ratio'], 2) : '—' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:58%">@lang('accounting::lang.account_name')</th>
                <th style="width:42%">@lang('employee::fields.amount')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sections as $section)
                <tr class="main-section"><td colspan="2">{{ $section['title'] }}</td></tr>
                @foreach ($section['groups'] as $group)
                    @if(($group['type'] ?? '') === 'subsection')
                        <tr class="subsection"><td colspan="2">{{ $group['label'] }}</td></tr>
                    @elseif(($group['type'] ?? '') === 'accounts')
                        @php $gf = $fmt($group['total'] ?? 0); @endphp
                        <tr class="group-header">
                            <td>{{ $group['label'] }}</td>
                            <td class="amount{{ $gf['neg'] ? ' neg' : '' }}">{{ $gf['text'] }}</td>
                        </tr>
                        @foreach ($group['accounts'] as $account)
                            @php $af = $fmt($account->balance); @endphp
                            <tr>
                                <td style="padding-inline-start:{{ (($account->depth ?? 0) + 1) * 8 }}px">
                                    <span style="color:#78829D;font-size:9px;">{{ $account->gl_code }}</span>
                                    {{ $name($account) }}
                                </td>
                                <td class="amount{{ $af['neg'] ? ' neg' : '' }}">{{ $af['text'] }}</td>
                            </tr>
                        @endforeach
                    @elseif(in_array($group['type'] ?? '', ['subtotal', 'grand'], true))
                        @php $sf = $fmt($group['amount'] ?? 0); @endphp
                        <tr class="{{ $group['type'] }}">
                            <td>{{ $group['label'] }}</td>
                            <td class="amount{{ $sf['neg'] ? ' neg' : '' }}">{{ $sf['text'] }}</td>
                        </tr>
                    @endif
                @endforeach
            @endforeach
            @php $ef = $fmt($total_liab_owners ?? 0); @endphp
            <tr class="equation">
                <td>@lang('accounting::lang.bs_total_liab_equity')</td>
                <td class="amount{{ $ef['neg'] ? ' neg' : '' }}">{{ $ef['text'] }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">@lang('accounting::lang.bs_print_footer') — {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>
