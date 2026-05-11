<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.journal_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .summary { margin: 8px 0; padding: 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: start; }
        thead th { background: #e5e7eb; text-align: center; }
        .journal-title { margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ __('accounting::lang.journal_report') }}</h2>
    <div style="font-size: 11px; color: #4b5563; margin-bottom: 6px;">{{ __('accounting::lang.journal_report_manual_only') }}</div>
    <div>{{ __('accounting::lang.from_date') }}:
        {{ $startDate ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') : '' }} |
        {{ __('accounting::lang.to_date') }}:
        {{ $endDate ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') : '' }}</div>
    @php
        $jsList = $journalSources ?? [];
    @endphp
    @if (! empty($jsList))
        <div>{{ __('accounting::lang.journal_source') }}:
            @foreach ($jsList as $idx => $js)
                @if ($idx > 0) — @endif
                @switch($js)
                    @case('sales') {{ __('accounting::lang.journal_source_sales') }} @break
                    @case('purchases') {{ __('accounting::lang.journal_source_purchases') }} @break
                    @case('receipt_voucher') {{ __('accounting::lang.journal_source_receipt_voucher') }} @break
                    @case('payment_voucher') {{ __('accounting::lang.journal_source_payment_voucher') }} @break
                    @case('manual_journal') {{ __('accounting::lang.journal_source_manual_journal') }} @break
                    @default {{ $js }}
                @endswitch
            @endforeach
        </div>
    @endif
    <div class="summary">
        <strong>{{ __('accounting::lang.debit') }}:</strong> {{ number_format((float) $totalDebit, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.credit') }}:</strong> {{ number_format((float) $totalCredit, 2) }}
        <span style="margin: 0 8px;">|</span>
        <strong>{{ __('accounting::lang.difference') }}:</strong> {{ number_format((float) $difference, 2) }}
    </div>

    @forelse($journals as $journal)
        <div class="journal-title">
            {{ __('accounting::lang.ref_no') }}: {{ $journal->ref_no }} |
            {{ __('accounting::lang.operation_date') }}:
            {{ $journal->operation_date ? \Illuminate\Support\Carbon::parse($journal->operation_date)->format('Y-m-d') : '' }}
        </div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('accounting::lang.account_name') }}</th>
                    <th>{{ __('accounting::lang.gl_code') }}</th>
                    <th>{{ __('accounting::lang.note') }}</th>
                    <th>{{ __('accounting::lang.debit') }}</th>
                    <th>{{ __('accounting::lang.credit') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journal->transactions as $transaction)
                    <tr>
                        <td>{{ app()->getLocale() == 'ar' ? $transaction->name_ar : $transaction->name_en }}</td>
                        <td>{{ $transaction->gl_code }}</td>
                        <td>{{ $transaction->note ?? '--' }}</td>
                        <td>{{ $transaction->type === 'debit' ? number_format((float) $transaction->amount, 2) : '-' }}</td>
                        <td>{{ $transaction->type === 'credit' ? number_format((float) $transaction->amount, 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div>{{ __('messages.no_data_found') }}</div>
    @endforelse
</body>
</html>

