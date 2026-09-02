@extends('layouts.app')
@section('title', __('accounting::lang.income_list'))

@section('css')
    @include('accounting::reports.partials.income-statement-styles')
@stop

@section('content')
@php
    use App\Helpers\CurrencyHelper;
    $netProfit = (float) ($data['net_profit'] ?? 0);
    $profitMargin = $data['profit_margin'] ?? null;
    $taxPercent = (float) ($data['tax_percent'] ?? 0);
    $showCompare = ! empty($compareData);
@endphp

<div class="container-fluid income-statement-wrap" id="income-report">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="is-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="text-muted small">{{ $company->name ?? '' }}</div>
                <h1 class="is-title mb-1">@lang('accounting::lang.income_list')</h1>
                <p class="text-muted mb-0 small">
                    @lang('accounting::lang.income_statement_period', [
                        'from' => \Carbon\Carbon::parse($start_date)->format('Y-m-d'),
                        'to' => \Carbon\Carbon::parse($end_date)->format('Y-m-d'),
                    ])
                </p>
                @if($showCompare && $comparePeriod)
                    <p class="text-muted mb-0 small mt-1">
                        @lang('accounting::lang.income_statement_compare_period_label', [
                            'from' => $comparePeriod['start'],
                            'to' => $comparePeriod['end'],
                        ])
                    </p>
                @endif
            </div>
            <div class="is-toolbar">
                <button type="button" class="btn btn-sm btn-light-primary" id="isExpandAll">
                    @lang('accounting::lang.income_statement_expand_all')
                </button>
                <button type="button" class="btn btn-sm btn-light" id="isCollapseAll">
                    @lang('accounting::lang.income_statement_collapse_all')
                </button>
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::INCOME_STATEMENT_PRINT)
                <button type="button" class="btn btn-sm btn-success" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
                @enddashboardcan
            </div>
        </div>
    </div>

    <div class="is-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.income_list')</h4>
        <p class="small text-muted mb-0">
            @lang('accounting::lang.income_statement_period', [
                'from' => \Carbon\Carbon::parse($start_date)->format('Y-m-d'),
                'to' => \Carbon\Carbon::parse($end_date)->format('Y-m-d'),
            ])
        </p>
    </div>

    <form method="GET" class="is-filters-card mb-4 no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1">@lang('accounting::lang.from_date')</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                    value="{{ request('start_date', $start_date) }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1">@lang('accounting::lang.to_date')</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                    value="{{ request('end_date', $end_date) }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1" for="level_filter">@lang('accounting::lang.account_level')</label>
                <select name="level_filter" id="level_filter" class="form-select form-select-sm">
                    @foreach (($levelsArray ?? [null => __('all')]) as $key => $value)
                        <option value="{{ $key }}" @selected((string) request('level_filter', $level_filter ?? '') === (string) $key)>{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label small mb-1" for="choose_cost_center_select">@lang('accounting::lang.cost_center')</label>
                <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                    class="form-select form-select-sm" multiple>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}" @selected(in_array($costCenter->id, $choose_cost_center_select ?? []))>
                            {{ app()->getLocale() == 'ar'
                                ? $costCenter->account_center_number.' - '.$costCenter->name_ar
                                : $costCenter->account_center_number.' - '.$costCenter->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1" for="compare_mode">@lang('accounting::lang.income_statement_growth')</label>
                <select name="compare_mode" id="compare_mode" class="form-select form-select-sm">
                    <option value="none" @selected(($compare_mode ?? 'none') === 'none')>@lang('accounting::lang.income_statement_compare_none')</option>
                    <option value="previous_period" @selected(($compare_mode ?? '') === 'previous_period')>@lang('accounting::lang.income_statement_compare_previous_period')</option>
                    <option value="previous_year" @selected(($compare_mode ?? '') === 'previous_year')>@lang('accounting::lang.income_statement_compare_previous_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1" for="hide_zero_lines">@lang('accounting::lang.balance')</label>
                <select name="hide_zero_lines" id="hide_zero_lines" class="form-select form-select-sm">
                    <option value="1" @selected(($hide_zero_lines ?? 1) == 1)>@lang('accounting::lang.income_statement_hide_zero')</option>
                    <option value="0" @selected(($hide_zero_lines ?? 1) == 0)>@lang('accounting::lang.income_statement_show_zero')</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">@lang('report::general.filter')</button>
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::INCOME_STATEMENT_PRINT)
                <button type="button" id="incomeStatementExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                <button type="button" id="incomeStatementExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
                @enddashboardcan
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4 no-print">
        @php
            $kpis = [
                ['label' => __('accounting::lang.income_statement_gross_revenue'), 'value' => $data['gross_revenue'] ?? 0, 'growth' => $kpiGrowth['net_sales'] ?? null],
                ['label' => __('accounting::lang.income_statement_total_expenses'), 'value' => $data['total_expenses_all'] ?? 0, 'growth' => $kpiGrowth['total_expenses'] ?? null, 'invert' => true],
                ['label' => __('accounting::lang.income_statement_profit_margin'), 'value' => $profitMargin, 'isPercent' => true],
                ['label' => __('accounting::lang.net_profit'), 'value' => $netProfit, 'growth' => $kpiGrowth['net_profit'] ?? null, 'highlight' => true],
                ['label' => __('report::general.gross_profit'), 'value' => $data['gross_profit'] ?? 0, 'growth' => $kpiGrowth['gross_profit'] ?? null],
                ['label' => __('accounting::lang.income_statement_operating_profit'), 'value' => $data['operating_profit'] ?? 0, 'growth' => $kpiGrowth['operating_profit'] ?? null],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="is-kpi {{ ! empty($kpi['highlight']) ? 'border-primary' : '' }}">
                    <div class="is-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="is-kpi-value {{ ($kpi['value'] ?? 0) < 0 && empty($kpi['isPercent']) ? 'text-danger' : '' }}">
                        @if(! empty($kpi['isPercent']))
                            {{ $kpi['value'] !== null ? number_format((float) $kpi['value'], 1).'%' : '—' }}
                        @else
                            @format_accounting_amount($kpi['value'] ?? 0)
                        @endif
                    </div>
                    @if($showCompare && isset($kpi['growth']) && $kpi['growth'] !== null)
                        @php $up = ($kpi['invert'] ?? false) ? $kpi['growth'] < 0 : $kpi['growth'] > 0; @endphp
                        <div class="is-kpi-growth {{ $up ? 'up' : 'down' }}">
                            {{ $kpi['growth'] > 0 ? '+' : '' }}{{ $kpi['growth'] }}% @lang('accounting::lang.income_statement_growth')
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="is-table-card">
        <div class="is-table-scroll">
            <table class="table table-sm table-hover mb-0" id="income-statement-table">
                <thead>
                    <tr>
                        <th style="min-width: 55%">@lang('accounting::lang.account_name')</th>
                        <th class="text-end" style="min-width: 45%">@lang('employee::fields.amount') (@get_format_currency())</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- الإيرادات --}}
                    <tr class="is-section">
                        <td colspan="2">@lang('accounting::lang.income_statement_gross_revenue')</td>
                    </tr>
                    @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $grossRevenueAccounts])
                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.total').' '.__('accounting::lang.income_statement_gross_revenue'),
                        'amount' => $data['gross_revenue'] ?? 0,
                    ])

                    {{-- مردودات المبيعات --}}
                    @if(($salesReturnAccounts ?? collect())->isNotEmpty() || ($data['sales_returns'] ?? 0) > 0.0001)
                        <tr class="is-section">
                            <td colspan="2">@lang('accounting::lang.income_statement_sales_returns')</td>
                        </tr>
                        @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $salesReturnAccounts ?? collect()])
                        @include('accounting::reports.partials.income-statement-summary-row', [
                            'label' => __('accounting::lang.total').' '.__('accounting::lang.income_statement_sales_returns'),
                            'amount' => -1 * (float) ($data['sales_returns'] ?? 0),
                        ])
                    @endif

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.income_statement_net_sales'),
                        'amount' => $data['net_sales'] ?? 0,
                        'rowClass' => 'is-grand',
                    ])

                    {{-- تكلفة المبيعات --}}
                    <tr class="is-section">
                        <td colspan="2">@lang('accounting::lang.income_statement_cost_of_revenue')</td>
                    </tr>
                    @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $cogsAccounts ?? collect()])
                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.income_statement_total_cost_of_revenue'),
                        'amount' => $data['cost_of_revenue'] ?? 0,
                    ])

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('report::general.gross_profit'),
                        'amount' => $data['gross_profit'] ?? 0,
                        'rowClass' => 'is-profit-row',
                    ])

                    {{-- المصاريف التشغيلية --}}
                    <tr class="is-section">
                        <td colspan="2">@lang('accounting::lang.income_statement_operating_expenses')</td>
                    </tr>
                    @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $expenseAccounts])
                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.income_statement_total_operating_expenses'),
                        'amount' => $data['total_expense'] ?? 0,
                    ])

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.income_statement_operating_profit'),
                        'amount' => $data['operating_profit'] ?? 0,
                        'rowClass' => 'is-grand',
                    ])

                    {{-- إيرادات / مصروفات أخرى --}}
                    @if(($otherIncomeAccounts ?? collect())->isNotEmpty() || ($data['total_other_income'] ?? 0) > 0.0001)
                        <tr class="is-section">
                            <td colspan="2">@lang('accounting::lang.income_statement_other_income')</td>
                        </tr>
                        @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $otherIncomeAccounts ?? collect()])
                        @include('accounting::reports.partials.income-statement-summary-row', [
                            'label' => __('accounting::lang.income_statement_total_other_income'),
                            'amount' => $data['total_other_income'] ?? 0,
                        ])
                    @endif

                    @if(($otherExpenseAccounts ?? collect())->isNotEmpty() || ($data['total_other_expense'] ?? 0) > 0.0001)
                        <tr class="is-section">
                            <td colspan="2">@lang('accounting::lang.income_statement_other_expenses')</td>
                        </tr>
                        @include('accounting::reports.partials.income-statement-account-rows', ['accounts' => $otherExpenseAccounts ?? collect()])
                        @include('accounting::reports.partials.income-statement-summary-row', [
                            'label' => __('accounting::lang.income_statement_total_other_expenses'),
                            'amount' => $data['total_other_expense'] ?? 0,
                        ])
                    @endif

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.income_before_tax'),
                        'amount' => $data['income_before_tax'] ?? 0,
                    ])

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.tax_amount').' ('.number_format($taxPercent, 0).'%)',
                        'amount' => -1 * abs($data['tax_amount'] ?? 0),
                    ])

                    @include('accounting::reports.partials.income-statement-summary-row', [
                        'label' => __('accounting::lang.net_profit'),
                        'amount' => $netProfit,
                        'rowClass' => $netProfit >= 0 ? 'is-profit-row' : 'is-loss-row',
                    ])
                </tbody>
            </table>
        </div>
    </div>

    <p class="is-vat-note no-print">
        @lang('accounting::lang.income_statement_vat_note', ['percent' => number_format($taxPercent, 0)])
    </p>

    <div class="is-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.income_statement_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
    const incomeExportPdfUrl = '{{ route('income-statement-export-pdf') }}';
    const incomeExportExcelUrl = '{{ route('income-statement-export-excel') }}';

    function buildIncomeQuery() {
        const params = new URLSearchParams();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        const costCenters = $('#choose_cost_center_select').val() || [];
        const compareMode = $('#compare_mode').val();
        const hideZero = $('#hide_zero_lines').val();

        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (compareMode) params.append('compare_mode', compareMode);
        if (hideZero !== null) params.append('hide_zero_lines', hideZero);
        const levelFilter = $('#level_filter').val();
        if (levelFilter !== undefined && levelFilter !== null && levelFilter !== '') {
            params.append('level_filter', levelFilter);
        }
        costCenters.forEach(function(value) {
            params.append('choose_cost_center_select[]', value);
        });

        return params.toString();
    }

    function toggleAccountChildren(parentId, show) {
        document.querySelectorAll('tr[data-parent-id="' + parentId + '"]').forEach(function(row) {
            row.style.display = show ? '' : 'none';
            if (!show && row.dataset.accountId) {
                toggleAccountChildren(row.dataset.accountId, false);
            }
        });
    }

    $(document).ready(function() {
        $('#choose_cost_center_select').select2({ width: '100%' });
        $('#compare_mode, #hide_zero_lines, #level_filter').select2({ minimumResultsForSearch: Infinity, width: '100%' });

        $('#incomeStatementExportPdf').on('click', function() {
            window.open(incomeExportPdfUrl + '?' + buildIncomeQuery(), '_blank');
        });

        $('#incomeStatementExportExcel').on('click', function() {
            window.location.href = incomeExportExcelUrl + '?' + buildIncomeQuery();
        });

        $(document).on('click', '[data-toggle-account]', function() {
            const parentId = $(this).data('toggle-account');
            const icon = $(this).find('i');
            const isExpanded = icon.hasClass('fa-chevron-down');
            toggleAccountChildren(parentId, !isExpanded);
            icon.toggleClass('fa-chevron-down', !isExpanded);
            icon.toggleClass('fa-chevron-right', isExpanded);
        });

        $('#isCollapseAll').on('click', function() {
            document.querySelectorAll('.is-parent-row').forEach(function(row) {
                if (row.dataset.accountId) {
                    toggleAccountChildren(row.dataset.accountId, false);
                }
            });
            document.querySelectorAll('[data-toggle-account] i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
        });

        $('#isExpandAll').on('click', function() {
            document.querySelectorAll('tr[data-parent-id]').forEach(function(row) {
                row.style.display = '';
            });
            document.querySelectorAll('[data-toggle-account] i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
        });
    });
</script>
@stop
