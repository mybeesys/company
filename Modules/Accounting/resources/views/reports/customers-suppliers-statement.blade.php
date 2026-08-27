@extends('layouts.app')
@section('title', __('accounting::lang.customers_and_suppliers_statement_of_account_report'))

@section('css')
    @include('accounting::reports.partials.customer-supplier-statement-styles')
@stop

@section('content')
@php
    $kpis = $kpis ?? [];
    $chart = $chart ?? ['labels' => [], 'series' => [], 'colors' => []];
    $balanceTrend = $balanceTrend ?? ['labels' => [], 'balances' => []];
    $barChart = $barChart ?? ['labels' => [], 'invoices' => [], 'payments' => []];
    $statementSummary = $statementSummary ?? [];
    $aging = $aging ?? ['buckets' => [], 'avg_collection_days' => 0];
    $analytics = $analytics ?? [];
    $contactLabel = Lang::has('accounting::lang.' . $contact->name)
        ? __('accounting::lang.' . $contact->name)
        : $contact->name;
@endphp

<div class="container-fluid css-wrap" id="css-report">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="css-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="text-muted small mb-1">{{ $company->name ?? '' }}</div>
                <h1 class="css-title">@lang('accounting::lang.customers_and_suppliers_statement_of_account_report')</h1>
                <p class="text-muted small mb-0">@lang('accounting::lang.css_report_intro')</p>
                <p class="fw-semibold mb-0 mt-2">
                    {{ $contactLabel }}
                    @if(!empty($contact->commercial_register))
                        <span class="text-muted">({{ $contact->commercial_register }})</span>
                    @elseif(!empty($contact->tax_number))
                        <span class="text-muted">({{ $contact->tax_number }})</span>
                    @endif
                </p>
                <p class="text-muted small mb-0 mt-1">
                    @lang('accounting::lang.from_date'): {{ $start_date }}
                    <span class="mx-1">—</span>
                    @lang('accounting::lang.to_date'): {{ $end_date }}
                </p>
                @if(!empty($comparePeriod))
                    <p class="text-muted small mb-0">
                        @lang('accounting::lang.css_compare_period'):
                        {{ $comparePeriod['start_date'] }} — {{ $comparePeriod['end_date'] }}
                    </p>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-light-primary" id="cssCollapseGroups">@lang('accounting::lang.css_collapse_all')</button>
                <button type="button" class="btn btn-sm btn-light" id="cssExpandGroups">@lang('accounting::lang.css_expand_all')</button>
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
            </div>
        </div>
    </div>

    <div class="css-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.customers_and_suppliers_statement_of_account_report')</h4>
        <p class="mb-0">{{ $contactLabel }}</p>
        <p class="small text-muted mb-0">{{ $start_date }} — {{ $end_date }}</p>
    </div>

    <form method="GET" action="{{ route('customers-suppliers-statement') }}" class="css-filters-card no-print" id="cssFilters">
        <input type="hidden" name="id" value="{{ $contact_id }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-3">
                <label class="form-label small" for="contact_filter">@lang('accounting::lang.cs')</label>
                <select name="id" id="contact_filter" class="form-select form-select-sm select-2">
                    @foreach ($contact_dropdown as $client)
                        <option value="{{ $client->id }}" @selected($contact_id == $client->id)>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="start_date_filter">@lang('accounting::lang.from_date')</label>
                <input type="date" name="start_date" id="start_date_filter" class="form-control form-control-sm" value="{{ $start_date }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="end_date_filter">@lang('accounting::lang.to_date')</label>
                <input type="date" name="end_date" id="end_date_filter" class="form-control form-control-sm" value="{{ $end_date }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="period_group">@lang('accounting::lang.css_period_group')</label>
                <select name="period_group" id="period_group" class="form-select form-select-sm">
                    <option value="month" @selected(($period_group ?? 'month') === 'month')>@lang('accounting::lang.cf_period_month')</option>
                    <option value="quarter" @selected(($period_group ?? '') === 'quarter')>@lang('accounting::lang.cf_period_quarter')</option>
                    <option value="year" @selected(($period_group ?? '') === 'year')>@lang('accounting::lang.cf_period_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label small" for="compare_mode">@lang('accounting::lang.css_compare')</label>
                <select name="compare_mode" id="compare_mode" class="form-select form-select-sm">
                    <option value="none" @selected(($compare_mode ?? 'none') === 'none')>@lang('accounting::lang.cf_compare_none')</option>
                    <option value="previous_period" @selected(($compare_mode ?? '') === 'previous_period')>@lang('accounting::lang.cf_compare_previous')</option>
                    <option value="previous_year" @selected(($compare_mode ?? '') === 'previous_year')>@lang('accounting::lang.cf_compare_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label small" for="choose_cost_center_select">@lang('accounting::lang.cost_center')</label>
                <select name="choose_cost_center_select[]" id="choose_cost_center_select" class="form-select form-select-sm" multiple>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}" @selected(in_array($costCenter->id, $choose_cost_center_select ?? []))>
                            {{ app()->getLocale() == 'ar'
                                ? $costCenter->account_center_number . ' - ' . $costCenter->name_ar
                                : $costCenter->account_center_number . ' - ' . $costCenter->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label small" for="establishment_ids">@lang('accounting::lang.establishment_name')</label>
                <select name="establishment_ids[]" id="establishment_ids" class="form-select form-select-sm" multiple>
                    @foreach ($establishments ?? [] as $est)
                        <option value="{{ $est->id }}" @selected(in_array($est->id, $establishment_ids ?? []))>{{ $est->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="created_by">@lang('accounting::lang.added_by')</label>
                <select name="created_by" id="created_by" class="form-select form-select-sm">
                    <option value="">@lang('messages.select')</option>
                    @foreach ($users ?? [] as $user)
                        <option value="{{ $user->id }}" @selected(($created_by ?? '') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="entry_type_filter">@lang('accounting::lang.movement_type')</label>
                <select name="entry_type" id="entry_type_filter" class="form-select form-select-sm">
                    <option value="">@lang('messages.select')</option>
                    <option value="debit" @selected(($entry_type ?? '') === 'debit')>@lang('accounting::lang.debit')</option>
                    <option value="credit" @selected(($entry_type ?? '') === 'credit')>@lang('accounting::lang.credit')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="sub_type_filter">@lang('accounting::lang.transaction_type')</label>
                <select name="sub_type" id="sub_type_filter" class="form-select form-select-sm">
                    <option value="">@lang('messages.select')</option>
                    @foreach ($available_sub_types as $availableSubType)
                        <option value="{{ $availableSubType }}" @selected(($sub_type ?? '') === $availableSubType)>
                            {{ Lang::has('accounting::lang.' . $availableSubType) ? __('accounting::lang.' . $availableSubType) : $availableSubType }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="ref_no_filter">@lang('accounting::lang.transaction_number')</label>
                <input type="text" name="ref_no" id="ref_no_filter" class="form-control form-control-sm" value="{{ $ref_no ?? '' }}">
            </div>
            <div class="col-md-6 col-lg-2 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="unsettled_only" value="1" id="unsettled_only" @checked(!empty($unsettled_only))>
                    <label class="form-check-label small" for="unsettled_only">@lang('accounting::lang.css_unsettled_only')</label>
                </div>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">@lang('report::general.filter')</button>
                <button type="button" id="statementExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                <button type="button" id="statementExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
            </div>
        </div>
    </form>

    <div class="alert {{ ($net_movement ?? 0) >= 0 ? 'alert-light-primary' : 'alert-light-warning' }} py-2 mb-3 no-print">
        <strong>@lang('accounting::lang.css_opening_balance'):</strong> @format_accounting_amount($openingBalance ?? 0)
        <span class="mx-2">|</span>
        <strong>@lang('accounting::lang.css_closing_balance'):</strong> @format_accounting_amount($closingBalance ?? 0)
        <span class="mx-2">|</span>
        <strong>@lang('accounting::lang.css_current_balance'):</strong> @format_accounting_amount($current_bal ?? 0)
        @if(isset($compareAnalytics['growth_percent']) && $compareAnalytics['growth_percent'] !== null)
            <span class="mx-2">|</span>
            <strong>@lang('accounting::lang.css_compare_growth'):</strong> {{ $compareAnalytics['growth_percent'] }}%
        @endif
    </div>

    <div class="row g-3 mb-4 no-print">
        @php
            $kpiCards = [
                ['key' => 'current_balance', 'label' => __('accounting::lang.css_current_balance'), 'highlight' => true],
                ['key' => 'total_invoices', 'label' => __('accounting::lang.css_cat_invoices')],
                ['key' => 'total_payments', 'label' => __('accounting::lang.css_cat_payments')],
                ['key' => 'total_returns', 'label' => __('accounting::lang.css_cat_returns')],
                ['key' => 'transaction_count', 'label' => __('accounting::lang.css_tx_count'), 'count' => true],
                ['key' => 'amount_due', 'label' => __('accounting::lang.css_amount_due')],
                ['key' => 'amount_paid', 'label' => __('accounting::lang.css_amount_paid')],
            ];
        @endphp
        @foreach ($kpiCards as $card)
            <div class="col-6 col-md-4 col-xl">
                <div class="css-kpi {{ !empty($card['highlight']) ? 'css-kpi-highlight' : '' }}">
                    <div class="css-kpi-label">{{ $card['label'] }}</div>
                    <div class="css-kpi-value">
                        @if(!empty($card['count']))
                            {{ number_format((int) ($kpis[$card['key']] ?? 0)) }}
                        @else
                            @format_accounting_amount($kpis[$card['key']] ?? 0)
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-lg-4">
            <div class="css-panel h-100">
                <div class="css-panel-header"><h3 class="css-panel-title">@lang('accounting::lang.css_chart_composition')</h3></div>
                <div class="css-chart-body"><div id="cssCompositionChart"></div></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="css-panel h-100">
                <div class="css-panel-header"><h3 class="css-panel-title">@lang('accounting::lang.css_chart_balance_trend')</h3></div>
                <div class="css-chart-body"><div id="cssBalanceTrend"></div></div>
            </div>
        </div>
        <div class="col-12">
            <div class="css-panel">
                <div class="css-panel-header"><h3 class="css-panel-title">@lang('accounting::lang.css_chart_invoice_payment')</h3></div>
                <div class="css-chart-body"><div id="cssBarChart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-md-6">
            <div class="css-panel h-100">
                <div class="css-panel-header"><h3 class="css-panel-title">@lang('accounting::lang.css_aging_analysis')</h3></div>
                <div class="p-3">
                    @php $buckets = $aging['buckets'] ?? []; @endphp
                    <ul class="css-analytics-list mb-3">
                        <li><span>@lang('accounting::lang.current')</span><strong class="css-fin">@format_accounting_amount($buckets['<1'] ?? 0)</strong></li>
                        <li><span>@lang('accounting::lang.1_30_days')</span><strong class="css-fin">@format_accounting_amount($buckets['1_30'] ?? 0)</strong></li>
                        <li><span>@lang('accounting::lang.31_60_days')</span><strong class="css-fin">@format_accounting_amount($buckets['31_60'] ?? 0)</strong></li>
                        <li><span>@lang('accounting::lang.61_90_days')</span><strong class="css-fin">@format_accounting_amount($buckets['61_90'] ?? 0)</strong></li>
                        <li><span>@lang('accounting::lang.91_and_over')</span><strong class="css-fin">@format_accounting_amount($buckets['>90'] ?? 0)</strong></li>
                        <li><span>@lang('accounting::lang.css_total_outstanding')</span><strong class="css-fin fw-bold">@format_accounting_amount($buckets['total_due'] ?? 0)</strong></li>
                    </ul>
                    @if(($aging['avg_collection_days'] ?? null) !== null)
                        <p class="small text-muted mb-0">@lang('accounting::lang.css_avg_collection_days'): <strong>{{ $aging['avg_collection_days'] }}</strong></p>
                    @endif
                    @if(($analytics['collection_ratio'] ?? null) !== null)
                        <p class="small text-muted mb-0 mt-1">@lang('accounting::lang.css_collection_ratio'): {{ $analytics['collection_ratio'] }}%</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="css-panel h-100">
                <div class="css-panel-header"><h3 class="css-panel-title">@lang('accounting::lang.css_top_movements')</h3></div>
                <div class="p-3">
                    <ul class="css-analytics-list">
                        @forelse($analytics['top_movements'] ?? [] as $row)
                            <li>
                                <span class="text-truncate me-2">{{ $row['label'] }}</span>
                                <strong class="css-fin {{ ($row['type'] ?? '') === 'credit' ? 'text-success' : '' }}">@format_accounting_amount($row['amount'])</strong>
                            </li>
                        @empty
                            <li class="text-muted">@lang('accounting::lang.no_data')</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="css-panel mb-4">
        <div class="css-panel-header"><h3 class="css-panel-title mb-0">@lang('accounting::lang.css_statement_summary')</h3></div>
        <div class="css-summary-grid">
            @foreach ($statementSummary as $item)
                <div class="css-summary-item {{ !empty($item['highlight']) ? 'css-highlight' : '' }}">
                    <div class="css-summary-label">{{ $item['label'] }}</div>
                    <div class="css-summary-amount {{ \App\Helpers\CurrencyHelper::is_negative_amount($item['amount']) ? 'text-danger' : '' }}">
                        @format_accounting_amount($item['amount'])
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="css-panel">
        <div class="css-panel-header d-flex justify-content-between align-items-center">
            <h3 class="css-panel-title mb-0">@lang('accounting::lang.statement_entries')</h3>
            <span class="small text-muted no-print">@lang('accounting::lang.css_running_balance_hint')</span>
        </div>
        <div class="css-statement-scroll p-0">
            <table class="table table-sm mb-0" id="cssStatementTable">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.operation_date')</th>
                        <th>@lang('accounting::lang.transaction_number')</th>
                        <th>@lang('accounting::lang.transaction_type')</th>
                        <th>@lang('accounting::lang.ledger_stmt_col_description')</th>
                        <th>@lang('accounting::lang.establishment_name')</th>
                        <th>@lang('accounting::lang.cost_center')</th>
                        <th class="css-fin">@lang('accounting::lang.debit')</th>
                        <th class="css-fin">@lang('accounting::lang.credit')</th>
                        <th class="css-fin">@lang('accounting::lang.css_running_balance')</th>
                        <th>@lang('accounting::lang.added_by')</th>
                        <th class="text-center no-print">@lang('messages.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @php $seenGroups = []; @endphp
                    @forelse ($linePaginator as $key => $line)
                        @php
                            $isOpening = ($line['row_type'] ?? '') === 'opening';
                            $groupKey = $line['group_key'] ?? 'row-' . $key;
                            $isGroupStart = !$isOpening && !isset($seenGroups[$groupKey]);
                            if ($isGroupStart) { $seenGroups[$groupKey] = true; }
                            $hasSiblings = !empty($line['has_group_siblings']);
                            $isSettlement = !empty($line['is_settlement']);
                            $rowClass = trim(implode(' ', array_filter([
                                $isOpening ? 'css-opening-row' : '',
                                !empty($line['is_important']) ? 'css-important-row' : '',
                                $isSettlement ? 'css-settlement-row' : '',
                                // Only collapse true multi-line journal details — never hide invoice payments/settlements.
                                ($isGroupStart || $isOpening || $isSettlement || !$hasSiblings) ? '' : 'css-group-child',
                            ])));
                        @endphp
                        <tr class="{{ $rowClass }}" data-css-group="{{ $groupKey }}" @if($isGroupStart && !$isOpening && $hasSiblings) data-css-group-parent="1" @endif>
                            <td>{{ $line['operation_date'] ? \Carbon\Carbon::parse($line['operation_date'])->format('Y-m-d') : '—' }}</td>
                            <td>
                                @if(!empty($line['detail_url']))
                                    <a href="{{ $line['detail_url'] }}" class="btn-modal" data-container="#printJournalEntry">{{ $line['ref_no'] }}</a>
                                @else
                                    {{ $line['ref_no'] }}
                                @endif
                            </td>
                            <td>
                                @if($isSettlement)
                                    <span class="badge badge-light-success">{{ $line['transaction_type'] }}</span>
                                @else
                                    {{ $line['transaction_type'] }}
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 180px;" title="{{ $line['description'] }}">
                                {{ $line['description'] }}
                                @if(isset($line['tax_amount']) && $line['tax_amount'] > 0)
                                    <span class="badge badge-light-info ms-1">VAT @format_accounting_amount($line['tax_amount'], false)</span>
                                @endif
                            </td>
                            <td>{{ $line['establishment_name'] }}</td>
                            <td>{{ $line['cost_center'] }}</td>
                            <td class="css-fin">@if(($line['debit'] ?? 0) > 0) @format_accounting_amount($line['debit'], false) @else — @endif</td>
                            <td class="css-fin">@if(($line['credit'] ?? 0) > 0) @format_accounting_amount($line['credit'], false) @else — @endif</td>
                            <td class="css-fin fw-bold {{ \App\Helpers\CurrencyHelper::is_negative_amount($line['running_balance'] ?? 0) ? 'css-fin-negative' : '' }}">
                                @format_accounting_amount($line['running_balance'] ?? 0)
                            </td>
                            <td>{{ $line['added_by'] }}</td>
                            <td class="text-center no-print">
                                @if($isGroupStart && !$isOpening && $hasSiblings)
                                    <button type="button" class="btn btn-xs btn-light btn-sm py-0 px-1 css-toggle-group" data-group="{{ $groupKey }}">±</button>
                                @elseif(!empty($line['detail_url']))
                                    <a href="{{ $line['detail_url'] }}" class="btn btn-xs btn-light-primary btn-sm py-1 px-2 btn-modal" data-container="#printJournalEntry">@lang('accounting::lang.voucher_show')</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">@lang('messages.no_data_found')</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="6">@lang('accounting::lang.total') (@lang('accounting::lang.filter'))</td>
                        <td class="css-fin">@format_accounting_amount($period_debit ?? 0, false)</td>
                        <td class="css-fin">@format_accounting_amount($period_credit ?? 0, false)</td>
                        <td class="css-fin">@format_accounting_amount($closingBalance ?? 0)</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            <div class="d-flex justify-content-center mt-3 p-2 no-print">
                {{ $linePaginator->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <div class="css-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.css_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
    @parent
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const statementExportPdfUrl = @json(route('customers-suppliers-statement-export-pdf'));
        const statementExportExcelUrl = @json(route('customers-suppliers-statement-export-excel'));
        const currencyLabel = @json(\App\Helpers\CurrencyHelper::get_format_currency());
        const cssChart = @json($chart);
        const cssBalanceTrend = @json($balanceTrend);
        const cssBarChart = @json($barChart);

        function buildStatementQuery() {
            const form = document.getElementById('cssFilters');
            return new URLSearchParams(new FormData(form)).toString();
        }

        function formatChartAmount(value) {
            const n = Number(value) || 0;
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        $(document).ready(function() {
            $('#choose_cost_center_select, #establishment_ids, #contact_filter, #entry_type_filter, #sub_type_filter').select2({ width: '100%' });

            $('#contact_filter').on('change', function() {
                const id = $(this).val();
                const url = new URL(window.location.href);
                url.searchParams.set('id', id);
                window.location = url.toString();
            });

            $('#statementExportPdf').on('click', () => window.open(statementExportPdfUrl + '?' + buildStatementQuery(), '_blank'));
            $('#statementExportExcel').on('click', () => { window.location.href = statementExportExcelUrl + '?' + buildStatementQuery(); });

            if (typeof ApexCharts !== 'undefined') {
                const donutTotal = (cssChart.series || []).reduce((s, v) => s + Number(v), 0);
                if (donutTotal > 0) {
                    new ApexCharts(document.querySelector('#cssCompositionChart'), {
                        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
                        series: cssChart.series,
                        labels: cssChart.labels,
                        colors: cssChart.colors,
                        legend: { position: 'bottom' },
                        dataLabels: { enabled: true },
                        tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                        plotOptions: {
                            pie: {
                                donut: {
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: @json(__('accounting::lang.total')),
                                            formatter: () => formatChartAmount(donutTotal),
                                        },
                                    },
                                },
                            },
                        },
                    }).render();
                }

                if ((cssBalanceTrend.labels || []).length) {
                    new ApexCharts(document.querySelector('#cssBalanceTrend'), {
                        chart: { type: 'line', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
                        series: [{ name: @json(__('accounting::lang.css_running_balance')), data: cssBalanceTrend.balances }],
                        xaxis: { categories: cssBalanceTrend.labels },
                        stroke: { curve: 'smooth', width: 3 },
                        colors: ['#e9b71f'],
                        yaxis: { labels: { formatter: (v) => formatChartAmount(v) } },
                        tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                    }).render();
                }

                if ((cssBarChart.labels || []).length) {
                    new ApexCharts(document.querySelector('#cssBarChart'), {
                        chart: { type: 'bar', height: 280, stacked: false, toolbar: { show: false } },
                        series: [
                            { name: @json(__('accounting::lang.css_cat_invoices')), data: cssBarChart.invoices },
                            { name: @json(__('accounting::lang.css_cat_payments')), data: cssBarChart.payments },
                        ],
                        xaxis: { categories: cssBarChart.labels },
                        colors: ['#e9b71f', '#17C653'],
                        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                        tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                    }).render();
                }
            }

            $('.css-toggle-group').on('click', function() {
                const g = $(this).data('group');
                $(`tr[data-css-group="${g}"].css-group-child`).toggleClass('css-visible');
            });

            $('#cssExpandGroups').on('click', () => $('.css-group-child').addClass('css-visible'));
            $('#cssCollapseGroups').on('click', () => $('.css-group-child').removeClass('css-visible'));

            // Default: collapsed groups
            $('.css-group-child').removeClass('css-visible');
        });
    </script>
@endsection
