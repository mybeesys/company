@extends('layouts.app')

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
    .sc-export-toolbar { gap: 0.5rem; }
    .sc-export-toolbar__label { font-weight: 600; color: #475569; font-size: 0.9rem; }
    [dir="rtl"] .sc-export-toolbar__label { margin-left: 0.35rem; }
    .sc-export-btn-group { box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06); }
    .sc-export-btn { font-weight: 600; border: 1px solid #e2e8f0 !important; padding: 0.5rem 0.85rem; }
    .sc-export-btn:focus { box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25); }
    .sc-export-btn--excel { background: #ecfdf5 !important; color: #047857 !important; }
    .sc-export-btn--excel:hover { filter: brightness(0.97); }
    .sc-export-btn--pdf { background: #fef2f2 !important; color: #b91c1c !important; }
    .sc-export-btn--pdf:hover { filter: brightness(0.97); }
    .sc-export-btn .sc-export-btn__icon { font-size: 1.1rem; opacity: 0.9; }
    .wsr-executive-section {
        background: linear-gradient(180deg, #f1f5f9 0%, #ffffff 42%);
        border-top: 1px solid #e2e8f0 !important;
    }
    .wsr-kpi-card {
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .wsr-kpi-card:hover {
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07) !important;
    }
    .wsr-kpi-card--p1 { border-top: 3px solid #2563eb; }
    .wsr-kpi-card--p2 { border-top: 3px solid #ea580c; }
    .wsr-kpi-card--single { border-top: 3px solid #4f46e5; }
    .wsr-kpi-value { font-size: 1.75rem; font-weight: 700; line-height: 1.15; color: #0f172a; letter-spacing: -0.02em; }
    @media (min-width: 1200px) {
        .wsr-kpi-value { font-size: 2rem; }
    }
    .wsr-kpi-delta { font-weight: 700; }
    .wsr-chart-slot { height: 260px; position: relative; max-width: 100%; }
    .wsr-chart-slot canvas { max-height: 260px; }
</style>
@stop

@section('content')
<div class="tab-content">
    <div class="tab-pane fade show active">
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-3 gap-md-5 flex-wrap">
                <div class="card-title flex-grow-1 me-2">
                    <h3 class="mb-0">@lang('menuItemLang.weekday-sales-report')</h3>
                    <div class="text-muted fs-7 mt-1">@lang('report::general.weekday_sales_hub_card_hint')</div>
                </div>
                <div class="card-toolbar sc-export-toolbar">
                    <span class="sc-export-toolbar__label d-none d-sm-inline">@lang('report::general.export_actions_label')</span>
                    <div class="btn-group sc-export-btn-group" role="group" aria-label="@lang('report::general.export_actions_label')">
                        <button type="button"
                            class="btn sc-export-btn sc-export-btn--excel"
                            id="wsrExportExcel"
                            title="@lang('report::general.export_excel_hint')">
                            <i class="bi bi-file-earmark-spreadsheet sc-export-btn__icon" aria-hidden="true"></i>
                            <span>@lang('report::general.export_excel_btn')</span>
                        </button>
                        <button type="button"
                            class="btn sc-export-btn sc-export-btn--pdf"
                            id="wsrExportPdf"
                            title="@lang('report::general.export_pdf_hint')">
                            <i class="bi bi-file-pdf sc-export-btn__icon" aria-hidden="true"></i>
                            <span>@lang('report::general.export_pdf_btn')</span>
                        </button>
                    </div>
                </div>
            </x-cards.card-header>

            <div class="card-body border-top p-5">
                <div class="card sc-filters-card shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form id="reportFilterForm">
                            <div class="row g-4 align-items-start">
                                <div class="col-12 col-xl-4 sc-filter-field">
                                    <label for="filterPanels" class="form-label fw-semibold">@lang('report::general.sales_comparison_filter_panels')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_filter_panels_hint')</div>
                                    <select class="form-select form-select-solid sc-select2-multi" id="filterPanels" name="filter_panels[]" multiple>
                                        <option value="branch">@lang('report::general.filter_panel_branch')</option>
                                        <option value="customer">@lang('report::general.filter_panel_customer')</option>
                                        <option value="product">@lang('report::general.filter_panel_product')</option>
                                        <option value="category">@lang('report::general.filter_panel_category')</option>
                                        <option value="subcategory">@lang('report::general.filter_panel_subcategory')</option>
                                        <option value="unit">@lang('report::general.filter_panel_unit')</option>
                                        <option value="payment">@lang('report::general.filter_panel_payment')</option>
                                    </select>
                                </div>
                                <div class="col-12 col-xl-8">
                                    <label for="wsrPeriodScope" class="form-label fw-semibold">@lang('report::general.weekday_report_period_select_label')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_period_select_hint')</div>
                                    <select class="form-select form-select-solid sc-select2-single" id="wsrPeriodScope" name="weekday_report_scope">
                                        <option value="single_month_to_date">@lang('report::general.preset_month_to_date')</option>
                                        <option value="single_this_month" selected>@lang('report::general.preset_this_month')</option>
                                        <option value="single_last_month">@lang('report::general.preset_last_month')</option>
                                        <option value="single_last_7_days">@lang('report::general.preset_last_7_days')</option>
                                        <option value="single_last_30_days">@lang('report::general.preset_last_30_days')</option>
                                        <option value="single_last_90_days">@lang('report::general.preset_last_90_days')</option>
                                        <option value="single_today">@lang('report::general.preset_today')</option>
                                        <option value="single_yesterday">@lang('report::general.preset_yesterday')</option>
                                        <option value="single_pick_day">@lang('report::general.weekday_report_scope_single_pick_day')</option>
                                    </select>
                                    <div class="row g-3 mt-2 d-none" id="wsrPickDayWrap">
                                        <div class="col-12 col-sm-6 col-md-4 col-xl-5">
                                            <label for="wsrPickDay" class="form-label fw-semibold">@lang('report::general.weekday_report_pick_day_label')</label>
                                            <input type="date" class="form-control form-control-solid" id="wsrPickDay" name="wsr_pick_day" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" />
                                            <div class="text-muted fs-8 mt-2">@lang('report::general.weekday_report_pick_day_hint')</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="scFilterPanelsHost" class="row g-4 mt-3 align-items-stretch">
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
                                <div class="col-12 sc-filter-field">
                                    <label class="form-label fw-semibold">@lang('report::general.weekday_report_select_days')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_select_days_hint')</div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                        <button type="button" class="btn btn-sm btn-light" id="wsrWeekdaysAll">@lang('report::general.weekday_report_select_all')</button>
                                        <button type="button" class="btn btn-sm btn-light" id="wsrWeekdaysNone">@lang('report::general.weekday_report_select_none')</button>
                                        <div class="ms-auto d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-primary" id="applyFilter">
                                                <i class="bi bi-funnel fs-2"></i> @lang('report::general.Apply Filter')
                                            </button>
                                            <button type="button" class="btn btn-warning" id="clearFilter">@lang('report::general.Remove filter')</button>
                                        </div>
                                    </div>
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

            <div class="card-body border-0 px-5 py-8 wsr-executive-section" id="wsrExecutiveSection">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-4 mb-7">
                    <div class="flex-grow-1">
                        <h4 class="fs-3 fw-bold text-gray-900 mb-2">@lang('report::general.weekday_report_executive_title')</h4>
                        <p class="text-muted fs-6 mb-0" style="max-width: 42rem;">@lang('report::general.weekday_report_executive_subtitle')</p>
                    </div>
                </div>

                <div class="row g-4 g-xl-5 mb-8" id="wsrKpiCompareRow">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="wsr-kpi-card wsr-kpi-card--p1 rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_qty')</div>
                            <div class="fs-9 text-muted mb-1 text-truncate" id="wsrKpiQtyALabel" title="">—</div>
                            <div class="wsr-kpi-value" id="wsrKpiQtyA">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="wsr-kpi-card wsr-kpi-card--p2 rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_qty')</div>
                            <div class="fs-9 text-muted mb-1 text-truncate" id="wsrKpiQtyBLabel" title="">—</div>
                            <div class="wsr-kpi-value" id="wsrKpiQtyB">—</div>
                            <div class="mt-4 pt-4 border-top border-gray-100">
                                <span class="fs-9 text-muted d-block mb-1">@lang('report::general.weekday_report_kpi_change_qty')</span>
                                <span class="fs-5 wsr-kpi-delta" id="wsrDeltaQty">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3 wsr-kpi-nonqty">
                        <div class="wsr-kpi-card wsr-kpi-card--p1 rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_revenue')</div>
                            <div class="fs-9 text-muted mb-1 text-truncate" id="wsrKpiRevALabel" title="">—</div>
                            <div class="wsr-kpi-value" id="wsrKpiRevA">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3 wsr-kpi-nonqty">
                        <div class="wsr-kpi-card wsr-kpi-card--p2 rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_revenue')</div>
                            <div class="fs-9 text-muted mb-1 text-truncate" id="wsrKpiRevBLabel" title="">—</div>
                            <div class="wsr-kpi-value" id="wsrKpiRevB">—</div>
                            <div class="mt-4 pt-4 border-top border-gray-100">
                                <span class="fs-9 text-muted d-block mb-1">@lang('report::general.weekday_report_kpi_change_revenue')</span>
                                <span class="fs-5 wsr-kpi-delta" id="wsrDeltaRev">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 g-xl-5 mb-8 d-none" id="wsrKpiSingleRow">
                    <div class="col-12">
                        <div class="rounded-3 border border-dashed border-gray-300 bg-white px-4 py-3 mb-4">
                            <span class="fs-8 text-muted fw-semibold text-uppercase me-2">@lang('report::general.weekday_report_kpi_period_note')</span>
                            <span class="fs-7 text-gray-800 fw-semibold" id="wsrKpiSinglePeriodNote">—</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="wsr-kpi-card wsr-kpi-card--single rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100 text-center text-md-start">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_qty')</div>
                            <div class="wsr-kpi-value" id="wsrKpiSingleQty">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 wsr-kpi-nonqty">
                        <div class="wsr-kpi-card wsr-kpi-card--single rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100 text-center text-md-start">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_revenue')</div>
                            <div class="wsr-kpi-value" id="wsrKpiSingleRev">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 wsr-kpi-nonqty">
                        <div class="wsr-kpi-card wsr-kpi-card--single rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100 text-center text-md-start">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_lines')</div>
                            <div class="wsr-kpi-value" id="wsrKpiSingleLines">—</div>
                        </div>
                    </div>
                </div>

            </div>

            <x-cards.card-body class="px-5 pb-5 pt-6">
                <div id="wsrSimpleNotice" class="alert alert-warning d-none mb-4" role="alert"></div>
                <div class="alert alert-light-primary d-flex align-items-start gap-2 mb-4" id="wsrSimpleExplanation">
                    <i class="bi bi-info-circle fs-4 text-primary flex-shrink-0"></i>
                    <div class="fs-7 text-gray-800">@lang('report::general.weekday_simple_grid_info')</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                    <div class="sc-table-search-pill d-flex align-items-stretch gap-1 ps-2 pe-1 py-1 border rounded flex-grow-1" style="max-width:480px;border-color:#e1e4ea;">
                        <i class="ki-outline ki-magnifier align-self-center ms-1 text-muted"></i>
                        <input type="text" id="wsrTableSearch" data-kt-filter="search" class="form-control border-0 bg-transparent shadow-none" placeholder="@lang('report::general.SalesComparison_search')" />
                    </div>
                </div>
                <div class="table-responsive" id="wsrSimpleGridWrap">
                    <table class="table table-bordered table-striped table-row-bordered align-middle fs-6 gy-3" id="wsrSimpleGridTable">
                        <thead class="fw-bold text-gray-800">
                            <tr id="wsrSimpleHeadRow1"></tr>
                            <tr id="wsrSimpleHeadRow2"></tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700" id="wsrSimpleBody"></tbody>
                    </table>
                </div>
            </x-cards.card-body>
        </div>
    </div>
</div>
@stop

@section('script')
@parent
<script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
<script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
<script>
    window.currentLang = @json(str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en');
</script>
<script>
    "use strict";
    let wsrLastKpi = null;
    let scRestoringFilters = false;
    const apiUrl = "{{ route('weekday-sales-report') }}";
    const exportExcelUrl = "{{ route('weekday-sales-export-excel') }}";
    const exportPdfUrl = "{{ route('weekday-sales-export-pdf') }}";
    const exportFailedMsg = @json(__('report::general.export_failed_message'));
    const comparisonCategoriesUrl = "{{ route('comparison-categories') }}";
    const comparisonSubcategoriesUrl = "{{ route('comparison-subcategories') }}";
    const comparisonUnitsUrl = "{{ route('comparison-units') }}";
    const comparisonPaymentMethodsUrl = "{{ route('comparison-payment-methods') }}";
    const WSR_GRID_TH = {
        product: @json(__('report::fields.product_name')),
        branch: @json(__('report::fields.establishment_name')),
        unit: @json(__('report::general.filter_panel_unit')),
        qty: @json(__('report::general.weekday_simple_grid_col_qty')),
        price: @json(__('report::general.weekday_simple_grid_col_price')),
    };

    function wsrFmtNum(n, frac) {
        if (n == null || isNaN(n)) {
            return '—';
        }
        return Number(n).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: frac != null ? frac : 2
        });
    }
    function wsrFmtQty(n) {
        return wsrFmtNum(n, 3);
    }
    function wsrBindDelta($el, pct) {
        $el.removeClass('text-success text-danger text-muted');
        if (pct == null || isNaN(pct)) {
            $el.text('—').addClass('text-muted');
            return;
        }
        $el.text((pct >= 0 ? '+' : '') + Number(pct).toFixed(2) + '%');
        if (pct > 0) {
            $el.addClass('text-success');
        } else if (pct < 0) {
            $el.addClass('text-danger');
        } else {
            $el.addClass('text-muted');
        }
    }
    function refreshWsrKpiAndCharts(kpi) {
        if (!kpi) {
            return;
        }
        const single = kpi.is_single;
        $('#wsrKpiCompareRow').toggleClass('d-none', single);
        $('#wsrKpiSingleRow').toggleClass('d-none', !single);

        if (single) {
            $('#wsrKpiSingleQty').text(wsrFmtQty(kpi.sum_qty_a));
            $('#wsrKpiSingleRev').text(wsrFmtNum(kpi.sum_subtotal_a, 2));
            $('#wsrKpiSingleLines').text(wsrFmtNum(kpi.sum_lines_a, 0));
            $('#wsrKpiSinglePeriodNote').text(kpi.period_a_label || '—');
            return;
        }

        const pa = kpi.period_a_label || '—';
        const pb = kpi.period_b_label || '—';
        $('#wsrKpiQtyALabel, #wsrKpiRevALabel').text(pa).attr('title', pa);
        $('#wsrKpiQtyBLabel, #wsrKpiRevBLabel').text(pb).attr('title', pb);
        $('#wsrKpiQtyA').text(wsrFmtQty(kpi.sum_qty_a));
        $('#wsrKpiQtyB').text(wsrFmtQty(kpi.sum_qty_b));
        $('#wsrKpiRevA').text(wsrFmtNum(kpi.sum_subtotal_a, 2));
        $('#wsrKpiRevB').text(wsrFmtNum(kpi.sum_subtotal_b, 2));
        wsrBindDelta($('#wsrDeltaQty'), kpi.qty_change_pct);
        wsrBindDelta($('#wsrDeltaRev'), kpi.revenue_change_pct);
    }

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
        $('#scWrap-branch').toggleClass('d-none', v.indexOf('branch') === -1);
        $('#scWrap-customer').toggleClass('d-none', v.indexOf('customer') === -1);
        $('#scWrap-product').toggleClass('d-none', v.indexOf('product') === -1);
        $('#scWrap-category').toggleClass('d-none', v.indexOf('category') === -1);
        $('#scWrap-subcategory').toggleClass('d-none', v.indexOf('subcategory') === -1);
        $('#scWrap-unit').toggleClass('d-none', v.indexOf('unit') === -1);
        $('#scWrap-payment').toggleClass('d-none', v.indexOf('payment') === -1);
    }

    function syncWsrPickDayWrap() {
        const scope = $('#wsrPeriodScope').val() || '';
        $('#wsrPickDayWrap').toggleClass('d-none', scope !== 'single_pick_day');
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
            weekday: $('.wsr-weekday:checked').map(function() { return $(this).val(); }).get().join(','),
            weekday_report_scope: $('#wsrPeriodScope').val() || 'single_this_month',
            wsr_pick_day: $('#wsrPickDay').val() || ''
        };
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

    function renderWsrSimpleGrid(json) {
        const dates = json.wsr_occurrence_dates || [];
        const rows = json.wsr_grid_rows || [];
        const $r1 = $('#wsrSimpleHeadRow1').empty();
        const $r2 = $('#wsrSimpleHeadRow2').empty();
        $r1.append($('<th/>', { rowspan: 2, class: 'text-start min-w-140px' }).text(WSR_GRID_TH.product));
        $r1.append($('<th/>', { rowspan: 2, class: 'text-start min-w-120px' }).text(WSR_GRID_TH.branch));
        $r1.append($('<th/>', { rowspan: 2, class: 'text-start min-w-100px' }).text(WSR_GRID_TH.unit));
        dates.forEach(function(dm) {
            $r1.append($('<th/>', { colspan: 2, class: 'text-center bg-light' }).text(dm.label || dm.date));
        });
        dates.forEach(function() {
            $r2.append($('<th/>', { class: 'text-end' }).text(WSR_GRID_TH.qty));
            $r2.append($('<th/>', { class: 'text-end' }).text(WSR_GRID_TH.price));
        });
        const $body = $('#wsrSimpleBody').empty();
        rows.forEach(function(row) {
            const $tr = $('<tr/>');
            $tr.append($('<td/>').text(row.product_name || '—'));
            $tr.append($('<td/>').text(row.establishment_name || '—'));
            $tr.append($('<td/>').text(row.unit_label || '—'));
            dates.forEach(function(dm) {
                const c = (row.cells && row.cells[dm.date]) ? row.cells[dm.date] : { qty: 0, unit_sale_price: null };
                $tr.append($('<td/>', { class: 'text-end' }).text(wsrFmtQty(c.qty)));
                const p = c.unit_sale_price;
                $tr.append($('<td/>', { class: 'text-end' }).text(p == null || isNaN(p) ? '—' : wsrFmtNum(p, 2)));
            });
            $body.append($tr);
        });
        const note = json.wsr_notice || '';
        $('#wsrSimpleNotice').toggleClass('d-none', !note).text(note);
    }

    function loadWsrReportData() {
        $.ajax({
            url: apiUrl,
            type: 'GET',
            data: getFilterParams(),
            traditional: true,
            success: function(json) {
                if (!json || typeof json !== 'object' || !json.wsr_simple_grid) {
                    return;
                }
                renderWsrSimpleGrid(json);
                wsrLastKpi = json.wsr_kpi || null;
                refreshWsrKpiAndCharts(json.wsr_kpi || null);
            },
            error: function() {
                alert(exportFailedMsg);
            }
        });
    }

    function bindWsrTableSearch() {
        $('#wsrTableSearch').off('keyup.wsrSimple input.wsrSimple').on('keyup.wsrSimple input.wsrSimple', function() {
            const q = ($(this).val() || '').toLowerCase().trim();
            $('#wsrSimpleBody tr').each(function() {
                const t = $(this).text().toLowerCase();
                $(this).toggle(q === '' || t.indexOf(q) !== -1);
            });
        });
    }

    function scTriggerFileExport(url) {
        const qs = $.param(getFilterParams());
        const fullUrl = url + (url.indexOf('?') >= 0 ? '&' : '?') + qs;
        fetch(fullUrl, {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/pdf, */*'
            }
        }).then(function(r) {
            const ct = (r.headers.get('Content-Type') || '').toLowerCase();
            if (r.status === 422 && ct.indexOf('json') !== -1) {
                return r.json().then(function(j) {
                    throw new Error(j.message || exportFailedMsg);
                });
            }
            if (!r.ok) {
                throw new Error(exportFailedMsg);
            }
            const cd = r.headers.get('Content-Disposition') || '';
            let filename = 'export';
            const mStar = /filename\*=UTF-8''([^;\s]+)/i.exec(cd);
            const mQuot = /filename="([^"]+)"/i.exec(cd);
            const mPlain = /filename=([^;\s]+)/i.exec(cd);
            if (mStar && mStar[1]) {
                try {
                    filename = decodeURIComponent(mStar[1].replace(/['"]/g, ''));
                } catch (e) {
                    filename = mStar[1];
                }
            } else if (mQuot && mQuot[1]) {
                filename = mQuot[1];
            } else if (mPlain && mPlain[1]) {
                filename = mPlain[1].replace(/['"]/g, '');
            }
            return r.blob().then(function(blob) {
                return { blob: blob, filename: filename };
            });
        }).then(function(x) {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(x.blob);
            a.download = x.filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function() {
                URL.revokeObjectURL(a.href);
            }, 600);
        }).catch(function(e) {
            console.error(e);
            alert(e.message || exportFailedMsg);
        });
    }

    $(document).ready(function() {
        scSelect2Multi($('#filterPanels'));
        scSelect2Single($('#wsrPeriodScope'));
        $('#filterPanels').on('change', syncFilterPanels);
        $('#wsrPeriodScope').on('change', function() {
            syncWsrPickDayWrap();
            loadWsrReportData();
        });
        $('#categoryFilter').on('change', function() {
            if (scRestoringFilters) return;
            populateSubcategories();
        });
        $('.sc-filter-multi').each(function() { scSelect2Multi($(this)); });
        syncWsrPickDayWrap();
        $.when(
            populateBranches(),
            populateCustomers(),
            populateProducts(),
            populateCategories(),
            populateUnits(),
            populatePaymentMethods()
        ).always(function() {
            scRestoringFilters = true;
            $('#filterPanels').val(['branch']).trigger('change');
            syncFilterPanels();
            $('#wsrPeriodScope').val('single_this_month').trigger('change.select2');
            populateSubcategories().always(function() {
                scRestoringFilters = false;
                bindWsrTableSearch();
                loadWsrReportData();
            });
        });
        $('#wsrPickDay').on('change', function() {
            if (($('#wsrPeriodScope').val() || '') === 'single_pick_day') {
                loadWsrReportData();
            }
        });
        $('#wsrWeekdaysAll').on('click', function() {
            $('.wsr-weekday').prop('checked', true);
            loadWsrReportData();
        });
        $('#wsrWeekdaysNone').on('click', function() {
            $('.wsr-weekday').prop('checked', false);
            loadWsrReportData();
        });
        $('#applyFilter').on('click', function() {
            loadWsrReportData();
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
            $('#filterPanels').val(['branch']).trigger('change');
            syncFilterPanels();
            $('#wsrPeriodScope').val('single_this_month').trigger('change.select2');
            syncWsrPickDayWrap();
            $('.wsr-weekday').prop('checked', true);
            $('#wsrTableSearch').val('');
            $('#wsrPickDay').val(@json(\Carbon\Carbon::now()->format('Y-m-d')));
            loadWsrReportData();
        });
        $('#wsrExportExcel').on('click', function() {
            scTriggerFileExport(exportExcelUrl);
        });
        $('#wsrExportPdf').on('click', function() {
            scTriggerFileExport(exportPdfUrl);
        });
    });
</script>
@stop
