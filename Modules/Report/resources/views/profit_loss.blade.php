@extends('layouts.app')

@section('title', __('menuItemLang.Profit-Loss'))

@section('css')
<style>
    .report-page-shell .card { border-radius: 14px; }
    .report-filter-card {
        border: 1px solid #eef1f5;
        border-radius: 14px;
        background: #fafcff;
    }
    .report-table-card {
        border: 1px solid #eef1f5;
        border-radius: 14px;
        padding: 1rem 1.25rem;
        background: #fff;
    }
    .pl-summary-table th,
    .pl-summary-table td { padding: 0.65rem 0; }
    .tabular-nums { font-variant-numeric: tabular-nums; }
    #profit-loss-summary .card { border-radius: 12px; }
    .pl-datatable thead th { padding: 1rem 1.25rem !important; }
    .pl-datatable tbody td { padding: 1rem 1.25rem !important; }
</style>
@endsection

@section('content')
<div class="report-page-shell">
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 border-0">
            <div class="card-title">
                <div>
                    <h1 class="mb-1 fs-2 fw-bold">@lang('menuItemLang.Profit-Loss')</h1>
                    <p class="text-muted fs-7 mb-0">@lang('report::general.profit_loss_subtitle')</p>
                </div>
            </div>
            <div class="card-toolbar no-print">
                <button type="button" class="btn btn-sm btn-light-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> @lang('report::general.Print')
                </button>
            </div>
        </x-cards.card-header>

        <div class="card-body border-top p-5 report-filter-card no-print">
            <form id="profitLossFilterForm">
                <div class="row g-5 align-items-end">
                    <div class="col-md-4">
                        <label for="plBranchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                        <select class="form-select form-select-solid" id="plBranchFilter" name="branch_id[]"
                            data-control="select2" data-placeholder="@lang('report::general.All Branches')" multiple>
                            <option></option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="plDateRange" class="form-label">@lang('report::general.transaction_date_range')</label>
                        <input type="text" class="form-control form-control-solid" id="plDateRange" name="date_range"
                            value="{{ $defaultDateRangeLabel ?? '' }}"
                            placeholder="@lang('report::general.custom_range')" autocomplete="off" />
                    </div>
                    <div class="col-md-3 d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-primary" id="plApplyFilter">
                            <i class="bi bi-funnel"></i> @lang('report::general.Apply Filter')
                        </button>
                        <button type="button" class="btn btn-light" id="plClearFilter">@lang('report::general.Remove filter')</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body border-top px-5 py-6" id="pl-summary-container">
            @include('report::profit_loss_details', ['data' => $data])
        </div>

        <div class="card-body border-top report-table-card no-print">
            <h2 class="fs-5 fw-bold text-gray-800 mb-4">@lang('report::general.profit_breakdown_tabs')</h2>
            <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold mb-5">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#profit_by_products">@lang('report::general.profit_by_products')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_categories">@lang('report::general.profit_by_categories')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_locations">@lang('report::general.profit_by_locations')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_invoice">@lang('report::general.profit_by_invoice')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_date">@lang('report::general.profit_by_date')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_customer">@lang('report::general.profit_by_customer')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#profit_by_day">@lang('report::general.profit_by_day')</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="profit_by_products">
                    @include('report::profit_by_products')
                </div>
                <div class="tab-pane fade" id="profit_by_categories">
                    @include('report::profit_by_categories')
                </div>
                <div class="tab-pane fade" id="profit_by_locations">
                    @include('report::profit_by_locations')
                </div>
                <div class="tab-pane fade" id="profit_by_invoice">
                    @include('report::profit_by_invoice')
                </div>
                <div class="tab-pane fade" id="profit_by_date">
                    @include('report::profit_by_date')
                </div>
                <div class="tab-pane fade" id="profit_by_customer">
                    @include('report::profit_by_customer')
                </div>
                <div class="tab-pane fade" id="profit_by_day"></div>
            </div>
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
    const profitLossUrl = @json(route('Profit-Loss'));
    const defaultDateRange = @json($defaultDateRange ?? ['start' => now()->startOfYear()->format('Y-m-d'), 'end' => now()->endOfYear()->format('Y-m-d')]);
    let dueDateRangeValue = @json($defaultDateRangeLabel ?? '');
    let profit_by_products_table;
    let profit_by_categories_datatable;
    let profit_by_locations_datatable;
    let profit_by_invoice_datatable;
    let profit_by_date_datatable;
    let profit_by_customers_table;

    function dateRangeSeparator() {
        return currentLang === 'ar' ? ' إلى ' : ' to ';
    }

    function setThisYearRange() {
        const yearStart = moment().startOf('year');
        const yearEnd = moment().endOf('year');
        dueDateRangeValue =
            yearStart.format('YYYY-MM-DD') + dateRangeSeparator() + yearEnd.format('YYYY-MM-DD');
        const $input = $('#plDateRange');
        $input.val(dueDateRangeValue);
        const picker = $input.data('daterangepicker');
        if (picker) {
            picker.setStartDate(yearStart);
            picker.setEndDate(yearEnd);
        }
    }

    function filterParams() {
        const picker = $('#plDateRange').data('daterangepicker');
        let start = defaultDateRange.start;
        let end = defaultDateRange.end;
        if (picker) {
            start = picker.startDate.format('YYYY-MM-DD');
            end = picker.endDate.format('YYYY-MM-DD');
        }
        return {
            start_date: start,
            end_date: end,
            date_range: dueDateRangeValue || $('#plDateRange').val(),
            branch_id: $('#plBranchFilter').val() || [],
        };
    }

    function reloadSummary() {
        const $container = $('#pl-summary-container');
        $container.addClass('opacity-50');
        $.ajax({
            url: profitLossUrl,
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

    function ajaxDataFn(d) {
        const f = filterParams();
        d.start_date = f.start_date;
        d.end_date = f.end_date;
        d.date_range = f.date_range;
        d.branch_id = f.branch_id;
    }

    function formatMoney(data) {
        if (data === null || data === undefined || data === '') return '—';
        const n = parseFloat(data);
        if (isNaN(n)) return data;
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function populateBranches() {
        $.get("{{ route('branches') }}", function (response) {
            if (!response.success) return;
            const $sel = $('#plBranchFilter');
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
        $('#plDateRange').daterangepicker({
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
        $('#plDateRange').on('apply.daterangepicker', function (ev, picker) {
            dueDateRangeValue =
                picker.startDate.format('YYYY-MM-DD') +
                dateRangeSeparator() +
                picker.endDate.format('YYYY-MM-DD');
            $(this).val(dueDateRangeValue);
        });

        populateBranches();

        $('#plApplyFilter').on('click', function () {
            reloadSummary();
            if (profit_by_products_table) profit_by_products_table.ajax.reload();
            if (profit_by_categories_datatable) profit_by_categories_datatable.ajax.reload();
            if (profit_by_locations_datatable) profit_by_locations_datatable.ajax.reload();
            if (profit_by_invoice_datatable) profit_by_invoice_datatable.ajax.reload();
            if (profit_by_date_datatable) profit_by_date_datatable.ajax.reload();
            if (profit_by_customers_table) profit_by_customers_table.ajax.reload();
        });

        $('#plClearFilter').on('click', function () {
            $('#plBranchFilter').val(null).trigger('change');
            setThisYearRange();
            reloadSummary();
            if (profit_by_products_table) profit_by_products_table.ajax.reload();
            if (profit_by_categories_datatable) profit_by_categories_datatable.ajax.reload();
            if (profit_by_locations_datatable) profit_by_locations_datatable.ajax.reload();
            if (profit_by_invoice_datatable) profit_by_invoice_datatable.ajax.reload();
            if (profit_by_date_datatable) profit_by_date_datatable.ajax.reload();
            if (profit_by_customers_table) profit_by_customers_table.ajax.reload();
        });

        profit_by_products_table = $('#profit_by_products_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/reports/get-profit/product',
                data: ajaxDataFn,
            },
            columns: [
                { data: 'product', name: 'product', searchable: false },
                { data: 'gross_profit', name: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
            ],
            columnDefs: [{ targets: 1, render: formatMoney }],
            footerCallback: function (row, data) {
                let total = 0;
                for (const r of data) {
                    total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                }
                $('#profit_by_products_table .footer_total').html(formatMoney(total));
            },
        });

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href');
            if (target === '#profit_by_categories') {
                if (typeof profit_by_categories_datatable === 'undefined') {
                    profit_by_categories_datatable = $('#profit_by_categories_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: { url: '/reports/get-profit/category', data: ajaxDataFn },
                        columns: [
                            { data: 'category', name: 'category' },
                            { data: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
                        ],
                        columnDefs: [{ targets: 1, render: formatMoney }],
                        order: [],
                        footerCallback: function (row, data) {
                            let total = 0;
                            for (const r of data) total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                            $('#profit_by_categories_table .footer_total').html(formatMoney(total));
                        },
                    });
                } else profit_by_categories_datatable.ajax.reload();
            } else if (target === '#profit_by_locations') {
                if (typeof profit_by_locations_datatable === 'undefined') {
                    profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: { url: '/reports/get-profit/location', data: ajaxDataFn },
                        columns: [
                            { data: 'location', name: 'E.name' },
                            { data: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
                        ],
                        columnDefs: [{ targets: 1, render: formatMoney }],
                        footerCallback: function (row, data) {
                            let total = 0;
                            for (const r of data) total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                            $('#profit_by_locations_table .footer_total').html(formatMoney(total));
                        },
                    });
                } else profit_by_locations_datatable.ajax.reload();
            } else if (target === '#profit_by_invoice') {
                if (typeof profit_by_invoice_datatable === 'undefined') {
                    profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: { url: '/reports/get-profit/invoice', data: ajaxDataFn },
                        columns: [
                            { data: 'ref_no', name: 'sale.ref_no' },
                            { data: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
                        ],
                        columnDefs: [{ targets: 1, render: formatMoney }],
                        footerCallback: function (row, data) {
                            let total = 0;
                            for (const r of data) total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                            $('#profit_by_invoice_table .footer_total').html(formatMoney(total));
                        },
                    });
                } else profit_by_invoice_datatable.ajax.reload();
            } else if (target === '#profit_by_date') {
                if (typeof profit_by_date_datatable === 'undefined') {
                    profit_by_date_datatable = $('#profit_by_date_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: { url: '/reports/get-profit/date', data: ajaxDataFn },
                        columns: [
                            { data: 'transaction_date', name: 'sale.transaction_date' },
                            { data: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
                        ],
                        columnDefs: [{ targets: 1, render: formatMoney }],
                        footerCallback: function (row, data) {
                            let total = 0;
                            for (const r of data) total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                            $('#profit_by_date_table .footer_total').html(formatMoney(total));
                        },
                    });
                } else profit_by_date_datatable.ajax.reload();
            } else if (target === '#profit_by_customer') {
                if (typeof profit_by_customers_table === 'undefined') {
                    profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: { url: '/reports/get-profit/customer', data: ajaxDataFn },
                        columns: [
                            { data: 'customer', name: 'CU.name' },
                            { data: 'gross_profit', searchable: false, className: 'text-end tabular-nums' },
                        ],
                        columnDefs: [{ targets: 1, render: formatMoney }],
                        footerCallback: function (row, data) {
                            let total = 0;
                            for (const r of data) total += r.gross_profit ? parseFloat(r.gross_profit) : 0;
                            $('#profit_by_customer_table .footer_total').html(formatMoney(total));
                        },
                    });
                } else profit_by_customers_table.ajax.reload();
            } else if (target === '#profit_by_day') {
                $.ajax({
                    url: '/reports/get-profit/day',
                    data: filterParams(),
                    dataType: 'html',
                    success: function (result) {
                        $('#profit_by_day').html(result);
                        if ($.fn.DataTable.isDataTable('#profit_by_day_table')) {
                            $('#profit_by_day_table').DataTable().destroy();
                        }
                        $('#profit_by_day_table').DataTable({ searching: false, paging: false, ordering: false });
                        if (typeof sum_table_col === 'function') {
                            const total = sum_table_col($('#profit_by_day_table'), 'gross-profit');
                            $('#profit_by_day_table .footer_total').text(total);
                        }
                    },
                });
            } else if (target === '#profit_by_products') {
                profit_by_products_table.ajax.reload();
            }
        });
    });
})();
</script>
@endsection
