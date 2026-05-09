@extends('layouts.app')

@php
    use Modules\Report\Utils\SalesComparisonPeriodResolver;

    $wsrFooterKeys = array_merge(
        ['product_name', 'category', 'subcategory', 'establishment_name', 'SKU', 'customer'],
        ['qty_period_a', 'avg_unit_price_period_a', 'discount_period_a', 'tax_period_a', 'subtotal_period_a', 'lines_period_a'],
        ['qty_period_b', 'avg_unit_price_period_b', 'discount_period_b', 'tax_period_b', 'subtotal_period_b', 'lines_period_b'],
        ['qty_difference', 'qty_change_percent', 'subtotal_difference', 'subtotal_change_percent', 'discount_difference', 'tax_difference', 'lines_difference']
    );
@endphp

@section('title', __('menuItemLang.weekday-sales-report'))

@section('css')
<style>
    .select2-container .select2-selection--multiple { height: auto !important; min-height: 44px; }
    .sc-filters-card { background: #f8f9fb; border-radius: 12px; border: 1px solid #e9edf3 !important; }
    .sc-table thead .sc-gh-context { background: #eef1f5 !important; color: #1f2937; }
    .sc-table thead .sc-gh-p1 { background: #bfdbfe !important; color: #1e3a8a; text-align: center; }
    .sc-table thead .sc-gh-p2 { background: #fed7aa !important; color: #7c2d12; text-align: center; }
    .sc-table thead .sc-gh-var { background: #ddd6fe !important; color: #4c1d95; text-align: center; }
    .sc-table .sc-cell-dim { background: rgba(249, 250, 251, 0.95); }
    .sc-table .sc-cell-p1 { background: rgba(219, 234, 254, 0.45); }
    .sc-table .sc-cell-p2 { background: rgba(255, 237, 213, 0.45); }
    .sc-table .sc-cell-var { background: rgba(237, 233, 254, 0.45); }
    .sc-table tbody td.sc-diff-up { font-weight: 600; color: #0f5132; }
    .sc-table tbody td.sc-diff-down { font-weight: 600; color: #842029; }
    .wsr-weekday-grid .form-check { min-width: 7rem; }
</style>
@stop

@section('content')
<div class="tab-content">
    <div class="tab-pane fade show active">
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5">
                <div class="card-title">
                    <h3 class="mb-0">@lang('menuItemLang.weekday-sales-report')</h3>
                    <div class="text-muted fs-7 mt-1">@lang('report::general.weekday_sales_hub_card_hint')</div>
                </div>
            </x-cards.card-header>

            <div class="card-body border-top p-5">
                <div class="card sc-filters-card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form id="reportFilterForm">
                            <div class="row g-4 align-items-end">
                                <div class="col-12 col-sm-6 col-xl-3 sc-filter-field">
                                    <label for="filterPanels" class="form-label fw-semibold">@lang('report::general.sales_comparison_filter_panels')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.sales_comparison_filter_panels_hint')</div>
                                    <select class="form-select form-select-solid sc-select2-multi" id="filterPanels" name="filter_panels[]" multiple>
                                        <option value="periods" selected>@lang('report::general.filter_panel_periods')</option>
                                        <option value="quick">@lang('report::general.filter_panel_quick')</option>
                                        <option value="branch">@lang('report::general.filter_panel_branch')</option>
                                        <option value="customer">@lang('report::general.filter_panel_customer')</option>
                                        <option value="product">@lang('report::general.filter_panel_product')</option>
                                        <option value="category">@lang('report::general.filter_panel_category')</option>
                                        <option value="subcategory">@lang('report::general.filter_panel_subcategory')</option>
                                        <option value="unit">@lang('report::general.filter_panel_unit')</option>
                                        <option value="payment">@lang('report::general.filter_panel_payment')</option>
                                    </select>
                                </div>
                                <div class="col-12 col-xl-6 d-flex flex-wrap justify-content-xl-end align-items-end gap-2 pt-2 pt-xl-0">
                                    <button type="button" class="btn btn-primary" id="applyFilter">
                                        <i class="bi bi-funnel fs-2"></i> @lang('report::general.Apply Filter')
                                    </button>
                                    <button type="button" class="btn btn-warning" id="clearFilter">@lang('report::general.Remove filter')</button>
                                </div>
                            </div>

                            <div id="scFilterPanelsHost" class="row g-4 mt-2 align-items-stretch">
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-quick">
                                    <label for="quickComparison" class="form-label fw-semibold">@lang('report::general.sales_comparison_quick_apply')</label>
                                    <select class="form-select form-select-solid sc-select2-single" id="quickComparison" name="quick_comparison">
                                        <option value="">@lang('report::general.sales_comparison_quick_none')</option>
                                        <option value="cm_lm">@lang('report::general.sales_comparison_quick_cm_lm')</option>
                                        <option value="cq_lq">@lang('report::general.sales_comparison_quick_cq_lq')</option>
                                        <option value="cy_ly">@lang('report::general.sales_comparison_quick_cy_ly')</option>
                                        <option value="l7">@lang('report::general.sales_comparison_quick_l7')</option>
                                        <option value="l30">@lang('report::general.sales_comparison_quick_l30')</option>
                                        <option value="ytd_ly">@lang('report::general.sales_comparison_quick_ytd_ly')</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-branch">
                                    <label for="branchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="branchFilter" name="branch_id[]" data-placeholder="@lang('report::general.All Branches')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-customer">
                                    <label for="customerFilter" class="form-label">@lang('report::purchase.Customer')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="customerFilter" name="customer_id[]" data-placeholder="@lang('report::purchase.All Customer')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-product">
                                    <label for="productFilter" class="form-label">@lang('report::purchase.Product')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="productFilter" name="product_id[]" data-placeholder="@lang('report::purchase.All Products')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-category">
                                    <label for="categoryFilter" class="form-label">@lang('report::general.filter_panel_category')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="categoryFilter" name="category_id[]" data-placeholder="@lang('report::general.All_categories')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-subcategory">
                                    <label for="subcategoryFilter" class="form-label">@lang('report::general.filter_panel_subcategory')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="subcategoryFilter" name="subcategory_id[]" data-placeholder="@lang('report::general.All_subcategories')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-unit">
                                    <label for="unitFilter" class="form-label">@lang('report::general.filter_panel_unit')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="unitFilter" name="unit_id[]" data-placeholder="@lang('report::general.All_units')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-payment">
                                    <label for="paymentMethodFilter" class="form-label">@lang('report::purchase.Payment Method')</label>
                                    <select class="form-select form-select-solid sc-filter-multi" id="paymentMethodFilter" name="payment_method[]" data-placeholder="@lang('report::general.All Methods')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-12 sc-filter-field" id="scWrap-periods">
                                    <div class="row g-4">
                                        <div class="col-12 col-md-6 col-xl-3">
                                            <label for="periodAPreset" class="form-label fw-semibold">@lang('report::general.sales_comparison_period_a')</label>
                                            <select class="form-select form-select-solid sc-select2-single" id="periodAPreset" name="period_a_preset">
                                                @foreach (SalesComparisonPeriodResolver::PRESETS as $presetKey)
                                                    <option value="{{ $presetKey }}">{{ __('report::general.preset_' . $presetKey) }}</option>
                                                @endforeach
                                            </select>
                                            <div id="periodACustomWrap" class="mt-3 d-none">
                                                <label for="periodARange" class="form-label">@lang('report::general.period_a_range')</label>
                                                <input type="text" class="form-control form-control-solid" id="periodARange" name="period_a_range" autocomplete="off" />
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-xl-3">
                                            <label for="periodBPreset" class="form-label fw-semibold">@lang('report::general.sales_comparison_period_b')</label>
                                            <select class="form-select form-select-solid sc-select2-single" id="periodBPreset" name="period_b_preset">
                                                @foreach (SalesComparisonPeriodResolver::PRESETS as $presetKey)
                                                    <option value="{{ $presetKey }}">{{ __('report::general.preset_' . $presetKey) }}</option>
                                                @endforeach
                                            </select>
                                            <div id="periodBCustomWrap" class="mt-3 d-none">
                                                <label for="periodBRange" class="form-label">@lang('report::general.period_b_range')</label>
                                                <input type="text" class="form-control form-control-solid" id="periodBRange" name="period_b_range" autocomplete="off" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 sc-filter-field">
                                    <label class="form-label fw-semibold">@lang('report::general.weekday_report_select_days')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_select_days_hint')</div>
                                    <div class="d-flex flex-wrap gap-3 wsr-weekday-grid">
                                        @foreach (range(0, 6) as $d)
                                            <div class="form-check form-check-custom form-check-solid">
                                                <input class="form-check-input wsr-weekday" type="checkbox" name="weekday[]" value="{{ $d }}" id="wsrWd{{ $d }}" checked />
                                                <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="wsrWd{{ $d }}">@lang('report::general.weekday_long_'.$d)</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <x-cards.card-body class="px-5 pb-5 pt-6">
                <div class="alert alert-light-primary d-flex align-items-start gap-2 mb-4">
                    <i class="bi bi-info-circle fs-4 text-primary flex-shrink-0"></i>
                    <div class="fs-7 text-gray-800">@lang('report::general.weekday_report_qty_explanation')</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                    <div class="sc-table-search-pill d-flex align-items-stretch gap-1 ps-2 pe-1 py-1 border rounded flex-grow-1" style="max-width:480px;border-color:#e1e4ea;">
                        <i class="ki-outline ki-magnifier align-self-center ms-1 text-muted"></i>
                        <input type="text" id="wsrTableSearch" data-kt-filter="search" class="form-control border-0 bg-transparent shadow-none" placeholder="@lang('report::general.SalesComparison_search')" />
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-striped table-row-bordered fs-6 gy-5 sc-table" id="kt_WeekdaySales_table" style="width:100%">
                        <thead>
                            <tr class="text-gray-800 fw-bold gs-0">
                                <th rowspan="2" class="text-start min-w-150px sc-gh-context px-2">@lang('report::fields.product_name')</th>
                                <th rowspan="2" class="text-start min-w-100px sc-gh-context px-2">@lang('report::fields.category')</th>
                                <th rowspan="2" class="text-start min-w-100px sc-gh-context px-2">@lang('report::fields.subcategory')</th>
                                <th rowspan="2" class="text-start min-w-100px sc-gh-context px-2">@lang('report::fields.establishment_name')</th>
                                <th rowspan="2" class="text-start min-w-90px sc-gh-context px-2">@lang('report::fields.SKU')</th>
                                <th rowspan="2" class="text-start min-w-100px sc-gh-context px-2">@lang('report::fields.customer')</th>
                                <th colspan="6" class="sc-gh-p1 py-3">@lang('report::general.sales_comparison_group_period_a')</th>
                                <th colspan="6" class="sc-gh-p2 py-3">@lang('report::general.sales_comparison_group_period_b')</th>
                                <th colspan="7" class="sc-gh-var py-3">@lang('report::general.sales_comparison_group_variance')</th>
                            </tr>
                            <tr class="text-gray-700">
                                <th class="text-start min-w-90px">@lang('report::fields.qty_period_a')</th>
                                <th class="text-start min-w-110px">@lang('report::fields.avg_unit_price_period_a')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.discount_period_a')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.tax_period_a')</th>
                                <th class="text-start min-w-100px">@lang('report::fields.subtotal_period_a')</th>
                                <th class="text-start min-w-80px">@lang('report::fields.lines_period_a')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.qty_period_b')</th>
                                <th class="text-start min-w-110px">@lang('report::fields.avg_unit_price_period_b')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.discount_period_b')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.tax_period_b')</th>
                                <th class="text-start min-w-100px">@lang('report::fields.subtotal_period_b')</th>
                                <th class="text-start min-w-80px">@lang('report::fields.lines_period_b')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.qty_difference')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.qty_change_percent')</th>
                                <th class="text-start min-w-100px">@lang('report::fields.subtotal_difference')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.subtotal_change_percent')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.discount_difference')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.tax_difference')</th>
                                <th class="text-start min-w-80px">@lang('report::fields.lines_difference')</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                        <tfoot class="fw-bold text-gray-800">
                            <tr class="sc-footer-totals">
                                @for ($i = 0; $i < 25; $i++)
                                    <td class="sc-cell"></td>
                                @endfor
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-cards.card-body>
        </div>
    </div>
</div>
@stop

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
<script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
<script>
    window.currentLang = @json(str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en');
</script>
<script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>
<script>
    "use strict";
    let dataTable;
    let scLastFooterTotals = null;
    let scRestoringFilters = false;
    const SC_FOOTER_KEYS = @json($wsrFooterKeys);

    const table = $('#kt_WeekdaySales_table');
    const apiUrl = "{{ route('weekday-sales-report') }}";
    const comparisonCategoriesUrl = "{{ route('comparison-categories') }}";
    const comparisonSubcategoriesUrl = "{{ route('comparison-subcategories') }}";
    const comparisonUnitsUrl = "{{ route('comparison-units') }}";
    const comparisonPaymentMethodsUrl = "{{ route('comparison-payment-methods') }}";

    function scSelect2Single($el) {
        if ($el.data('select2')) $el.select2('destroy');
        const ph = $el.data('placeholder');
        $el.select2({
            width: '100%',
            placeholder: ph || undefined,
            allowClear: Boolean($el.find('option[value=""]').length),
            minimumResultsForSearch: 0
        });
    }
    function scSelect2Multi($el) {
        const ph = $el.data('placeholder') || '';
        if ($el.data('select2')) $el.select2('destroy');
        $el.select2({ width: '100%', placeholder: ph, closeOnSelect: false });
    }
    function syncFilterPanels() {
        const v = $('#filterPanels').val() || [];
        $('#scWrap-quick').toggleClass('d-none', v.indexOf('quick') === -1);
        $('#scWrap-branch').toggleClass('d-none', v.indexOf('branch') === -1);
        $('#scWrap-customer').toggleClass('d-none', v.indexOf('customer') === -1);
        $('#scWrap-product').toggleClass('d-none', v.indexOf('product') === -1);
        $('#scWrap-category').toggleClass('d-none', v.indexOf('category') === -1);
        $('#scWrap-subcategory').toggleClass('d-none', v.indexOf('subcategory') === -1);
        $('#scWrap-unit').toggleClass('d-none', v.indexOf('unit') === -1);
        $('#scWrap-payment').toggleClass('d-none', v.indexOf('payment') === -1);
        $('#scWrap-periods').toggleClass('d-none', v.indexOf('periods') === -1);
    }
    function toggleCustomRanges() {
        $('#periodACustomWrap').toggleClass('d-none', $('#periodAPreset').val() !== 'custom');
        $('#periodBCustomWrap').toggleClass('d-none', $('#periodBPreset').val() !== 'custom');
    }
    function bindRangePicker(selector) {
        $(selector).daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' }
        });
        $(selector).on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });
        $(selector).on('cancel.daterangepicker', function() { $(this).val(''); });
    }
    function applyQuickComparison(code) {
        const m = window.moment;
        if (code === 'cm_lm') {
            $('#periodARange').val('');
            $('#periodBRange').val('');
            $('#periodAPreset').val('last_month').trigger('change.select2');
            $('#periodBPreset').val('this_month').trigger('change.select2');
        } else if (code === 'cq_lq') {
            $('#periodARange').val('');
            $('#periodBRange').val('');
            $('#periodAPreset').val('last_quarter').trigger('change.select2');
            $('#periodBPreset').val('this_quarter').trigger('change.select2');
        } else if (code === 'cy_ly') {
            $('#periodARange').val('');
            $('#periodBRange').val('');
            $('#periodAPreset').val('last_year').trigger('change.select2');
            $('#periodBPreset').val('this_year').trigger('change.select2');
        } else if (code === 'l7' && m) {
            const endB = m();
            const startB = m().subtract(6, 'days');
            const endA = m().subtract(7, 'days');
            const startA = m().subtract(13, 'days');
            $('#periodAPreset').val('custom').trigger('change.select2');
            $('#periodBPreset').val('custom').trigger('change.select2');
            $('#periodARange').val(startA.format('YYYY-MM-DD') + ' - ' + endA.format('YYYY-MM-DD'));
            $('#periodBRange').val(startB.format('YYYY-MM-DD') + ' - ' + endB.format('YYYY-MM-DD'));
        } else if (code === 'l30' && m) {
            const endB = m();
            const startB = m().subtract(29, 'days');
            const endA = m().subtract(30, 'days');
            const startA = m().subtract(59, 'days');
            $('#periodAPreset').val('custom').trigger('change.select2');
            $('#periodBPreset').val('custom').trigger('change.select2');
            $('#periodARange').val(startA.format('YYYY-MM-DD') + ' - ' + endA.format('YYYY-MM-DD'));
            $('#periodBRange').val(startB.format('YYYY-MM-DD') + ' - ' + endB.format('YYYY-MM-DD'));
        } else if (code === 'ytd_ly' && m) {
            const today = m();
            const bStart = today.clone().startOf('year');
            const bEnd = today.clone();
            const aStart = today.clone().subtract(1, 'year').startOf('year');
            const aEnd = today.clone().subtract(1, 'year');
            $('#periodAPreset').val('custom').trigger('change.select2');
            $('#periodBPreset').val('custom').trigger('change.select2');
            $('#periodARange').val(aStart.format('YYYY-MM-DD') + ' - ' + aEnd.format('YYYY-MM-DD'));
            $('#periodBRange').val(bStart.format('YYYY-MM-DD') + ' - ' + bEnd.format('YYYY-MM-DD'));
        }
        toggleCustomRanges();
    }
    function setDefaultPeriods() {
        $('#periodAPreset').val('last_month').trigger('change.select2');
        $('#periodBPreset').val('this_month').trigger('change.select2');
        $('#periodARange').val('');
        $('#periodBRange').val('');
        $('#quickComparison').val('').trigger('change.select2');
        toggleCustomRanges();
    }
    function getFilterParams() {
        return {
            branch_id: $('#branchFilter').val() || [],
            customer_id: $('#customerFilter').val() || [],
            product_id: $('#productFilter').val() || [],
            category_id: $('#categoryFilter').val() || [],
            subcategory_id: $('#subcategoryFilter').val() || [],
            unit_id: $('#unitFilter').val() || [],
            payment_method: $('#paymentMethodFilter').val() || [],
            period_a_preset: $('#periodAPreset').val(),
            period_b_preset: $('#periodBPreset').val(),
            period_a_range: $('#periodARange').val(),
            period_b_range: $('#periodBRange').val(),
            weekday: $('.wsr-weekday:checked').map(function() { return $(this).val(); }).get()
        };
    }
    function applyScFooterToDom(tfoot) {
        const f = scLastFooterTotals;
        const $row = $(tfoot).find('tr.sc-footer-totals');
        const $cells = $row.find('td');
        if (!$cells.length) return;
        const parseNum = (s) => {
            if (s == null || s === '' || s === '—') return null;
            const n = parseFloat(String(s).replace(/,/g, ''));
            return isNaN(n) ? null : n;
        };
        SC_FOOTER_KEYS.forEach(function(key, i) {
            const $td = $cells.eq(i);
            $td.removeClass('sc-cell-dim sc-cell-p1 sc-cell-p2 sc-cell-var sc-diff-up sc-diff-down text-center');
            if (i <= 5) $td.addClass('sc-cell sc-cell-dim');
            else if (i <= 11) $td.addClass('sc-cell sc-cell-p1');
            else if (i <= 17) $td.addClass('sc-cell sc-cell-p2');
            else $td.addClass('sc-cell sc-cell-var');
            const text = f && f[key] != null ? String(f[key]) : '';
            $td.text(text);
        });
        if (f) {
            const q = parseNum(f.qty_difference);
            const r = parseNum(f.subtotal_difference);
            const qCell = $cells.eq(18);
            const rCell = $cells.eq(20);
            if (q !== null) {
                if (q > 0) qCell.addClass('sc-diff-up');
                else if (q < 0) qCell.addClass('sc-diff-down');
            }
            if (r !== null) {
                if (r > 0) rCell.addClass('sc-diff-up');
                else if (r < 0) rCell.addClass('sc-diff-down');
            }
        }
    }
    function styleVarianceCells(row, data) {
        const parseNum = (s) => {
            if (s == null || s === '') return null;
            const n = parseFloat(String(s).replace(/,/g, ''));
            return isNaN(n) ? null : n;
        };
        const q = parseNum(data.qty_difference);
        const r = parseNum(data.subtotal_difference);
        const $cells = $(row).find('td');
        const qCell = $cells.eq(18);
        const rCell = $cells.eq(20);
        if (q !== null) {
            if (q > 0) qCell.addClass('sc-diff-up');
            else if (q < 0) qCell.addClass('sc-diff-down');
        }
        if (r !== null) {
            if (r > 0) rCell.addClass('sc-diff-up');
            else if (r < 0) rCell.addClass('sc-diff-down');
        }
    }

    function populateBranches() {
        return $.ajax({
            url: "{{ route('branches') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#branchFilter');
                    sel.empty();
                    response.data.forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }
    function populateCustomers() {
        return $.ajax({
            url: "{{ route('getCustomers') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#customerFilter');
                    sel.empty();
                    response.data.forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }
    function populateProducts() {
        return $.ajax({
            url: "{{ route('retrieveProducts') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#productFilter');
                    sel.empty();
                    response.data.forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }
    function populateCategories() {
        return $.ajax({
            url: comparisonCategoriesUrl,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#categoryFilter');
                    sel.empty();
                    (response.data || []).forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }
    function populateSubcategories(subIds) {
        const catIds = $('#categoryFilter').val() || [];
        return $.ajax({
            url: comparisonSubcategoriesUrl,
            type: 'GET',
            traditional: true,
            data: { category_id: catIds },
            success: function(response) {
                if (response.success) {
                    const sel = $('#subcategoryFilter');
                    sel.empty();
                    (response.data || []).forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    if (subIds && subIds.length) sel.val(subIds).trigger('change');
                    else sel.val(null).trigger('change');
                }
            }
        });
    }
    function populateUnits() {
        return $.ajax({
            url: comparisonUnitsUrl,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#unitFilter');
                    sel.empty();
                    (response.data || []).forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }
    function populatePaymentMethods() {
        return $.ajax({
            url: comparisonPaymentMethodsUrl,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const sel = $('#paymentMethodFilter');
                    sel.empty();
                    (response.data || []).forEach(function(row) {
                        sel.append(new Option(row.name, row.id, false, false));
                    });
                    sel.trigger('change');
                }
            }
        });
    }

    function initDatatable() {
        dataTable = $(table).DataTable({
            processing: true,
            serverSide: false,
            paging: false,
            searching: true,
            ordering: true,
            order: [[0, 'asc']],
            ajax: {
                url: apiUrl,
                type: 'GET',
                traditional: true,
                data: function(d) {
                    return $.extend(true, {}, d, getFilterParams());
                },
                dataSrc: function(json) {
                    if (!json || typeof json !== 'object') {
                        scLastFooterTotals = null;
                        return [];
                    }
                    scLastFooterTotals = json.sc_footer_totals || null;
                    return Array.isArray(json.data) ? json.data : [];
                }
            },
            columns: [
                { data: 'product_name', name: 'product_name' },
                { data: 'category', name: 'category' },
                { data: 'subcategory', name: 'subcategory' },
                { data: 'establishment_name', name: 'establishment_name' },
                { data: 'SKU', name: 'SKU' },
                { data: 'customer', name: 'customer' },
                { data: 'qty_period_a', name: 'qty_period_a' },
                { data: 'avg_unit_price_period_a', name: 'avg_unit_price_period_a' },
                { data: 'discount_period_a', name: 'discount_period_a' },
                { data: 'tax_period_a', name: 'tax_period_a' },
                { data: 'subtotal_period_a', name: 'subtotal_period_a' },
                { data: 'lines_period_a', name: 'lines_period_a' },
                { data: 'qty_period_b', name: 'qty_period_b' },
                { data: 'avg_unit_price_period_b', name: 'avg_unit_price_period_b' },
                { data: 'discount_period_b', name: 'discount_period_b' },
                { data: 'tax_period_b', name: 'tax_period_b' },
                { data: 'subtotal_period_b', name: 'subtotal_period_b' },
                { data: 'lines_period_b', name: 'lines_period_b' },
                { data: 'qty_difference', name: 'qty_difference' },
                { data: 'qty_change_percent', name: 'qty_change_percent' },
                { data: 'subtotal_difference', name: 'subtotal_difference' },
                { data: 'subtotal_change_percent', name: 'subtotal_change_percent' },
                { data: 'discount_difference', name: 'discount_difference' },
                { data: 'tax_difference', name: 'tax_difference' },
                { data: 'lines_difference', name: 'lines_difference' }
            ],
            columnDefs: [
                { targets: [0, 1, 2, 3, 4, 5], className: 'sc-cell sc-cell-dim' },
                { targets: [6, 7, 8, 9, 10, 11], className: 'sc-cell sc-cell-p1' },
                { targets: [12, 13, 14, 15, 16, 17], className: 'sc-cell sc-cell-p2' },
                { targets: [18, 19, 20, 21, 22, 23, 24], className: 'sc-cell sc-cell-var' }
            ],
            info: true,
            createdRow: function(row, data) {
                styleVarianceCells(row, data);
            },
            footerCallback: function(tfoot) {
                applyScFooterToDom(tfoot);
            }
        });
        window.dataTable = dataTable;
    }

    $(document).ready(function() {
        if (!table.length) return;
        scSelect2Multi($('#filterPanels'));
        $('#filterPanels').on('change', syncFilterPanels);
        $('#categoryFilter').on('change', function() {
            if (scRestoringFilters) return;
            populateSubcategories();
        });
        $('.sc-filter-multi').each(function() { scSelect2Multi($(this)); });
        $('.sc-select2-single').each(function() { scSelect2Single($(this)); });
        bindRangePicker('#periodARange');
        bindRangePicker('#periodBRange');
        $('#periodAPreset, #periodBPreset').on('change', toggleCustomRanges);
        $('#quickComparison').on('change', function() {
            const code = $(this).val();
            if (!code) return;
            applyQuickComparison(code);
            if (dataTable) dataTable.ajax.reload();
        });
        $.when(
            populateBranches(),
            populateCustomers(),
            populateProducts(),
            populateCategories(),
            populateUnits(),
            populatePaymentMethods()
        ).always(function() {
            scRestoringFilters = true;
            $('#filterPanels').val(['periods']).trigger('change');
            syncFilterPanels();
            setDefaultPeriods();
            populateSubcategories().always(function() {
                scRestoringFilters = false;
                initDatatable();
                handleSearchDatatable(dataTable);
            });
        });
        $('#applyFilter').on('click', function() {
            if (dataTable) dataTable.ajax.reload();
        });
        $('#clearFilter').on('click', function() {
            $('#branchFilter').val(null).trigger('change');
            $('#customerFilter').val(null).trigger('change');
            $('#productFilter').val(null).trigger('change');
            $('#categoryFilter').val(null).trigger('change');
            $('#subcategoryFilter').val(null).trigger('change');
            $('#unitFilter').val(null).trigger('change');
            $('#paymentMethodFilter').val(null).trigger('change');
            populateSubcategories();
            $('#filterPanels').val(['periods']).trigger('change');
            syncFilterPanels();
            setDefaultPeriods();
            $('.wsr-weekday').prop('checked', true);
            $('#wsrTableSearch').val('');
            if (dataTable) {
                dataTable.search('').ajax.reload();
            }
        });
    });
</script>
@stop
