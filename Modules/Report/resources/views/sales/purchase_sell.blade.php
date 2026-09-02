@extends('layouts.app')

@section('title', __('menuItemLang.purchase-sell'))

@section('css')
<style>
    .report-page-shell .card { border-radius: 14px; }
    .report-filter-card {
        border: 1px solid #eef1f5;
        border-radius: 14px;
        background: #fafcff;
    }
    .ps-summary-table th,
    .ps-summary-table td { padding: 0.65rem 0; }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    #purchase-sell-summary-container .card { border-radius: 12px; }
    @media print {
        .no-print { display: none !important; }
        .report-page-shell .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    }
</style>
@endsection

@section('content')
<div class="report-page-shell">
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 border-0">
            <div class="card-title">
                <div>
                    <h1 class="mb-1 fs-2 fw-bold">@lang('menuItemLang.purchase-sell')</h1>
                    <p class="text-muted fs-7 mb-0">@lang('report::general.purchase_sell_subtitle')</p>
                </div>
            </div>
            <div class="card-toolbar no-print">
                @dashboardcan(\Modules\Report\Support\ReportPermissions::PURCHASE_SELL_PRINT)
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> @lang('report::general.Print')
                </button>
                @enddashboardcan
            </div>
        </x-cards.card-header>

        <div class="card-body border-top p-5 report-filter-card no-print">
            <form id="purchaseSellFilterForm">
                <div class="row g-5 align-items-end">
                    <div class="col-md-4">
                        <label for="psBranchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                        <select class="form-select form-select-solid" id="psBranchFilter" name="branch_id[]"
                            data-control="select2" data-placeholder="@lang('report::general.All Branches')" multiple>
                            <option></option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="psDateRange" class="form-label">@lang('report::general.transaction_date_range')</label>
                        <input type="text" class="form-control form-control-solid" id="psDateRange" name="date_range"
                            value="{{ $defaultDateRangeLabel ?? '' }}"
                            placeholder="@lang('report::general.custom_range')" autocomplete="off" />
                    </div>
                    <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-primary" id="psApplyFilter">
                            <i class="bi bi-funnel"></i> @lang('report::general.Apply Filter')
                        </button>
                        <button type="button" class="btn btn-light" id="psClearFilter">@lang('report::general.Remove filter')</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body border-top px-5 py-6" id="purchase-sell-summary-container">
            @include('report::sales.purchase_sell_details', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
<script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>
<script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
<script>
(function () {
    'use strict';

    const currentLang = @json(app()->getLocale());
    const purchaseSellUrl = @json(route('purchase-sell'));
    const defaultDateRange = @json($defaultDateRange ?? ['start' => now()->startOfYear()->format('Y-m-d'), 'end' => now()->endOfYear()->format('Y-m-d')]);
    let dueDateRangeValue = @json($defaultDateRangeLabel ?? '');

    function dateRangeSeparator() {
        return currentLang === 'ar' ? ' إلى ' : ' to ';
    }

    function setThisYearRange() {
        const yearStart = moment().startOf('year');
        const yearEnd = moment().endOf('year');
        dueDateRangeValue =
            yearStart.format('YYYY-MM-DD') + dateRangeSeparator() + yearEnd.format('YYYY-MM-DD');
        const $input = $('#psDateRange');
        $input.val(dueDateRangeValue);
        const picker = $input.data('daterangepicker');
        if (picker) {
            picker.setStartDate(yearStart);
            picker.setEndDate(yearEnd);
        }
    }

    function filterParams() {
        const picker = $('#psDateRange').data('daterangepicker');
        let start = defaultDateRange.start;
        let end = defaultDateRange.end;
        if (picker) {
            start = picker.startDate.format('YYYY-MM-DD');
            end = picker.endDate.format('YYYY-MM-DD');
        }
        return {
            start_date: start,
            end_date: end,
            date_range: dueDateRangeValue || $('#psDateRange').val(),
            branch_id: $('#psBranchFilter').val() || [],
        };
    }

    function reloadSummary() {
        const $container = $('#purchase-sell-summary-container');
        $container.addClass('opacity-50');
        $.ajax({
            url: purchaseSellUrl,
            type: 'GET',
            data: filterParams(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                $container.html(html);
            },
            complete: function () {
                $container.removeClass('opacity-50');
            },
        });
    }

    function populateBranches() {
        $.get(@json(route('branches')), function (response) {
            if (!response.success) return;
            const $sel = $('#psBranchFilter');
            $sel.empty();
            response.data.forEach(function (branch) {
                $sel.append(new Option(branch.name, branch.id, false, false));
            });
            $sel.trigger('change');
        });
    }

    $(document).ready(function () {
        const ranges = currentLang === 'ar' ? arabicRanges : customRanges;
        const yearStart = moment(defaultDateRange.start);
        const yearEnd = moment(defaultDateRange.end);

        $('#psDateRange').daterangepicker({
            locale: localeSettings[currentLang],
            opens: currentLang === 'ar' ? 'right' : 'left',
            autoUpdateInput: false,
            startDate: yearStart,
            endDate: yearEnd,
            ranges: ranges,
        });

        if (!dueDateRangeValue) {
            setThisYearRange();
        }

        $('#psDateRange').on('apply.daterangepicker', function (ev, picker) {
            dueDateRangeValue =
                picker.startDate.format('YYYY-MM-DD') +
                dateRangeSeparator() +
                picker.endDate.format('YYYY-MM-DD');
            $(this).val(dueDateRangeValue);
        });

        populateBranches();

        $('#psApplyFilter').on('click', reloadSummary);
        $('#psClearFilter').on('click', function () {
            $('#psBranchFilter').val(null).trigger('change');
            setThisYearRange();
            reloadSummary();
        });
    });
})();
</script>
@endsection
