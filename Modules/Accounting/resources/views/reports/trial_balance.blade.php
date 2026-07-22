@extends('layouts.app')
@section('title', __('accounting::lang.trial_balance'))

@section('css')
    @include('accounting::reports.partials.trial-balance-styles')
@stop

@section('content')
@php
    $defaultStart = $start_date ?? now()->startOfYear()->format('Y-m-d');
    $defaultEnd = $end_date ?? now()->format('Y-m-d');
@endphp

<div class="container-fluid trial-balance-wrap" id="trial-balance-report">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="tb-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1 min-w-0">
                <div class="text-muted small mb-1">{{ $company->name ?? '' }}</div>
                <h1 class="tb-title">@lang('accounting::lang.trial_balance')</h1>
                <p class="text-muted small mb-0">@lang('accounting::lang.tb_report_intro')</p>
            </div>
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <button type="button" class="btn btn-sm btn-light-primary" id="tbExpandAll">@lang('accounting::lang.tb_expand_all')</button>
                <button type="button" class="btn btn-sm btn-light-primary" id="tbCollapseAll">@lang('accounting::lang.tb_collapse_all')</button>
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
            </div>
        </div>
    </div>

    <div class="tb-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.trial_balance')</h4>
        <p class="small text-muted mb-0" id="tbPrintPeriod"></p>
    </div>

    <div class="tb-filters-card no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="start_date_filter">@lang('accounting::lang.from_date')</label>
                <input type="date" id="start_date_filter" class="form-control form-control-sm" value="{{ $defaultStart }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="end_date_filter">@lang('accounting::lang.to_date')</label>
                <input type="date" id="end_date_filter" class="form-control form-control-sm" value="{{ $defaultEnd }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="level_filter">@lang('accounting::lang.account_level')</label>
                <select id="level_filter" class="form-select form-select-sm">
                    @foreach ($levelsArray as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="with_zero_balances">@lang('accounting::lang.balance')</label>
                <select id="with_zero_balances" class="form-select form-select-sm">
                    <option value="0" selected>@lang('accounting::lang.without_zero_balances')</option>
                    <option value="1">@lang('accounting::lang.with_zero_balances')</option>
                    <option value="2">@lang('accounting::lang.zero_balances')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small" for="compare_mode">@lang('accounting::lang.tb_compare')</label>
                <select id="compare_mode" class="form-select form-select-sm">
                    <option value="none">@lang('accounting::lang.tb_compare_none')</option>
                    <option value="previous_period">@lang('accounting::lang.tb_compare_previous')</option>
                    <option value="previous_year">@lang('accounting::lang.tb_compare_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label small" for="choose_cost_center_select">@lang('accounting::lang.cost_center')</label>
                <select id="choose_cost_center_select" class="form-select form-select-sm" multiple>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}">
                            {{ app()->getLocale() == 'ar'
                                ? $costCenter->account_center_number.' - '.$costCenter->name_ar
                                : $costCenter->account_center_number.' - '.$costCenter->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 col-lg-8">
                <label class="form-label small" for="choose_accounts_select">@lang('accounting::lang.account')</label>
                <select id="choose_accounts_select" class="form-select form-select-sm" multiple>
                    @foreach ($accounts_array as $key => $value)
                        <option value="{{ $key }}" selected>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="tbApplyFilters">@lang('report::general.filter')</button>
                <button type="button" id="trialBalanceExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                <button type="button" id="trialBalanceExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
            </div>
        </div>
    </div>

    <div id="tbPlOpeningWarning" class="alert alert-warning py-2 mb-3 no-print d-none" role="alert">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span id="tbPlOpeningWarningText"></span>
            <a id="tbPlOpeningWarningLink" class="btn btn-sm btn-warning d-none" href="#" target="_blank" rel="noopener">
                @lang('accounting::lang.tb_pl_opening_fix_link')
            </a>
        </div>
    </div>

    <div id="trial-balance-status" class="alert alert-secondary py-2 mb-3 no-print">
        <strong id="trial-balance-status-label">—</strong>
        <span class="mx-2">|</span>
        <span id="trial-balance-diff-label">—</span>
        <span class="mx-2" id="tbCompareWrap" style="display:none;">|</span>
        <span id="trial-balance-compare-label" style="display:none;"></span>
    </div>

    <div class="row g-3 mb-4 no-print" id="tbKpiRow">
        @php
            $kpis = [
                ['key' => 'total_debit_period', 'label' => __('accounting::lang.tb_kpi_debit_period')],
                ['key' => 'total_credit_period', 'label' => __('accounting::lang.tb_kpi_credit_period')],
                ['key' => 'difference', 'label' => __('accounting::lang.difference'), 'danger' => true],
                ['key' => 'account_count', 'label' => __('accounting::lang.tb_kpi_accounts'), 'int' => true],
                ['key' => 'active_accounts', 'label' => __('accounting::lang.tb_kpi_active')],
                ['key' => 'inactive_accounts', 'label' => __('accounting::lang.tb_kpi_inactive')],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="tb-kpi">
                    <div class="tb-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="tb-kpi-value {{ !empty($kpi['danger']) ? 'text-danger' : '' }}" data-kpi="{{ $kpi['key'] }}">—</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4 no-print">
        <div class="col-lg-5">
            <div class="tb-panel h-100">
                <div class="tb-panel-header">
                    <h3 class="tb-panel-title">@lang('accounting::lang.tb_chart_by_type')</h3>
                </div>
                <div class="tb-chart-body">
                    <div id="trialBalanceTypeChart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="tb-panel h-100">
                <div class="tb-panel-header">
                    <h3 class="tb-panel-title">@lang('accounting::lang.tb_top_movement')</h3>
                </div>
                <div class="p-3">
                    <ul class="tb-top-list" id="tbTopMovementList">
                        <li class="text-muted">@lang('accounting::lang.tb_load_data_hint')</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="tb-table-card">
        <div class="tb-table-scroll">
            <table class="table table-sm table-hover align-middle mb-0 w-100" id="kt_accounts_table">
                <thead>
                    <tr class="tb-thead-group">
                        <th colspan="2"></th>
                        <th colspan="2">@lang('accounting::lang.opening_balance')</th>
                        <th colspan="4">@lang('accounting::lang.accounting_transactions')</th>
                        <th colspan="3">@lang('accounting::lang.closing_balance')</th>
                        <th></th>
                    </tr>
                    <tr id="accounts_headerRow">
                        <th class="text-start">@lang('accounting::lang.number')</th>
                        <th class="text-start">@lang('accounting::lang.name')</th>
                        <th class="tb-fin">@lang('accounting::lang.debit')</th>
                        <th class="tb-fin">@lang('accounting::lang.credit')</th>
                        <th class="tb-fin">@lang('accounting::lang.debit')</th>
                        <th class="tb-fin">@lang('accounting::lang.credit')</th>
                        <th class="tb-fin">@lang('accounting::lang.tb_period_net')</th>
                        <th class="text-center">@lang('accounting::lang.tb_period_balance_type')</th>
                        <th class="text-center">@lang('accounting::lang.tb_balance_type')</th>
                        <th class="tb-fin">@lang('accounting::lang.debit')</th>
                        <th class="tb-fin">@lang('accounting::lang.credit')</th>
                        <th class="text-center">@lang('messages.actions')</th>
                    </tr>
                </thead>
                <tbody id="accounts_tableBody"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">@lang('accounting::lang.total'):</th>
                        <th id="debitOpeningTotal" class="debit_opening_total tb-fin"></th>
                        <th id="creditOpeningTotal" class="credit_opening_total tb-fin"></th>
                        <th id="debitTotal" class="debit_total tb-fin"></th>
                        <th id="creditTotal" class="credit_total tb-fin"></th>
                        <th id="periodNetTotal" class="period_net_total tb-fin"></th>
                        <th colspan="2"></th>
                        <th id="closingDebitTotal" class="closing_debit_total tb-fin"></th>
                        <th id="closingCreditTotal" class="closing_credit_total tb-fin"></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="modal fade" id="printledger" tabindex="-1" role="dialog"></div>
    </div>

    <div class="tb-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.tb_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        "use strict";

        const table = $('#kt_accounts_table');
        const dataUrl = @json(route('trial-balance'));
        const exportPdfUrl = @json(route('trial-balance-export-pdf'));
        const exportExcelUrl = @json(route('trial-balance-export-excel'));
        const currencyLabel = @json(\App\Helpers\CurrencyHelper::get_format_currency());
        const tbBalancedLabel = @json(__('accounting::lang.balanced'));
        const tbUnbalancedLabel = @json(__('accounting::lang.unbalanced'));
        const tbCompareGrowthLabel = @json(__('accounting::lang.tb_compare_growth'));
        const tbInitialStartDate = @json($defaultStart);
        const tbInitialEndDate = @json($defaultEnd);

        let dataTable;
        let typeChart;
        const collapsedGroups = new Set();

        const FIN_COLS = [2, 3, 4, 5, 6, 9, 10];

        function formatChartAmount(value) {
            const n = Math.round((Number(value) + Number.EPSILON) * 100) / 100;
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatTbAmount(value) {
            const n = Math.round((Number(value) + Number.EPSILON) * 100) / 100;
            if (n < -0.0001) {
                return '(' + Math.abs(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')';
            }
            return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatTbAmountHtml(value) {
            const n = Number(value);
            const cls = n < -0.0001 ? ' tb-fin-negative' : '';
            return '<span class="tb-fin' + cls + '">' + formatTbAmount(n) + '</span>';
        }

        function renderBalanceStatus(isBalanced, diff) {
            const box = $('#trial-balance-status');
            box.removeClass('alert-success alert-warning alert-secondary');
            box.addClass(isBalanced ? 'alert-success' : 'alert-warning');
            $('#trial-balance-status-label').text(
                @json(__('accounting::lang.balance')) + ': ' + (isBalanced ? tbBalancedLabel : tbUnbalancedLabel)
            );
            $('#trial-balance-diff-label').text(
                @json(__('accounting::lang.difference')) + ': ' + formatTbAmount(diff) + ' ' + currencyLabel
            );
            $('#closingDebitTotal, #closingCreditTotal').toggleClass('tb-cell-unbalanced', !isBalanced);
        }

        function renderPlOpeningWarning(warning) {
            const box = $('#tbPlOpeningWarning');
            const link = $('#tbPlOpeningWarningLink');
            if (!warning || !warning.show_warning) {
                box.addClass('d-none');
                link.addClass('d-none');
                return;
            }
            $('#tbPlOpeningWarningText').text(warning.message || '');
            box.removeClass('d-none');
            if (warning.close_url) {
                link.attr('href', warning.close_url).removeClass('d-none');
            } else {
                link.addClass('d-none');
            }
        }

        function updateKpis(kpis, compareKpis) {
            if (!kpis) return;
            document.querySelectorAll('[data-kpi]').forEach(function(el) {
                const key = el.getAttribute('data-kpi');
                let val = kpis[key];
                if (val === undefined || val === null) {
                    el.textContent = '—';
                    return;
                }
                if (key === 'account_count' || key === 'active_accounts' || key === 'inactive_accounts') {
                    el.textContent = Number(val).toLocaleString();
                } else {
                    el.textContent = formatTbAmount(val) + ' ' + currencyLabel;
                }
            });
            if (compareKpis && compareKpis.difference !== undefined) {
                const growth = compareKpis.difference > 0.0001
                    ? ((kpis.difference - compareKpis.difference) / compareKpis.difference * 100)
                    : null;
                $('#tbCompareWrap, #trial-balance-compare-label').show();
                $('#trial-balance-compare-label').text(
                    tbCompareGrowthLabel + ': ' + (growth !== null ? growth.toFixed(1) + '%' : '—')
                );
            } else {
                $('#tbCompareWrap, #trial-balance-compare-label').hide();
            }
        }

        function renderTopMovement(items) {
            const ul = $('#tbTopMovementList');
            ul.empty();
            if (!items || !items.length) {
                ul.append('<li class="text-muted">' + @json(__('accounting::lang.no_data')) + '</li>');
                return;
            }
            items.forEach(function(row) {
                ul.append(
                    '<li><span class="text-truncate">' + (row.gl_code ? row.gl_code + ' — ' : '') + (row.name || '') +
                    '</span><strong class="tb-fin">' + formatTbAmount(row.movement) + '</strong></li>'
                );
            });
        }

        function renderTypeChart(chart) {
            if (!chart || !chart.series || !chart.series.length) {
                if (typeChart) { typeChart.destroy(); typeChart = null; }
                return;
            }
            const opts = {
                series: chart.series,
                labels: chart.labels,
                chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
                colors: chart.colors || [],
                legend: { position: 'bottom', fontSize: '12px' },
                dataLabels: { enabled: true, formatter: (v) => v.toFixed(1) + '%' },
                tooltip: { y: { formatter: (v) => formatChartAmount(v) + ' ' + currencyLabel } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '62%',
                            labels: {
                                show: true,
                                value: { formatter: (v) => formatChartAmount(v) },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: @json(__('accounting::lang.total')),
                                    formatter: (w) => formatChartAmount(
                                        (w.globals.seriesTotals || []).reduce((s, v) => s + Number(v), 0)
                                    ),
                                    fontSize: '15px',
                                    fontWeight: 600,
                                },
                            },
                        },
                    },
                },
            };
            if (typeChart) {
                typeChart.updateOptions(opts);
            } else if (document.querySelector('#trialBalanceTypeChart')) {
                typeChart = new ApexCharts(document.querySelector('#trialBalanceTypeChart'), opts);
                typeChart.render();
            }
        }

        function applyAccordionVisibility() {
            table.find('tbody tr.tb-account-row').each(function() {
                const classes = (this.className || '').split(/\s+/);
                const groupClass = classes.find((c) => c.indexOf('tb-group-') === 0 && c !== 'tb-group-row');
                const key = groupClass ? groupClass.replace('tb-group-', '') : '';
                $(this).toggleClass('d-none', collapsedGroups.has(key));
            });
            table.find('.tb-accordion-toggle').each(function() {
                const key = $(this).data('group');
                const expanded = !collapsedGroups.has(String(key));
                $(this).attr('aria-expanded', expanded ? 'true' : 'false');
                $(this).find('.tb-accordion-icon').toggleClass('tb-collapsed', !expanded);
            });
        }

        function readTbStartDate() {
            return $('#start_date_filter').val() || tbInitialStartDate || '';
        }

        function readTbEndDate() {
            return $('#end_date_filter').val() || tbInitialEndDate || '';
        }

        function buildReportQuery() {
            const params = new URLSearchParams();
            const startDate = readTbStartDate();
            const endDate = readTbEndDate();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            const lvl = $('#level_filter').val();
            if (lvl !== undefined && lvl !== null && lvl !== '') params.append('level_filter', lvl);
            params.append('with_zero_balances', $('#with_zero_balances').val() ?? '0');
            params.append('compare_mode', $('#compare_mode').val() ?? 'none');
            ($('#choose_accounts_select').val() || []).forEach(v => params.append('choose_accounts_select[]', v));
            ($('#choose_cost_center_select').val() || []).forEach(v => params.append('choose_cost_center_select[]', v));
            return params.toString();
        }

        function ajaxData(d) {
            d.length = -1;
            d.start = 0;
            const startDate = readTbStartDate();
            const endDate = readTbEndDate();
            if (startDate) d.start_date = startDate;
            if (endDate) d.end_date = endDate;
            const lvl = $('#level_filter').val();
            if (lvl !== undefined && lvl !== null && lvl !== '') d.level_filter = lvl;
            d.with_zero_balances = $('#with_zero_balances').val() ?? '0';
            d.compare_mode = $('#compare_mode').val() ?? 'none';
            const accountTypes = $('#choose_accounts_select').val();
            if (accountTypes && accountTypes.length) {
                d.choose_accounts_select = accountTypes;
            }
            const costCenters = $('#choose_cost_center_select').val();
            if (costCenters && costCenters.length) {
                d.choose_cost_center_select = costCenters;
            }
        }

        function initDatatable() {
            dataTable = table.DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                searching: true,
                info: true,
                ajax: {
                    url: dataUrl,
                    data: ajaxData,
                    error: function(xhr) {
                        console.error('trial-balance ajax failed', xhr.status, xhr.responseText);
                    },
                },
                deferLoading: 0,
                columns: [
                    { data: 'gl_code', name: 'gl_code', orderable: false },
                    { data: 'name', name: 'name', orderable: false },
                    { data: 'debit_opening_balance', name: 'debit_opening_balance', searchable: false, className: 'tb-fin' },
                    { data: 'credit_opening_balance', name: 'credit_opening_balance', searchable: false, className: 'tb-fin' },
                    { data: 'debit_balance', name: 'debit_balance', searchable: false, className: 'tb-fin' },
                    { data: 'credit_balance', name: 'credit_balance', searchable: false, className: 'tb-fin' },
                    { data: 'period_net', name: 'period_net', searchable: false, orderable: false, className: 'tb-fin' },
                    { data: 'period_balance_type', name: 'period_balance_type', searchable: false, orderable: false, className: 'text-center' },
                    { data: 'balance_type', name: 'balance_type', searchable: false, orderable: false, className: 'text-center' },
                    { data: 'closing_debit_balance', name: 'closing_debit_balance', searchable: false, className: 'tb-fin' },
                    { data: 'closing_credit_balance', name: 'closing_credit_balance', searchable: false, className: 'tb-fin' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                ],
                columnDefs: [
                    { targets: FIN_COLS, render: function(data) { return formatTbAmountHtml(data); } },
                ],
                order: [],
                scrollX: true,
                createdRow: function(row, data) {
                    if (data.is_group) {
                        $(row).addClass('tb-group-row');
                        $(row).attr('data-group-key', data.group_key || '');
                    } else {
                        $(row).addClass('tb-account-row tb-group-' + String(data.group_key || 'other').replace(/[^a-z0-9_\-]/gi, ''));
                    }
                },
                footerCallback: function() {
                    const api = this.api();
                    const json = api.ajax.json() || {};

                    function sumDetail(field) {
                        const rows = (json.data || []).filter((r) => !r.is_group);
                        return rows.reduce((s, r) => s + (parseFloat(r[field]) || 0), 0);
                    }

                    $('.debit_opening_total').html(formatTbAmount(sumDetail('debit_opening_balance')));
                    $('.credit_opening_total').html(formatTbAmount(sumDetail('credit_opening_balance')));
                    $('.debit_total').html(formatTbAmount(sumDetail('debit_balance')));
                    $('.credit_total').html(formatTbAmount(sumDetail('credit_balance')));
                    $('.period_net_total').html(formatTbAmount(sumDetail('period_net')));
                    $('.closing_debit_total').html(formatTbAmount(json.totalClosingDebitBalance || 0));
                    $('.closing_credit_total').html(formatTbAmount(json.totalClosingCreditBalance || 0));

                    renderBalanceStatus(!!json.isBalanced, json.difference || 0);
                    renderPlOpeningWarning(json.plOpeningWarning);
                    updateKpis(json.analytics?.kpis, json.compareAnalytics?.kpis);
                    renderTypeChart(json.analytics?.chart);
                    renderTopMovement(json.analytics?.top_movement);
                },
                drawCallback: function() {
                    if (typeof KTMenu !== 'undefined') KTMenu.createInstances();
                    applyAccordionVisibility();
                },
            });
        }

        $(document).ready(function() {
            if (!table.length) return;

            if (!$('#start_date_filter').val() && tbInitialStartDate) {
                $('#start_date_filter').val(tbInitialStartDate);
            }
            if (!$('#end_date_filter').val() && tbInitialEndDate) {
                $('#end_date_filter').val(tbInitialEndDate);
            }

            $('#with_zero_balances, #level_filter, #choose_cost_center_select, #compare_mode').select2({ width: '100%' });
            $('#choose_accounts_select').select2({ width: '100%' });
            const allAccountTypes = $('#choose_accounts_select option').map(function() {
                return this.value;
            }).get();
            if (allAccountTypes.length) {
                $('#choose_accounts_select').val(allAccountTypes).trigger('change.select2');
            }

            initDatatable();

            function reloadTb() {
                $('#tbPrintPeriod').text(
                    @json(__('accounting::lang.from_date')) + ': ' + (readTbStartDate() || '') +
                    ' — ' + @json(__('accounting::lang.to_date')) + ': ' + (readTbEndDate() || '')
                );
                dataTable.ajax.reload();
            }

            $('#tbApplyFilters').on('click', reloadTb);
            $('#start_date_filter, #end_date_filter, #level_filter, #with_zero_balances, #compare_mode')
                .on('change', reloadTb);
            $('#choose_cost_center_select, #choose_accounts_select').on('change', reloadTb);

            table.on('click', '.tb-accordion-toggle', function(e) {
                e.preventDefault();
                const key = String($(this).data('group') || '');
                if (collapsedGroups.has(key)) {
                    collapsedGroups.delete(key);
                } else {
                    collapsedGroups.add(key);
                }
                applyAccordionVisibility();
            });

            $('#tbExpandAll').on('click', function() {
                collapsedGroups.clear();
                applyAccordionVisibility();
            });
            $('#tbCollapseAll').on('click', function() {
                table.find('.tb-accordion-toggle').each(function() {
                    collapsedGroups.add(String($(this).data('group') || ''));
                });
                applyAccordionVisibility();
            });

            $('#trialBalanceExportPdf').on('click', function() {
                window.open(exportPdfUrl + '?' + buildReportQuery(), '_blank');
            });
            $('#trialBalanceExportExcel').on('click', function() {
                window.location.href = exportExcelUrl + '?' + buildReportQuery();
            });

            setTimeout(reloadTb, 0);
        });
    </script>
@stop
