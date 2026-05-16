<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.expense_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: start; }
        thead th { background: #e5e7eb; }
        .text-end { text-align: end; }
        h2 { margin: 0 0 8px; font-size: 16px; }
        h3 { margin: 14px 0 6px; font-size: 13px; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.expense_report') }}</h2>
    <div>{{ __('accounting::lang.from_date') }}: {{ $startDate }} | {{ __('accounting::lang.to_date') }}: {{ $endDate }}</div>
    @php $summary = $summary ?? ['count' => 0, 'net' => 0, 'tax' => 0, 'gross' => 0]; @endphp
    <div class="summary">
        <strong>{{ __('accounting::lang.expense_report_count') }}:</strong> {{ number_format($summary['count']) }}
        | <strong>{{ __('accounting::lang.expense_report_net') }}:</strong> {{ number_format($summary['net'], 2) }}
        | <strong>{{ __('accounting::lang.expense_report_tax') }}:</strong> {{ number_format($summary['tax'], 2) }}
        | <strong>{{ __('accounting::lang.expense_report_gross') }}:</strong> {{ number_format($summary['gross'], 2) }}
    </div>

    @if (($byCategory ?? collect())->isNotEmpty())
        <h3>{{ __('accounting::lang.expense_report_by_category') }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ __('expense::fields.category') }}</th>
                    <th class="text-end">{{ __('accounting::lang.expense_report_count') }}</th>
                    <th class="text-end">{{ __('expense::fields.net_amount') }}</th>
                    <th class="text-end">{{ __('expense::fields.tax_amount') }}</th>
                    <th class="text-end">{{ __('expense::fields.gross_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byCategory as $row)
                    <tr>
                        <td>{{ $row->category_name }}</td>
                        <td class="text-end">{{ (int) $row->expense_count }}</td>
                        <td class="text-end">{{ number_format($row->net_total, 2) }}</td>
                        <td class="text-end">{{ number_format($row->tax_total, 2) }}</td>
                        <td class="text-end">{{ number_format($row->gross_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>{{ __('accounting::lang.expense_report_details') }}</h3>
    <table>
        <thead>
            <tr>
                <th>{{ __('expense::fields.expense_date') }}</th>
                <th>#</th>
                <th>{{ __('expense::fields.category') }}</th>
                <th>{{ __('expense::fields.credit_account') }}</th>
                <th>{{ __('expense::fields.description') }}</th>
                <th class="text-end">{{ __('expense::fields.net_amount') }}</th>
                <th class="text-end">{{ __('expense::fields.tax_amount') }}</th>
                <th class="text-end">{{ __('expense::fields.gross_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $localeAr = app()->getLocale() === 'ar'; @endphp
            @forelse ($expenses ?? [] as $expense)
                @php
                    $credit = $expense->creditAccount;
                    $creditNm = $credit ? (($localeAr ? $credit->name_ar : $credit->name_en).' ('.$credit->gl_code.')') : '—';
                @endphp
                <tr>
                    <td>{{ $expense->date?->format('Y-m-d') ?? '' }}</td>
                    <td>{{ $expense->id }}</td>
                    <td>{{ $expense->category?->name ?? '' }}</td>
                    <td>{{ $creditNm }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($expense->description, 60) }}</td>
                    <td class="text-end">{{ number_format($expense->net_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $expense->getRawOriginal('tax'), 2) }}</td>
                    <td class="text-end">{{ number_format($expense->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">{{ __('accounting::lang.no_data') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (($expenses ?? collect())->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5"><strong>{{ __('accounting::lang.total') }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($summary['net'], 2) }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($summary['tax'], 2) }}</strong></td>
                    <td class="text-end"><strong>{{ number_format($summary['gross'], 2) }}</strong></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
