<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.journal_report') }}</title>
    @include('accounting::reports.partials.journal_report._styles')
    <style>
        body {
            font-family: DejaVu Sans, 'Segoe UI', Tahoma, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            padding: 14px 16px;
            background: #fff;
        }

        .jr-print-doc-header {
            display: block !important;
        }

        .jr-report-hero,
        .jr-kpi-row {
            display: none;
        }

        .jr-kpi-row.print-summary {
            display: flex !important;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .jr-kpi.print-summary .jr-kpi {
            flex: 1;
            min-width: 100px;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .jr-entry {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 10px;
        }

        .jr-entry-actions {
            display: none !important;
        }

        .jr-lines-table tbody tr:hover {
            background: inherit !important;
        }

        .print-page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            padding: 4px;
            border-top: 1px solid #e2e8f0;
        }

        @page {
            size: A4 portrait;
            margin: 14mm 10mm 18mm;
        }
    </style>
</head>
<body class="jr-report">
    @php
        $localeAr = app()->getLocale() === 'ar';

        if ($journals->isNotEmpty()) {
            $journals->loadMissing('added_by');
            $ccIds = $journals->flatMap(fn ($j) => $j->transactions->pluck('cost_center_id'))->filter()->unique();
            $jrCostCenterMap = $ccIds->isNotEmpty()
                ? \Modules\Accounting\Models\AccountingCostCenter::query()->whereIn('id', $ccIds)->get()->keyBy('id')
                : collect();
        } else {
            $jrCostCenterMap = collect();
        }

        $jrInferSource = function ($journal) {
            $subs = $journal->transactions->pluck('sub_type')->filter()->unique();
            if ($subs->contains('receipt_voucher')) {
                return __('accounting::lang.journal_source_receipt_voucher');
            }
            if ($subs->contains('payment_voucher')) {
                return __('accounting::lang.journal_source_payment_voucher');
            }
            if ($subs->intersect(collect(['sell', 'sell-return', 'sell_cash', 'sales_revenue']))->isNotEmpty()) {
                return __('accounting::lang.journal_source_sales');
            }
            if ($subs->intersect(collect(['purchases', 'purchases-return']))->isNotEmpty()) {
                return __('accounting::lang.journal_source_purchases');
            }
            if ($subs->contains('expense')) {
                return __('accounting::lang.expense_report');
            }

            return __('accounting::lang.automatic_journal');
        };

        foreach ($journals as $journal) {
            $journal->journal_debit = (float) $journal->transactions->where('type', 'debit')->sum('amount');
            $journal->journal_credit = (float) $journal->transactions->where('type', 'credit')->sum('amount');
            $journal->journal_diff = abs($journal->journal_debit - $journal->journal_credit);
        }
    @endphp

    @include('accounting::reports.partials.journal_report._print_header', [
        'startDate' => $startDate ?? null,
        'endDate' => $endDate ?? null,
    ])

    <div class="row g-2 mb-3 jr-kpi-row print-summary" style="display:flex;">
        <div class="col">
            <div class="jr-kpi jr-kpi--count" style="padding:8px;border:1px solid #e2e8f0;">
                <div class="jr-kpi-label">{{ __('accounting::lang.journals_count') }}</div>
                <div class="jr-kpi-value">{{ $journals->count() }}</div>
            </div>
        </div>
        <div class="col">
            <div class="jr-kpi jr-kpi--debit" style="padding:8px;border:1px solid #e2e8f0;">
                <div class="jr-kpi-label">{{ __('accounting::lang.debit') }}</div>
                <div class="jr-kpi-value">{{ number_format((float) $totalDebit, 2) }}</div>
            </div>
        </div>
        <div class="col">
            <div class="jr-kpi jr-kpi--credit" style="padding:8px;border:1px solid #e2e8f0;">
                <div class="jr-kpi-label">{{ __('accounting::lang.credit') }}</div>
                <div class="jr-kpi-value">{{ number_format((float) $totalCredit, 2) }}</div>
            </div>
        </div>
        <div class="col">
            <div class="jr-kpi jr-kpi--diff" style="padding:8px;border:1px solid #e2e8f0;">
                <div class="jr-kpi-label">{{ __('accounting::lang.difference') }}</div>
                <div class="jr-kpi-value">{{ number_format((float) $difference, 2) }}</div>
            </div>
        </div>
    </div>

    @php $jsList = $journalSources ?? []; @endphp
    @if (! empty($jsList))
        <p class="small text-muted mb-2">
            <strong>{{ __('accounting::lang.journal_source') }}:</strong>
            @foreach ($jsList as $idx => $js)
                @if ($idx > 0) — @endif
                @switch($js)
                    @case('sales') {{ __('accounting::lang.journal_source_sales') }} @break
                    @case('purchases') {{ __('accounting::lang.journal_source_purchases') }} @break
                    @case('receipt_voucher') {{ __('accounting::lang.journal_source_receipt_voucher') }} @break
                    @case('payment_voucher') {{ __('accounting::lang.journal_source_payment_voucher') }} @break
                    @default {{ $js }}
                @endswitch
            @endforeach
        </p>
    @endif

    @forelse($journals as $journal)
        @include('accounting::reports.partials.journal_report._entry', [
            'journal' => $journal,
            'jrCostCenterMap' => $jrCostCenterMap,
            'jrInferSource' => $jrInferSource,
        ])
    @empty
        <p>{{ __('messages.no_data_found') }}</p>
    @endforelse

    <div class="print-page-footer">
        {{ config('app.name') }} — {{ __('accounting::lang.journal_report') }} — {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
