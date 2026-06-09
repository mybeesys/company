<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.expense_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h2 { margin: 0 0 6px; font-size: 15px; }
        h3 { margin: 12px 0 6px; font-size: 12px; color: #e9b71f; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #DBDFE9; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #DBDFE9; padding: 4px 5px; }
        thead th { background: #E9F3FF; font-size: 9px; }
        .text-end { text-align: end; }
        .cat-row td { background: #F1F1F4; font-weight: 700; }
        .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #78829D; }
    </style>
</head>
<body>
@php
    use App\Helpers\CurrencyHelper;
    $summary = $summary ?? ['count' => 0, 'net' => 0, 'tax' => 0, 'gross' => 0];
    $localeAr = app()->getLocale() === 'ar';
@endphp

    <h2>{{ $company->name ?? '' }} — {{ __('accounting::lang.expense_report') }}</h2>
    <div>{{ __('accounting::lang.from_date') }}: {{ $startDate }} | {{ __('accounting::lang.to_date') }}: {{ $endDate }}</div>

    <div class="summary">
        @lang('accounting::lang.expense_report_count'): {{ number_format($summary['count']) }}
        | @lang('accounting::lang.expense_report_gross'): {{ CurrencyHelper::format_accounting_amount($summary['gross'], false) }}
        | @lang('accounting::lang.expense_report_tax'): {{ CurrencyHelper::format_accounting_amount($summary['tax'], false) }}
    </div>

    @if (($byCategory ?? collect())->isNotEmpty())
        <h3>@lang('accounting::lang.expense_report_by_classification')</h3>
        <table>
            <thead>
                <tr>
                    <th>@lang('accounting::lang.expense_report_classification')</th>
                    <th class="text-end">@lang('accounting::lang.expense_report_count')</th>
                    <th class="text-end">@lang('expense::fields.gross_amount')</th>
                    <th class="text-end">@lang('accounting::lang.expense_report_share')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byCategory as $row)
                    <tr>
                        <td>{{ $row->category_label }}</td>
                        <td class="text-end">{{ $row->expense_count }}</td>
                        <td class="text-end">{{ CurrencyHelper::format_accounting_amount($row->gross_total, false) }}</td>
                        <td class="text-end">{{ number_format($row->share_percent, 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>@lang('accounting::lang.expense_report_details')</h3>
    <table>
        <thead>
            <tr>
                <th>@lang('expense::fields.expense_date')</th>
                <th>@lang('accounting::lang.expense_report_classification')</th>
                <th>@lang('accounting::lang.expense_report_item')</th>
                <th>@lang('expense::fields.cost_center')</th>
                <th class="text-end">@lang('expense::fields.gross_amount')</th>
                <th class="text-end">@lang('accounting::lang.expense_report_share')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($byCategory ?? [] as $cat)
                <tr class="cat-row">
                    <td colspan="4">{{ $cat->category_label }}</td>
                    <td class="text-end">{{ CurrencyHelper::format_accounting_amount($cat->gross_total, false) }}</td>
                    <td class="text-end">{{ number_format($cat->share_percent, 1) }}%</td>
                </tr>
                @foreach ($cat->expenses as $expense)
                    @php
                        $cc = $expense->costCenter;
                        $ccNm = $cc ? ($localeAr ? $cc->name_ar : $cc->name_en) : '—';
                    @endphp
                    <tr>
                        <td>{{ $expense->date?->format('Y-m-d') }}</td>
                        <td>{{ $expense->category_label }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($expense->description, 60) }}</td>
                        <td>{{ $ccNm }}</td>
                        <td class="text-end">{{ CurrencyHelper::format_accounting_amount($expense->total, false) }}</td>
                        <td class="text-end">{{ number_format($expense->share_percent, 2) }}%</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">@lang('accounting::lang.expense_report_print_footer') — {{ now()->format('Y-m-d H:i') }}</div>
</body>
</html>
