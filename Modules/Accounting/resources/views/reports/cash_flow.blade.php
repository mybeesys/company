@extends('layouts.app')
@section('title', __('accounting::lang.cash_flow_statement'))

@section('css')
    @include('accounting::reports.partials.cash-flow-styles')
@stop

@section('content')
@php
    $analytics = $analytics ?? [];
    $kpis = $analytics['kpis'] ?? [];
    $chart = $chart ?? ['labels' => [], 'series' => [], 'colors' => []];
    $barChart = $barChart ?? ['labels' => [], 'inflows' => [], 'outflows' => []];
    $monthlyTrend = $monthlyTrend ?? ['labels' => [], 'inflows' => [], 'outflows' => [], 'net' => []];
    $statement = $statement ?? [];
    $sectionSummaries = $sectionSummaries ?? [];
@endphp

<div class="container-fluid cash-flow-wrap" id="cash-flow-report">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="cf-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="text-muted small mb-1">{{ $company->name ?? '' }}</div>
                <h1 class="cf-title">@lang('accounting::lang.cash_flow_statement')</h1>
                <p class="text-muted small mb-0">@lang('accounting::lang.cf_report_intro')</p>
                <p class="text-muted small mb-0 mt-1">
                    @lang('accounting::lang.from_date'): {{ $startDate }}
                    <span class="mx-1">—</span>
                    @lang('accounting::lang.to_date'): {{ $endDate }}
                </p>
                @if(!empty($comparePeriod))
                    <p class="text-muted small mb-0">
                        @lang('accounting::lang.cf_compare_period'):
                        {{ $comparePeriod['start_date'] }} — {{ $comparePeriod['end_date'] }}
                    </p>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-light-primary" id="cfExpandAll">@lang('accounting::lang.cf_expand_all')</button>
                <button type="button" class="btn btn-sm btn-light" id="cfCollapseAll">@lang('accounting::lang.cf_collapse_all')</button>
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
            </div>
        </div>
    </div>

    <div class="cf-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.cash_flow_statement')</h4>
        <p class="small text-muted mb-0">{{ $startDate }} — {{ $endDate }}</p>
    </div>

    <form method="GET" action="{{ route('cash-flow') }}" class="cf-filters-card no-print" id="cashFlowFilters">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="start_date">@lang('accounting::lang.from_date')</label>
                <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="end_date">@lang('accounting::lang.to_date')</label>
                <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="period_group">@lang('accounting::lang.cf_period_group')</label>
                <select name="period_group" id="period_group" class="form-select form-select-sm">
                    <option value="month" @selected(($period_group ?? 'month') === 'month')>@lang('accounting::lang.cf_period_month')</option>
                    <option value="quarter" @selected(($period_group ?? '') === 'quarter')>@lang('accounting::lang.cf_period_quarter')</option>
                    <option value="year" @selected(($period_group ?? '') === 'year')>@lang('accounting::lang.cf_period_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="compare_mode">@lang('accounting::lang.cf_compare')</label>
                <select name="compare_mode" id="compare_mode" class="form-select form-select-sm">
                    <option value="none" @selected(($compare_mode ?? 'none') === 'none')>@lang('accounting::lang.cf_compare_none')</option>
                    <option value="previous_period" @selected(($compare_mode ?? '') === 'previous_period')>@lang('accounting::lang.cf_compare_previous')</option>
                    <option value="previous_year" @selected(($compare_mode ?? '') === 'previous_year')>@lang('accounting::lang.cf_compare_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="movement_type">@lang('accounting::lang.movement_type')</label>
                <select name="movement_type" id="movement_type" class="form-select form-select-sm">
                    <option value="">@lang('messages.select')</option>
                    <option value="credit" @selected(($movement_type ?? '') === 'credit')>@lang('accounting::lang.cash_inflows')</option>
                    <option value="debit" @selected(($movement_type ?? '') === 'debit')>@lang('accounting::lang.cash_outflows')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="activity_section">@lang('accounting::lang.activity_section')</label>
                <select name="activity_section" id="activity_section" class="form-select form-select-sm">
                    <option value="">@lang('messages.select')</option>
                    <option value="operating" @selected(($activity_section ?? '') === 'operating')>@lang('accounting::lang.operating_activities')</option>
                    <option value="investing" @selected(($activity_section ?? '') === 'investing')>@lang('accounting::lang.investing_activities')</option>
                    <option value="financing" @selected(($activity_section ?? '') === 'financing')>@lang('accounting::lang.financing_activities')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label small" for="choose_cost_center_select">@lang('accounting::lang.cost_center')</label>
                <select name="choose_cost_center_select[]" id="choose_cost_center_select" class="form-select form-select-sm" multiple>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}" @selected(in_array($costCenter->id, $choose_cost_center_select ?? []))>
                            {{ app()->getLocale() == 'ar'
                                ? $costCenter->account_center_number.' - '.$costCenter->name_ar
                                : $costCenter->account_center_number.' - '.$costCenter->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 col-lg-8">
                <label class="form-label small" for="sub_types">@lang('accounting::lang.transaction_type')</label>
                <select name="sub_types[]" id="sub_types" class="form-select form-select-sm" multiple>
                    @foreach ($availableSubTypes as $subType)
                        <option value="{{ $subType }}" @selected(in_array($subType, $selected_sub_types ?? []))>
                            {{ \Illuminate\Support\Facades\Lang::has('accounting::lang.'.$subType) ? __('accounting::lang.'.$subType) : $subType }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm">@lang('report::general.filter')</button>
                <button type="button" id="cashFlowExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                <button type="button" id="cashFlowExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
            </div>
        </div>
    </form>

    <div class="alert {{ ($netCashFlow ?? 0) >= 0 ? 'alert-success' : 'alert-warning' }} py-2 mb-3 no-print">
        <strong>@lang('accounting::lang.net_cash_flows'):</strong> @format_accounting_amount($netCashFlow ?? 0)
        <span class="mx-2">|</span>
        <strong>@lang('accounting::lang.cf_opening_cash'):</strong> @format_accounting_amount($openingCash ?? 0)
        <span class="mx-2">|</span>
        <strong>@lang('accounting::lang.cf_closing_cash'):</strong> @format_accounting_amount($closingCash ?? 0)
        @if(isset($compareAnalytics['growth_percent']) && $compareAnalytics['growth_percent'] !== null)
            <span class="mx-2">|</span>
            <strong>@lang('accounting::lang.cf_compare_growth'):</strong> {{ $compareAnalytics['growth_percent'] }}%
        @endif
    </div>

    <div class="row g-3 mb-4 no-print">
        @php
            $kpiCards = [
                ['key' => 'net_cash_flow', 'label' => __('accounting::lang.net_cash_flows'), 'money' => true],
                ['key' => 'operating_net', 'label' => __('accounting::lang.operating_activities'), 'money' => true],
                ['key' => 'investing_net', 'label' => __('accounting::lang.investing_activities'), 'money' => true],
                ['key' => 'financing_net', 'label' => __('accounting::lang.financing_activities'), 'money' => true],
                ['key' => 'closing_cash', 'label' => __('accounting::lang.cf_closing_cash'), 'money' => true],
                ['key' => 'liquidity_growth', 'label' => __('accounting::lang.cf_liquidity_growth'), 'pct' => true],
            ];
        @endphp
        @foreach ($kpiCards as $card)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="cf-kpi">
                    <div class="cf-kpi-label">{{ $card['label'] }}</div>
                    <div class="cf-kpi-value">
                        @if(!empty($card['money']))
                            @format_accounting_amount($kpis[$card['key']] ?? 0)
                        @elseif(!empty($card['pct']))
                            @if(($kpis['liquidity_growth'] ?? null) !== null)
                                {{ $kpis['liquidity_growth'] }}%
                            @else
                                —
                            @endif
                        @else
                            {{ $kpis[$card['key']] ?? '—' }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-lg-4">
            <div class="cf-panel h-100">
                <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_chart_sections')</h3></div>
                <div class="cf-chart-body"><div id="cfSectionChart"></div></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="cf-panel h-100">
                <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_chart_trend')</h3></div>
                <div class="cf-chart-body"><div id="cfTrendChart"></div></div>
            </div>
        </div>
        <div class="col-12">
            <div class="cf-panel">
                <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_chart_in_out')</h3></div>
                <div class="cf-chart-body"><div id="cfBarChart"></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-md-6">
            <div class="cf-panel h-100">
                <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_top_inflows')</h3></div>
                <div class="p-3">
                    <ul class="cf-analytics-list">
                        @forelse($analytics['top_inflows'] ?? [] as $row)
                            <li><span>{{ $row['label'] }}</span><strong class="cf-fin">@format_accounting_amount($row['inflows'])</strong></li>
                        @empty
                            <li class="text-muted">@lang('accounting::lang.no_data')</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="cf-panel h-100">
                <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_top_outflows')</h3></div>
                <div class="p-3">
                    <ul class="cf-analytics-list">
                        @forelse($analytics['top_outflows'] ?? [] as $row)
                            <li><span>{{ $row['label'] }}</span><strong class="cf-fin text-danger">@format_accounting_amount($row['outflows'])</strong></li>
                        @empty
                            <li class="text-muted">@lang('accounting::lang.no_data')</li>
                        @endforelse
                    </ul>
                    @if(!empty($analytics['operating_cash_ratio']))
                        <p class="small text-muted mb-0 mt-2">
                            @lang('accounting::lang.cf_operating_ratio'): {{ $analytics['operating_cash_ratio'] }}%
                        </p>
                    @endif
                    @if(($analytics['cash_burn_rate'] ?? 0) > 0)
                        <p class="small text-muted mb-0">
                            @lang('accounting::lang.cf_burn_rate'): @format_accounting_amount($analytics['cash_burn_rate'])
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="cf-panel mb-4">
        <div class="cf-panel-header d-flex justify-content-between align-items-center">
            <h3 class="cf-panel-title mb-0">@lang('accounting::lang.cash_flow_statement')</h3>
        </div>
        <div class="cf-statement-scroll p-0">
            <table class="table table-sm mb-0" id="cfStatementTable">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.cf_line_item')</th>
                        <th class="cf-fin">@lang('accounting::lang.cash_inflows')</th>
                        <th class="cf-fin">@lang('accounting::lang.cash_outflows')</th>
                        <th class="cf-fin">@lang('accounting::lang.amount')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statement as $section)
                        <tr class="cf-section-row" data-cf-section="{{ $section->section_key }}">
                            <td colspan="4">
                                <i class="fa fa-chevron-down fa-xs me-2 cf-section-icon"></i>
                                {{ $section->section_label }}
                            </td>
                        </tr>
                        @foreach ($section->lines as $line)
                            <tr class="cf-line-row cf-section-{{ $section->section_key }} {{ $line->is_subtotal ? 'cf-subtotal' : '' }} {{ $line->depth ? 'cf-indent-'.$line->depth : '' }}">
                                <td class="{{ $line->depth ? 'cf-indent-1' : '' }}">{{ $line->label }}</td>
                                <td class="cf-fin">@if($line->inflows > 0) @format_accounting_amount($line->inflows, false) @else — @endif</td>
                                <td class="cf-fin">@if($line->outflows > 0) @format_accounting_amount($line->outflows, false) @else — @endif</td>
                                <td class="cf-fin {{ $line->amount < 0 ? 'cf-fin-negative' : '' }}">
                                    @format_accounting_amount($line->amount)
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="cf-summary-row">
                        <td>@lang('accounting::lang.cf_net_change_cash')</td>
                        <td class="cf-fin">@format_accounting_amount($cashInflows ?? 0, false)</td>
                        <td class="cf-fin">@format_accounting_amount($cashOutflows ?? 0, false)</td>
                        <td class="cf-fin {{ ($netCashFlow ?? 0) < 0 ? 'cf-fin-negative' : '' }}">@format_accounting_amount($netCashFlow ?? 0)</td>
                    </tr>
                    <tr class="cf-summary-row">
                        <td>@lang('accounting::lang.cf_opening_cash')</td>
                        <td colspan="2"></td>
                        <td class="cf-fin">@format_accounting_amount($openingCash ?? 0)</td>
                    </tr>
                    <tr class="cf-summary-row">
                        <td>@lang('accounting::lang.cf_closing_cash')</td>
                        <td colspan="2"></td>
                        <td class="cf-fin fw-bold">@format_accounting_amount($closingCash ?? 0)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="cf-panel">
        <div class="cf-panel-header"><h3 class="cf-panel-title">@lang('accounting::lang.cf_detail_movements')</h3></div>
        <div class="cf-detail-scroll table-responsive p-3">
            <table class="table table-sm table-hover mb-0" id="cfDetailTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('accounting::lang.activity_section')</th>
                        <th>@lang('accounting::lang.date')</th>
                        <th>@lang('accounting::lang.transaction_number')</th>
                        <th>@lang('accounting::lang.transaction_type')</th>
                        <th>@lang('accounting::lang.cost_center')</th>
                        <th class="text-end">@lang('accounting::lang.amount')</th>
                        <th class="text-center no-print">@lang('messages.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detailPaginator as $key => $row)
                        <tr>
                            <td>{{ $detailPaginator->firstItem() + $key }}</td>
                            <td>{{ $row['section'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($row['operation_date'])->format('Y-m-d') }}</td>
                            <td>{{ $row['ref_no'] }}</td>
                            <td>{{ $row['transaction_type'] }}</td>
                            <td>{{ $row['cost_center'] }}</td>
                            <td class="text-end cf-fin {{ !empty($row['is_outflow']) ? 'cf-fin-negative' : '' }}">
                                @format_accounting_amount($row['is_outflow'] ? -$row['amount'] : $row['amount'])
                            </td>
                            <td class="text-center no-print">
                                @if(!empty($row['detail_url']))
                                    <a href="{{ $row['detail_url'] }}" class="btn btn-xs btn-light-primary btn-sm py-1 px-2">@lang('accounting::lang.voucher_show')</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">@lang('messages.no_data_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="d-flex justify-content-center mt-3 no-print">
                {{ $detailPaginator->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <div class="cf-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.cf_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const cashFlowExportPdfUrl = @json(route('cash-flow-export-pdf'));
        const cashFlowExportExcelUrl = @json(route('cash-flow-export-excel'));
        const currencyLabel = @json(\App\Helpers\CurrencyHelper::get_format_currency());
        const chartLabels = @json($chart['labels'] ?? []);
        const chartSeries = @json($chart['series'] ?? []);
        const chartColors = @json($chart['colors'] ?? []);
        const trendLabels = @json($monthlyTrend['labels'] ?? []);
        const trendIn = @json($monthlyTrend['inflows'] ?? []);
        const trendOut = @json($monthlyTrend['outflows'] ?? []);
        const trendNet = @json($monthlyTrend['net'] ?? []);
        const barLabels = @json($barChart['labels'] ?? []);
        const barIn = @json($barChart['inflows'] ?? []);
        const barOut = @json($barChart['outflows'] ?? []);

        function formatChartAmount(value) {
            const n = Math.round((Number(value) + Number.EPSILON) * 100) / 100;
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function buildCashFlowQuery() {
            const form = document.getElementById('cashFlowFilters');
            const params = new URLSearchParams(new FormData(form));
            return params.toString();
        }

        function initCfCharts() {
            if (chartSeries.length && document.querySelector('#cfSectionChart')) {
                new ApexCharts(document.querySelector('#cfSectionChart'), {
                    series: chartSeries,
                    labels: chartLabels,
                    chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
                    colors: chartColors,
                    legend: { position: 'bottom' },
                    dataLabels: { formatter: (v) => v.toFixed(1) + '%' },
                    tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '62%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: @json(__('accounting::lang.total')),
                                        formatter: (w) => formatChartAmount((w.globals.seriesTotals || []).reduce((s, v) => s + Number(v), 0)),
                                    },
                                },
                            },
                        },
                    },
                }).render();
            }

            if (trendLabels.length && document.querySelector('#cfTrendChart')) {
                new ApexCharts(document.querySelector('#cfTrendChart'), {
                    series: [
                        { name: @json(__('accounting::lang.cash_inflows')), data: trendIn },
                        { name: @json(__('accounting::lang.cash_outflows')), data: trendOut },
                        { name: @json(__('accounting::lang.net_cash_flows')), data: trendNet },
                    ],
                    chart: { type: 'line', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                    colors: ['#17C653', '#F8285A', '#1B84FF'],
                    stroke: { curve: 'smooth', width: 2 },
                    xaxis: { categories: trendLabels },
                    yaxis: { labels: { formatter: (v) => formatChartAmount(v) } },
                    tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                }).render();
            }

            if (barLabels.length && document.querySelector('#cfBarChart')) {
                new ApexCharts(document.querySelector('#cfBarChart'), {
                    series: [
                        { name: @json(__('accounting::lang.cash_inflows')), data: barIn },
                        { name: @json(__('accounting::lang.cash_outflows')), data: barOut },
                    ],
                    chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                    colors: ['#17C653', '#F8285A'],
                    plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
                    xaxis: { categories: barLabels },
                    yaxis: { labels: { formatter: (v) => formatChartAmount(v) } },
                    tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                }).render();
            }
        }

        $(document).ready(function() {
            $('#choose_cost_center_select, #sub_types, #movement_type, #activity_section').select2({ width: '100%' });
            initCfCharts();

            $(document).on('click', '.cf-section-row', function() {
                const key = $(this).data('cf-section');
                const rows = $('.cf-section-' + key).not('.cf-subtotal');
                const icon = $(this).find('.cf-section-icon');
                const hidden = rows.first().is(':hidden');
                rows.toggle(hidden);
                $('.cf-section-' + key + '.cf-subtotal').show();
                icon.toggleClass('fa-chevron-down', hidden).toggleClass('fa-chevron-right', !hidden);
            });

            $('#cfExpandAll').on('click', function() {
                $('.cf-line-row').show();
                $('.cf-section-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            });
            $('#cfCollapseAll').on('click', function() {
                $('.cf-line-row').not('.cf-subtotal').hide();
                $('.cf-section-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            });

            $('#cashFlowExportPdf').on('click', () => window.open(cashFlowExportPdfUrl + '?' + buildCashFlowQuery(), '_blank'));
            $('#cashFlowExportExcel').on('click', () => { window.location.href = cashFlowExportExcelUrl + '?' + buildCashFlowQuery(); });
        });
    </script>
@stop
