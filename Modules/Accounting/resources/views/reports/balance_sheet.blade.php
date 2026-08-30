@extends('layouts.app')
@section('title', __('accounting::lang.balance_sheet'))

@section('css')
    @include('accounting::reports.partials.balance-sheet-styles')
@stop

@section('content')
@php
    $m = $metrics ?? [];
    $isBalanced = ($difference ?? 0) < 0.005;
@endphp

<div class="container-fluid balance-sheet-wrap" id="balance-sheet-report">
    @include('accounting::reports.partials.inventory_policy_notice')

    <div class="bs-report-banner no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="text-muted small">{{ $company->name ?? '' }}</div>
                <h1 class="bs-title mb-1">@lang('accounting::lang.balance_sheet')</h1>
                <p class="text-muted mb-0 small">
                    <strong>@lang('accounting::lang.bs_as_at'):</strong> {{ $end_date }}
                </p>
                <p class="text-muted mb-0 small">@lang('accounting::lang.bs_position_explanation')</p>
                @if(!empty($comparePeriod))
                    <p class="text-muted mb-0 small mt-1">
                        @lang('accounting::lang.bs_compare_period_label', [
                            'from' => $comparePeriod['start'],
                            'to' => $comparePeriod['end'],
                        ])
                    </p>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-light-primary" id="bsExpandAll">@lang('accounting::lang.bs_expand_all')</button>
                <button type="button" class="btn btn-sm btn-light" id="bsCollapseAll">@lang('accounting::lang.bs_collapse_all')</button>
                <button type="button" class="btn btn-sm btn-success" onclick="window.print()">
                    <i class="fa fa-print"></i> @lang('general.print')
                </button>
            </div>
        </div>
    </div>

    <div class="bs-print-header text-center mb-3">
        <h2>{{ $company->name ?? '' }}</h2>
        <h4>@lang('accounting::lang.balance_sheet')</h4>
        <p class="small text-muted mb-0">@lang('accounting::lang.bs_as_at'): {{ $end_date }}</p>
    </div>

    <form method="GET" class="bs-filters-card mb-4 no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1">@lang('accounting::lang.from_date')</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date', $start_date) }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1">@lang('accounting::lang.to_date')</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date', $end_date) }}">
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
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1" for="compare_mode">@lang('accounting::lang.income_statement_growth')</label>
                <select name="compare_mode" id="compare_mode" class="form-select form-select-sm">
                    <option value="none" @selected(($compare_mode ?? 'none') === 'none')>@lang('accounting::lang.bs_compare_none')</option>
                    <option value="previous_period" @selected(($compare_mode ?? '') === 'previous_period')>@lang('accounting::lang.bs_compare_previous_period')</option>
                    <option value="previous_year" @selected(($compare_mode ?? '') === 'previous_year')>@lang('accounting::lang.bs_compare_previous_year')</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label small mb-1" for="with_zero_balances">@lang('accounting::lang.balance')</label>
                <select name="with_zero_balances" id="with_zero_balances" class="form-select form-select-sm">
                    <option value="0" @selected(($with_zero_balances ?? 0) == 0)>@lang('accounting::lang.without_zero_balances')</option>
                    <option value="1" @selected(($with_zero_balances ?? 0) == 1)>@lang('accounting::lang.with_zero_balances')</option>
                </select>
            </div>
            <div class="col-md-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">@lang('report::general.filter')</button>
                <button type="button" id="balanceSheetExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                <button type="button" id="balanceSheetExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
            </div>
        </div>
    </form>

    <div class="alert {{ $isBalanced ? 'alert-success' : 'alert-warning' }} py-2 mb-3 no-print">
        <strong>@lang('accounting::lang.balance'):</strong> {{ $balance_status }}
        <span class="mx-2">|</span>
        <strong>@lang('accounting::lang.difference'):</strong> @format_accounting_amount($difference ?? 0)
        <span class="mx-2">|</span>
        <span class="small">@lang('accounting::lang.bs_equation')</span>
    </div>

    <div class="row g-3 mb-4 no-print">
        @php
            $kpis = [
                ['label' => __('accounting::lang.total_assets'), 'value' => $m['total_assets'] ?? 0],
                ['label' => __('accounting::lang.bs_total_liabilities'), 'value' => $m['total_liabilities'] ?? 0],
                ['label' => __('accounting::lang.bs_total_equity'), 'value' => $m['total_equity'] ?? 0],
                ['label' => __('accounting::lang.bs_working_capital'), 'value' => $m['working_capital'] ?? 0],
                ['label' => __('accounting::lang.bs_liquidity_ratio'), 'value' => $m['current_ratio'] ?? null, 'isRatio' => true],
                ['label' => __('accounting::lang.bs_debt_ratio'), 'value' => $m['debt_percent'] ?? null, 'isPercent' => true],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="bs-kpi">
                    <div class="bs-kpi-label">{{ $kpi['label'] }}</div>
                    <div class="bs-kpi-value">
                        @if(!empty($kpi['isRatio']))
                            {{ $kpi['value'] !== null ? number_format((float) $kpi['value'], 2) : '—' }}
                        @elseif(!empty($kpi['isPercent']))
                            {{ $kpi['value'] !== null ? number_format((float) $kpi['value'], 1).'%' : '—' }}
                        @else
                            @format_accounting_amount($kpi['value'] ?? 0)
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bs-ratio-card no-print mb-4">
        <div class="fw-semibold mb-3 text-gray-800">@lang('accounting::lang.bs_analytics')</div>
        <div class="row g-2">
            @php
                $ratios = [
                    ['label' => __('accounting::lang.bs_current_ratio'), 'value' => $m['current_ratio'] ?? null, 'suffix' => 'x'],
                    ['label' => __('accounting::lang.bs_debt_ratio'), 'value' => $m['debt_percent'] ?? null, 'suffix' => '%'],
                    ['label' => __('accounting::lang.bs_working_capital'), 'value' => $m['working_capital'] ?? 0, 'money' => true],
                    ['label' => __('accounting::lang.bs_equity_ratio'), 'value' => $m['equity_percent'] ?? null, 'suffix' => '%'],
                ];
            @endphp
            @foreach ($ratios as $ratio)
                <div class="col-6 col-md-3">
                    <div class="bs-ratio-item border rounded py-3">
                        <div class="bs-ratio-value">
                            @if(!empty($ratio['money']))
                                @format_accounting_amount($ratio['value'])
                            @else
                                {{ $ratio['value'] !== null ? number_format((float) $ratio['value'], !empty($ratio['suffix']) && $ratio['suffix'] === 'x' ? 2 : 1).($ratio['suffix'] ?? '') : '—' }}
                            @endif
                        </div>
                        <div class="bs-ratio-label">{{ $ratio['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bs-table-card">
        <div class="bs-table-scroll">
            <table class="table table-sm table-hover mb-0" id="balance-sheet-table">
                <thead>
                    <tr>
                        <th style="min-width:55%">@lang('accounting::lang.account_name')</th>
                        <th class="text-end" style="min-width:45%">@lang('employee::fields.amount') (@get_format_currency())</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr class="bs-main-section">
                            <td colspan="2">{{ $section['title'] }}</td>
                        </tr>
                        @foreach ($section['groups'] as $group)
                            @if(($group['type'] ?? '') === 'subsection')
                                <tr class="bs-subsection">
                                    <td colspan="2">{{ $group['label'] }}</td>
                                </tr>
                            @elseif(($group['type'] ?? '') === 'accounts')
                                <tr class="bs-group-header">
                                    <td>{{ $group['label'] }}</td>
                                    @include('accounting::reports.partials.income-statement-amount', ['amount' => $group['total'] ?? 0])
                                </tr>
                                @include('accounting::reports.partials.balance-sheet-account-rows', ['accounts' => $group['accounts']])
                            @elseif(in_array($group['type'] ?? '', ['subtotal', 'grand'], true))
                                <tr class="bs-{{ $group['type'] }}">
                                    <td>{{ $group['label'] }}</td>
                                    @include('accounting::reports.partials.income-statement-amount', ['amount' => $group['amount'] ?? 0])
                                </tr>
                            @endif
                        @endforeach
                    @endforeach
                    <tr class="bs-equation">
                        <td>@lang('accounting::lang.bs_total_liab_equity')</td>
                        @include('accounting::reports.partials.income-statement-amount', ['amount' => $total_liab_owners ?? 0])
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bs-print-footer text-center text-muted small mt-4">
        @lang('accounting::lang.bs_print_footer') — {{ now()->format('Y-m-d H:i') }}
    </div>
</div>
@endsection

@section('script')
<script>
    const balanceSheetExportPdfUrl = '{{ route('balance-sheet-export-pdf') }}';
    const balanceSheetExportExcelUrl = '{{ route('balance-sheet-export-excel') }}';

    function buildBalanceSheetQuery() {
        const params = new URLSearchParams();
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        const withZeroBalances = $('#with_zero_balances').val();
        const compareMode = $('#compare_mode').val();
        const costCenters = $('#choose_cost_center_select').val() || [];
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (withZeroBalances !== null) params.append('with_zero_balances', withZeroBalances);
        if (compareMode) params.append('compare_mode', compareMode);
        const levelFilter = $('#level_filter').val();
        if (levelFilter !== undefined && levelFilter !== null && levelFilter !== '') {
            params.append('level_filter', levelFilter);
        }
        costCenters.forEach(v => params.append('choose_cost_center_select[]', v));
        return params.toString();
    }

    function toggleBsChildren(parentId, show) {
        document.querySelectorAll('tr[data-parent-id="' + parentId + '"]').forEach(row => {
            row.style.display = show ? '' : 'none';
            if (!show && row.dataset.accountId) toggleBsChildren(row.dataset.accountId, false);
        });
    }

    $(document).ready(function() {
        $('#choose_cost_center_select').select2({ width: '100%' });
        $('#with_zero_balances, #compare_mode, #level_filter').select2({ minimumResultsForSearch: Infinity, width: '100%' });

        $('#balanceSheetExportPdf').on('click', () => window.open(balanceSheetExportPdfUrl + '?' + buildBalanceSheetQuery(), '_blank'));
        $('#balanceSheetExportExcel').on('click', () => { window.location.href = balanceSheetExportExcelUrl + '?' + buildBalanceSheetQuery(); });

        $(document).on('click', '[data-bs-toggle-account]', function() {
            const parentId = $(this).data('bs-toggle-account');
            const icon = $(this).find('i');
            const isExpanded = icon.hasClass('fa-chevron-down');
            toggleBsChildren(parentId, !isExpanded);
            icon.toggleClass('fa-chevron-down', !isExpanded);
            icon.toggleClass('fa-chevron-right', isExpanded);
        });

        $('#bsCollapseAll').on('click', function() {
            document.querySelectorAll('.bs-parent-row').forEach(row => {
                if (row.dataset.accountId) toggleBsChildren(row.dataset.accountId, false);
            });
            document.querySelectorAll('[data-bs-toggle-account] i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
        });

        $('#bsExpandAll').on('click', function() {
            document.querySelectorAll('tr[data-parent-id]').forEach(row => { row.style.display = ''; });
            document.querySelectorAll('[data-bs-toggle-account] i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
        });
    });
</script>
@stop
