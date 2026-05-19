@extends('layouts.app')
@section('title', __('accounting::lang.expense_report'))

@section('css')
    @include('accounting::reports.partials.expense-report-styles')
@stop

@section('content')
@php
    $summary = $summary ?? ['count' => 0, 'net' => 0, 'tax' => 0, 'gross' => 0];
    $analytics = $analytics ?? [];
    $chart = $chart ?? ['labels' => [], 'series' => [], 'colors' => []];
    $monthlyTrend = $monthlyTrend ?? ['labels' => [], 'gross' => []];
    $highThreshold = (float) ($analytics['high_amount_threshold'] ?? 0);
    $localeAr = app()->getLocale() === 'ar';
@endphp

<div class="container-fluid expense-report-wrap" id="expense-report-root">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="er-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="text-muted small mb-1">{{ $company->name ?? '' }}</div>
                <h1 class="er-title">@lang('accounting::lang.expense_report')</h1>
                <p class="text-muted small mb-1">@lang('accounting::lang.expense_report_intro')</p>
                <p class="text-muted small mb-0">
                    @lang('accounting::lang.from_date'): {{ $startDate }}
                    <span class="mx-1">—</span>
                    @lang('accounting::lang.to_date'): {{ $endDate }}
                </p>
                @if(!empty($comparePeriod))
                    <p class="text-muted small mb-0 mt-1">
                        {{ __('accounting::lang.expense_report_previous_period') }}:
                        {{ $comparePeriod['start_date'] }} — {{ $comparePeriod['end_date'] }}
                    </p>
                @endif
            </div>
            <div class="er-toolbar flex-shrink-0">
                <button type="button" class="btn btn-sm btn-light-primary" id="erExpandAll">@lang('accounting::lang.expense_report_expand_all')</button>
                <button type="button" class="btn btn-sm btn-light" id="erCollapseAll">@lang('accounting::lang.expense_report_collapse_all')</button>
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
            </div>
        </div>
    </div>

    <div class="er-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.expense_report')</h4>
        <p class="small text-muted mb-0">
            @lang('accounting::lang.from_date'): {{ $startDate }}
            — @lang('accounting::lang.to_date'): {{ $endDate }}
        </p>
    </div>

    <div class="er-filters-card no-print">
        <form method="GET" action="{{ route('expense-report') }}" id="expenseReportFilters">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label small">@lang('accounting::lang.from_date')</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label small">@lang('accounting::lang.to_date')</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label small">@lang('accounting::lang.income_statement_growth')</label>
                        <select name="compare_mode" class="form-select form-select-sm">
                            <option value="none" @selected(($compareMode ?? 'none') === 'none')>@lang('accounting::lang.expense_report_compare_none')</option>
                            <option value="previous_period" @selected(($compareMode ?? '') === 'previous_period')>@lang('accounting::lang.expense_report_compare_previous')</option>
                            <option value="previous_year" @selected(($compareMode ?? '') === 'previous_year')>@lang('accounting::lang.expense_report_compare_year')</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small">@lang('accounting::lang.expense_report_category_filter')</label>
                        <select name="expense_categories[]" id="expense_report_categories" class="form-select form-select-sm" multiple>
                            @foreach ($expenseCategories ?? [] as $catKey)
                                <option value="{{ $catKey }}" @selected(in_array($catKey, $selectedCategories ?? [], true))>
                                    @lang('accounting::lang.expense_cat_'.$catKey)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small">@lang('expense::fields.cost_center')</label>
                        <select name="cost_center_ids[]" id="expense_report_cost_centers" class="form-select form-select-sm" multiple>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" @selected(in_array($cc->id, $costCenterIds ?? [], true))>
                                    {{ $localeAr ? $cc->name_ar : $cc->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small">@lang('expense::fields.debit_account')</label>
                        <select name="debit_account_ids[]" id="expense_report_debit_accounts" class="form-select form-select-sm" multiple>
                            @foreach ($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(in_array($acc->id, $debitAccountIds ?? [], true))>
                                    {{ ($localeAr ? $acc->name_ar : $acc->name_en) }} ({{ $acc->gl_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label small">@lang('expense::fields.credit_account')</label>
                        <select name="credit_account_ids[]" id="expense_report_treasury" class="form-select form-select-sm" multiple>
                            @foreach ($treasuryAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(in_array($acc->id, $creditAccountIds ?? [], true))>
                                    {{ ($localeAr ? $acc->name_ar : $acc->name_en) }} ({{ $acc->gl_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label small">@lang('accounting::lang.expense_report_created_by')</label>
                        <select name="created_by_ids[]" id="expense_report_users" class="form-select form-select-sm" multiple>
                            @foreach ($expenseCreators ?? [] as $emp)
                                <option value="{{ $emp->id }}" @selected(in_array($emp->id, $createdByIds ?? [], true))>
                                    {{ $localeAr ? ($emp->name ?? $emp->user_name) : ($emp->name_en ?? $emp->name ?? $emp->user_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label small">@lang('accounting::lang.expense_report_tax_filter')</label>
                        <select name="tax_id" class="form-select form-select-sm">
                            <option value="all" @selected(($taxId ?? 'all') === 'all')>@lang('accounting::lang.expense_report_tax_all')</option>
                            <option value="none" @selected(($taxId ?? '') === 'none')>@lang('accounting::lang.expense_report_tax_none')</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" @selected((string) ($taxId ?? '') === (string) $tax->id)>{{ $tax->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label small">@lang('accounting::lang.search')</label>
                        <input type="text" name="q" class="form-control form-control-sm" value="{{ $keyword ?? '' }}"
                            placeholder="{{ __('accounting::lang.expense_report_search_placeholder') }}">
                    </div>
                    <div class="col-md-6 col-lg-4 d-flex align-items-end">
                        <label class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="with_attachments" value="1" @checked($withAttachments ?? false)>
                            <span class="form-check-label small">@lang('accounting::lang.expense_report_with_attachments')</span>
                        </label>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">@lang('accounting::lang.search')</button>
                        <a href="{{ route('expense-report') }}" class="btn btn-light btn-sm">@lang('accounting::lang.clear_filters')</a>
                        <button type="button" id="expenseExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                        <button type="button" id="expenseExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
                    </div>
                </div>
        </form>
    </div>

    <div class="row g-3 mb-4 no-print">
        @php
            $kpis = [
                ['label' => __('accounting::lang.expense_report_total_gross'), 'value' => $summary['gross'], 'money' => true],
                [
                    'label' => __('accounting::lang.expense_report_top_category'),
                    'value' => $analytics['top_category'] ?? '—',
                    'sub' => isset($analytics['top_category_amount'])
                        ? \App\Helpers\CurrencyHelper::format_accounting_amount($analytics['top_category_amount'])
                        : null,
                ],
                ['label' => __('accounting::lang.expense_report_operating_share'), 'value' => ($analytics['operating_percent'] ?? 0).'%'],
                ['label' => __('accounting::lang.expense_report_avg_monthly'), 'value' => $analytics['avg_monthly'] ?? 0, 'money' => true],
                ['label' => __('accounting::lang.expense_report_tax'), 'value' => $summary['tax'], 'money' => true],
                ['label' => __('accounting::lang.expense_report_growth'), 'value' => $analytics['growth_percent'] ?? null, 'growth' => true],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="er-kpi">
                    <div class="er-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="er-kpi-value">
                        @if(!empty($kpi['money']))
                            @format_accounting_amount($kpi['value'])
                        @elseif(!empty($kpi['growth']))
                            @if($kpi['value'] !== null)
                                <span class="er-kpi-growth {{ $kpi['value'] >= 0 ? 'down' : 'up' }}">
                                    {{ $kpi['value'] > 0 ? '+' : '' }}{{ $kpi['value'] }}%
                                </span>
                            @else
                                —
                            @endif
                        @else
                            {{ $kpi['value'] }}
                        @endif
                    </div>
                    @if(!empty($kpi['sub']))
                        <div class="text-muted small mt-1">{{ $kpi['sub'] }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-lg-5">
            <div class="er-panel h-100">
                <div class="er-panel-header">
                    <h3 class="er-panel-title">@lang('accounting::lang.expense_report_by_classification')</h3>
                </div>
                <div class="er-chart-body">
                    <div id="expenseCategoryChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="er-panel h-100">
                <div class="er-panel-header">
                    <h3 class="er-panel-title">@lang('accounting::lang.expense_report_monthly_trend')</h3>
                </div>
                <div class="er-chart-body">
                    <div id="expenseTrendChart"></div>
                </div>
            </div>
        </div>
    </div>

    @if (($byCategory ?? collect())->isNotEmpty())
        <div class="er-panel mb-4">
            <div class="er-panel-header">
                <h3 class="er-panel-title">@lang('accounting::lang.expense_report_by_classification')</h3>
            </div>
            <div class="p-3 pt-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle er-classification-table mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>@lang('accounting::lang.expense_report_classification')</th>
                                <th class="text-end">@lang('accounting::lang.expense_report_count')</th>
                                <th class="text-end">@lang('expense::fields.gross_amount')</th>
                                <th class="text-end">@lang('accounting::lang.expense_report_share')</th>
                                @if(!empty($compareSummary))
                                    <th class="text-end">@lang('accounting::lang.expense_report_previous_period')</th>
                                    <th class="text-end">@lang('accounting::lang.expense_report_growth')</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byCategory as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row->category_label }}</td>
                                    <td class="text-end">{{ number_format($row->expense_count) }}</td>
                                    <td class="text-end">@format_accounting_amount($row->gross_total)</td>
                                    <td class="text-end">{{ number_format($row->share_percent, 1) }}%</td>
                                    @if(!empty($compareSummary))
                                        <td class="text-end text-muted">@format_accounting_amount($row->compare_gross ?? 0)</td>
                                        <td class="text-end">
                                            @if($row->growth_percent !== null)
                                                <span class="{{ ($row->growth_percent ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ ($row->growth_percent ?? 0) > 0 ? '+' : '' }}{{ $row->growth_percent }}%
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold border-top">
                                <td>@lang('accounting::lang.total')</td>
                                <td class="text-end">{{ number_format($summary['count']) }}</td>
                                <td class="text-end">@format_accounting_amount($summary['gross'])</td>
                                <td class="text-end">100%</td>
                                @if(!empty($compareSummary))
                                    <td class="text-end">@format_accounting_amount($compareSummary['gross'] ?? 0)</td>
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="er-panel">
        <div class="er-panel-header">
            <h3 class="er-panel-title">@lang('accounting::lang.expense_report_details')</h3>
        </div>
        <div class="p-0">
            @if (($byCategory ?? collect())->isNotEmpty())
                <div class="er-table-scroll">
                    <table class="table table-sm table-hover mb-0" id="expenseReportTable">
                        <thead>
                            <tr>
                                <th>@lang('accounting::lang.expense_report_item')</th>
                                <th>@lang('accounting::lang.expense_report_classification')</th>
                                <th>@lang('accounting::lang.expense_report_branch')</th>
                                <th>@lang('expense::fields.cost_center')</th>
                                <th class="text-end">@lang('expense::fields.gross_amount')</th>
                                <th class="text-end">@lang('accounting::lang.expense_report_share')</th>
                                <th>@lang('expense::fields.expense_date')</th>
                                <th class="text-center no-print">@lang('employee::fields.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($byCategory as $cat)
                                <tr class="er-cat-row" data-er-category="{{ $cat->category_key }}">
                                    <td colspan="4">
                                        <i class="fa fa-chevron-down fa-xs me-2 er-cat-icon"></i>
                                        {{ $cat->category_label }}
                                        <span class="opacity-75 ms-2">({{ $cat->expense_count }})</span>
                                    </td>
                                    <td class="text-end is-fin-amount">@format_accounting_amount($cat->gross_total)</td>
                                    <td class="text-end text-muted">{{ number_format($cat->share_percent, 1) }}%</td>
                                    <td></td>
                                    <td class="no-print"></td>
                                </tr>
                                @foreach ($cat->expenses as $expense)
                                    @php
                                        $debit = $expense->debitAccount;
                                        $debitNm = $debit ? ($localeAr ? $debit->name_ar : $debit->name_en) : '—';
                                        $cc = $expense->costCenter;
                                        $ccNm = $cc ? ($localeAr ? $cc->name_ar : $cc->name_en) : '—';
                                        $isHigh = $highThreshold > 0 && (float) $expense->total >= $highThreshold;
                                        $detailUrl = $expense->detail_url ?? null;
                                    @endphp
                                    <tr class="er-detail-row er-cat-{{ $cat->category_key }} {{ $isHigh ? 'er-high' : '' }}">
                                        <td>
                                            <div class="fw-semibold ps-4">{{ \Illuminate\Support\Str::limit($expense->description, 70) }}</div>
                                            <div class="text-muted small ps-4">{{ $debitNm }} @if($debit)({{ $debit->gl_code }})@endif</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $expense->category_label }}</span>
                                            @if(!empty($expense->source_label))
                                                <span class="badge badge-light-secondary ms-1">{{ $expense->source_label }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">@lang('accounting::lang.expense_report_branch_na')</td>
                                        <td class="small">{{ $ccNm }}</td>
                                        <td class="text-end is-fin-amount">@format_accounting_amount($expense->total)</td>
                                        <td class="text-end text-muted small">{{ number_format($expense->share_percent, 2) }}%</td>
                                        <td>{{ $expense->date->format('Y-m-d') }}</td>
                                        <td class="text-center no-print">
                                            @if($detailUrl)
                                                <a href="{{ $detailUrl }}" class="btn btn-xs btn-light-primary btn-sm py-1 px-2">
                                                    @lang('accounting::lang.voucher_show')
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 text-center">
                    <h4 class="fw-semibold">@lang('accounting::lang.no_data')</h4>
                    <p class="text-muted">@lang('accounting::lang.expense_report_empty_hint')</p>
                </div>
            @endif
        </div>
    </div>

    <div class="er-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.expense_report_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const expenseExportPdfUrl = @json(route('expense-report-export-pdf'));
        const expenseExportExcelUrl = @json(route('expense-report-export-excel'));
        const currencyLabel = @json(\App\Helpers\CurrencyHelper::get_format_currency());
        const chartLabels = @json($chart['labels'] ?? []);
        const chartSeries = @json($chart['series'] ?? []);
        const chartColors = @json($chart['colors'] ?? []);
        const trendLabels = @json($monthlyTrend['labels'] ?? []);
        const trendGross = @json($monthlyTrend['gross'] ?? []);

        function formatChartAmount(value) {
            const n = Math.round((Number(value) + Number.EPSILON) * 100) / 100;
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatChartAmountWithCurrency(value) {
            return formatChartAmount(value) + ' ' + currencyLabel;
        }

        function donutSeriesTotal(w) {
            return (w.globals.seriesTotals || []).reduce((sum, v) => sum + Number(v), 0);
        }

        function buildExpenseReportQuery() {
            const form = document.getElementById('expenseReportFilters');
            const params = new URLSearchParams(new FormData(form));
            if (!form.querySelector('[name=with_attachments]').checked) {
                params.delete('with_attachments');
            }
            return params.toString();
        }

        function initExpenseCharts() {
            if (chartSeries.length && document.querySelector('#expenseCategoryChart')) {
                new ApexCharts(document.querySelector('#expenseCategoryChart'), {
                    series: chartSeries,
                    labels: chartLabels,
                    chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                    colors: chartColors,
                    legend: { position: 'bottom', fontSize: '12px' },
                    dataLabels: { enabled: true, formatter: (v) => v.toFixed(1) + '%' },
                    tooltip: {
                        y: { formatter: (val) => formatChartAmountWithCurrency(val) }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '62%',
                                labels: {
                                    show: true,
                                    value: {
                                        formatter: (val) => formatChartAmount(val),
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: @json(__('accounting::lang.total')),
                                        formatter: (w) => formatChartAmount(donutSeriesTotal(w)),
                                        fontSize: '15px',
                                        fontWeight: 600,
                                    },
                                },
                            },
                        },
                    },
                }).render();
            }

            if (trendGross.length && document.querySelector('#expenseTrendChart')) {
                new ApexCharts(document.querySelector('#expenseTrendChart'), {
                    series: [{ name: @json(__('accounting::lang.expense_report_gross')), data: trendGross }],
                    chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
                    colors: chartColors.length ? [chartColors[0]] : ['#1B84FF'],
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                    xaxis: { categories: trendLabels },
                    yaxis: {
                        labels: { formatter: (v) => Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }) }
                    },
                    tooltip: {
                        y: { formatter: (v) => formatChartAmountWithCurrency(v) }
                    }
                }).render();
            }
        }

        $(document).ready(function() {
            const dir = document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr';
            $('#expense_report_debit_accounts, #expense_report_cost_centers, #expense_report_categories, #expense_report_users').select2({
                width: '100%', dir, placeholder: @json(__('messages.select')), allowClear: true, closeOnSelect: false
            });
            $('#expense_report_treasury').select2({
                width: '100%', dir, placeholder: @json(__('expense::lang.filter_treasury_placeholder')),
                allowClear: true, closeOnSelect: false, minimumResultsForSearch: 0
            });

            $('#expenseExportPdf').on('click', () => window.open(expenseExportPdfUrl + '?' + buildExpenseReportQuery(), '_blank'));
            $('#expenseExportExcel').on('click', () => { window.location.href = expenseExportExcelUrl + '?' + buildExpenseReportQuery(); });

            initExpenseCharts();

            $(document).on('click', '.er-cat-row', function() {
                const key = $(this).data('er-category');
                const rows = $('.er-cat-' + key);
                const icon = $(this).find('.er-cat-icon');
                const hidden = rows.first().is(':hidden');
                rows.toggle(hidden);
                icon.toggleClass('fa-chevron-down', hidden).toggleClass('fa-chevron-right', !hidden);
            });

            $('#erCollapseAll').on('click', function() {
                $('.er-detail-row').hide();
                $('.er-cat-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            });

            $('#erExpandAll').on('click', function() {
                $('.er-detail-row').show();
                $('.er-cat-icon').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            });
        });
    </script>
@endsection
