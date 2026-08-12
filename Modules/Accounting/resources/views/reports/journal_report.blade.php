@extends('layouts.app')
@section('title', __('accounting::lang.journal_report'))

@section('content')
    @include('accounting::reports.partials.journal_report._styles')

    @php
        $localeAr = app()->getLocale() === 'ar';

        if (isset($journals) && $journals->isNotEmpty()) {
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
            // Returns before generic sales/purchases (same module filter group, different label).
            if ($subs->contains('sell-return')) {
                return __('accounting::lang.journal_source_sell_return');
            }
            if ($subs->contains('purchases-return')) {
                return __('accounting::lang.journal_source_purchase_return');
            }
            if ($subs->intersect(collect(['sell', 'sell_cash', 'sales_revenue']))->isNotEmpty()) {
                return __('accounting::lang.journal_source_sales');
            }
            if ($subs->contains('purchases')) {
                return __('accounting::lang.journal_source_purchases');
            }
            if ($subs->contains('expense')) {
                return __('accounting::lang.expense_report');
            }

            return __('accounting::lang.automatic_journal');
        };

        $periodFrom = $startDate ?? request('start_date');
        $periodTo = $endDate ?? request('end_date');
        $journalCount = isset($journals) ? $journals->count() : 0;
    @endphp

    <div class="container-fluid py-4 jr-report" id="jr-report-root">
        @include('accounting::reports.partials.journal_report._print_header', [
            'startDate' => $periodFrom,
            'endDate' => $periodTo,
        ])

        <div class="jr-report-hero no-print">
            <h1>{{ __('accounting::lang.journal_report') }}</h1>
        </div>

        <div class="card jr-filter-card no-print mb-4">
            <div class="card-header">
                <i class="fas fa-filter me-2 text-primary"></i>{{ __('accounting::lang.filter') }}
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('journal-report') }}" id="jr-filter-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.from_date') }}</label>
                            <input type="date" name="start_date" class="form-control form-control-sm"
                                value="{{ request('start_date', $periodFrom) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.to_date') }}</label>
                            <input type="date" name="end_date" class="form-control form-control-sm"
                                value="{{ request('end_date', $periodTo) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.ref_no') }}</label>
                            <input type="text" name="ref_no" class="form-control form-control-sm"
                                value="{{ $refNo ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.note') }}</label>
                            <input type="text" name="note" class="form-control form-control-sm"
                                value="{{ $note ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="journal_source">{{ __('accounting::lang.journal_source') }}</label>
                            <select name="journal_source[]" id="journal_source" class="form-select form-select-sm" multiple>
                                @php $js = $journalSources ?? []; @endphp
                                <option value="sales" @selected(in_array('sales', $js, true))>{{ __('accounting::lang.journal_source_sales') }}</option>
                                <option value="purchases" @selected(in_array('purchases', $js, true))>{{ __('accounting::lang.journal_source_purchases') }}</option>
                                <option value="receipt_voucher" @selected(in_array('receipt_voucher', $js, true))>{{ __('accounting::lang.journal_source_receipt_voucher') }}</option>
                                <option value="payment_voucher" @selected(in_array('payment_voucher', $js, true))>{{ __('accounting::lang.journal_source_payment_voucher') }}</option>
                            </select>
                            <div class="form-text">{{ __('accounting::lang.journal_source_hint') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}</label>
                            <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                                class="form-select form-select-sm" multiple>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}"
                                        @selected(in_array($costCenter->id, $choose_cost_center_select ?? []))>
                                        {{ $localeAr ? $costCenter->name_ar : $costCenter->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i>{{ __('accounting::lang.search') }}
                            </button>
                            <a href="{{ route('journal-report') }}" class="btn btn-light btn-sm">{{ __('accounting::lang.clear_filters') }}</a>
                            <button type="button" class="btn btn-light-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>{{ __('accounting::lang.print_full_report') }}
                            </button>
                            <button type="button" id="journalExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                            <button type="button" id="journalExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4 jr-kpi-row">
            <div class="col-md-3 col-6">
                <div class="jr-kpi jr-kpi--count">
                    <div class="jr-kpi-label">{{ __('accounting::lang.journals_count') }}</div>
                    <div class="jr-kpi-value">{{ $journalCount }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="jr-kpi jr-kpi--debit">
                    <div class="jr-kpi-label">{{ __('accounting::lang.debit') }}</div>
                    <div class="jr-kpi-value">@format_currency($totalDebit ?? 0)</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="jr-kpi jr-kpi--credit">
                    <div class="jr-kpi-label">{{ __('accounting::lang.credit') }}</div>
                    <div class="jr-kpi-value">@format_currency($totalCredit ?? 0)</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="jr-kpi jr-kpi--diff">
                    <div class="jr-kpi-label">{{ __('accounting::lang.difference') }}</div>
                    <div class="jr-kpi-value">@format_currency($difference ?? 0)</div>
                </div>
            </div>
        </div>

        <div id="jr-entries-area">
            @if (isset($journals) && $journals->isNotEmpty())
                @foreach ($journals as $journal)
                    @include('accounting::reports.partials.journal_report._entry', [
                        'journal' => $journal,
                        'jrCostCenterMap' => $jrCostCenterMap,
                        'jrInferSource' => $jrInferSource,
                    ])
                @endforeach
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-10">
                        <img src="/assets/media/illustrations/empty-content.svg" class="w-200px mb-4" alt="">
                        <h4 class="fw-semibold text-gray-700">@lang('accounting::lang.no_data')</h4>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        const journalExportPdfUrl = '{{ route('journal-report-export-pdf') }}';
        const journalExportExcelUrl = '{{ route('journal-report-export-excel') }}';

        function buildJournalQuery() {
            const params = new URLSearchParams();
            const form = document.getElementById('jr-filter-form');
            if (!form) return '';
            const fd = new FormData(form);
            for (const [key, value] of fd.entries()) {
                if (value !== '') params.append(key, value);
            }
            return params.toString();
        }

        $(document).ready(function() {
            $('#journal_source').select2({
                placeholder: @json(__('accounting::lang.journal_source_placeholder')),
                width: '100%',
            });
            $('#choose_cost_center_select').select2({ width: '100%' });

            $('#journalExportPdf').on('click', function() {
                window.open(journalExportPdfUrl + '?' + buildJournalQuery(), '_blank');
            });
            $('#journalExportExcel').on('click', function() {
                window.location.href = journalExportExcelUrl + '?' + buildJournalQuery();
            });
        });

        function printJournalCard(cardId) {
            const card = document.getElementById(cardId);
            if (!card) return;
            const root = document.getElementById('jr-report-root');
            const printHeader = root ? root.querySelector('.jr-print-doc-header') : null;
            const w = window.open('', '_blank');
            const dir = document.documentElement.getAttribute('dir') || 'rtl';
            const lang = document.documentElement.getAttribute('lang') || 'ar';
            w.document.write(`
                <!DOCTYPE html>
                <html lang="${lang}" dir="${dir}">
                <head>
                    <meta charset="utf-8">
                    <title>{{ __('accounting::lang.journal_report') }}</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
                    <style>
                        @page { size: A4 portrait; margin: 12mm; }
                        body { font-size: 11px; padding: 12px; }
                        .jr-entry { page-break-inside: avoid; border: 1px solid #94a3b8; margin-bottom: 12px; }
                        .jr-lines-table { width: 100%; border-collapse: collapse; }
                        .jr-lines-table th, .jr-lines-table td { border: 1px solid #cbd5e1; padding: 4px 6px; }
                        .jr-lines-table thead th { background: #334155; color: #fff; font-size: 9px; }
                        .col-amount { text-align: end; font-variant-numeric: tabular-nums; }
                        .amount-debit { background: #eff6ff; }
                        .amount-credit { background: #ecfdf5; }
                        .jr-entry-actions, .no-print { display: none !important; }
                    </style>
                </head>
                <body>
                    ${printHeader ? printHeader.outerHTML : ''}
                    ${card.outerHTML}
                </body>
                </html>
            `);
            w.document.close();
            w.focus();
            setTimeout(() => { w.print(); w.close(); }, 400);
        }
    </script>
@endsection
