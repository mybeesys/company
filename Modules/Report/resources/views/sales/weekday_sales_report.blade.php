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
                <div class="card-toolbar d-flex flex-row align-items-center sc-export-toolbar">
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
                            </div>

                            <div class="row g-4 mt-1">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">@lang('report::general.weekday_report_scope_label')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_scope_hint')</div>
                                    <div class="d-flex flex-wrap gap-4">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="weekday_report_scope" id="wsrScopeCompareCmLm" value="compare_this_vs_last_month" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrScopeCompareCmLm">@lang('report::general.weekday_report_scope_compare_cm_lm')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="weekday_report_scope" id="wsrScopeSingleThis" value="single_this_month" checked />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrScopeSingleThis">@lang('report::general.weekday_report_scope_single_this')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="weekday_report_scope" id="wsrScopeSingleLast" value="single_last_month" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrScopeSingleLast">@lang('report::general.weekday_report_scope_single_last')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="weekday_report_scope" id="wsrScopeCustom" value="custom_periods" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrScopeCustom">@lang('report::general.weekday_report_scope_custom')</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mt-1">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">@lang('report::general.weekday_report_view_label')</label>
                                    <div class="text-muted fs-8 mb-2">@lang('report::general.weekday_report_view_hint')</div>
                                    <div class="d-flex flex-wrap gap-4">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="wsr_view" id="wsrViewByProduct" value="by_product" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrViewByProduct">@lang('report::general.weekday_report_view_by_product')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="wsr_view" id="wsrViewByDay" value="by_day" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrViewByDay">@lang('report::general.weekday_report_view_by_day')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="wsr_view" id="wsrViewByDate" value="by_date" />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrViewByDate">@lang('report::general.weekday_report_view_by_date')</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="wsr_view" id="wsrViewByDateProduct" value="by_date_product" checked />
                                            <label class="form-check-label fw-semibold cursor-pointer" for="wsrViewByDateProduct">@lang('report::general.weekday_report_view_by_date_product')</label>
                                        </div>
                                    </div>
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
                                    <div class="alert alert-light border py-3 d-none mb-4" id="wsrAutoPeriodNote" role="status">
                                        <div class="fs-7 text-gray-800">@lang('report::general.weekday_report_scope_auto_periods_note')</div>
                                    </div>
                                    <div class="row g-4 wsr-period-manual-fields">
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
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="wsr-kpi-card wsr-kpi-card--p1 rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_revenue')</div>
                            <div class="fs-9 text-muted mb-1 text-truncate" id="wsrKpiRevALabel" title="">—</div>
                            <div class="wsr-kpi-value" id="wsrKpiRevA">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
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
                    <div class="col-12 col-md-4">
                        <div class="wsr-kpi-card wsr-kpi-card--single rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100 text-center text-md-start">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_revenue')</div>
                            <div class="wsr-kpi-value" id="wsrKpiSingleRev">—</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="wsr-kpi-card wsr-kpi-card--single rounded-4 border border-gray-200 bg-white p-5 shadow-sm h-100 text-center text-md-start">
                            <div class="fs-8 fw-semibold text-gray-500 text-uppercase mb-3">@lang('report::general.weekday_report_kpi_lines')</div>
                            <div class="wsr-kpi-value" id="wsrKpiSingleLines">—</div>
                        </div>
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
                                <th colspan="6" class="sc-gh-p1 py-3" id="wsrHeadP1">@lang('report::general.sales_comparison_group_period_a')</th>
                                <th colspan="6" class="sc-gh-p2 py-3" id="wsrHeadP2">@lang('report::general.sales_comparison_group_period_b')</th>
                                <th colspan="7" class="sc-gh-var py-3" id="wsrHeadVar">@lang('report::general.sales_comparison_group_variance')</th>
                            </tr>
                            <tr class="text-gray-700">
                                <th class="text-start min-w-90px">@lang('report::fields.qty_period_a')</th>
                                <th class="text-start min-w-110px">@lang('report::fields.avg_unit_price_period_a')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.discount_period_a')</th>
                                <th class="text-start min-w-90px">@lang('report::fields.tax_period_a')</th>
                                <th class="text-start min-w-100px">@lang('report::fields.subtotal_period_a')</th>
                                <th class="text-start min-w-80px">@lang('report::fields.lines_period_a')</th>
                                <th class="text-start min-w-90px wsr-th-p2">@lang('report::fields.qty_period_b')</th>
                                <th class="text-start min-w-110px wsr-th-p2">@lang('report::fields.avg_unit_price_period_b')</th>
                                <th class="text-start min-w-90px wsr-th-p2">@lang('report::fields.discount_period_b')</th>
                                <th class="text-start min-w-90px wsr-th-p2">@lang('report::fields.tax_period_b')</th>
                                <th class="text-start min-w-100px wsr-th-p2">@lang('report::fields.subtotal_period_b')</th>
                                <th class="text-start min-w-80px wsr-th-p2">@lang('report::fields.lines_period_b')</th>
                                <th class="text-start min-w-90px wsr-th-var">@lang('report::fields.qty_difference')</th>
                                <th class="text-start min-w-90px wsr-th-var">@lang('report::fields.qty_change_percent')</th>
                                <th class="text-start min-w-100px wsr-th-var">@lang('report::fields.subtotal_difference')</th>
                                <th class="text-start min-w-90px wsr-th-var">@lang('report::fields.subtotal_change_percent')</th>
                                <th class="text-start min-w-90px wsr-th-var">@lang('report::fields.discount_difference')</th>
                                <th class="text-start min-w-90px wsr-th-var">@lang('report::fields.tax_difference')</th>
                                <th class="text-start min-w-80px wsr-th-var">@lang('report::fields.lines_difference')</th>
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
    let wsrLastKpi = null;
    let scLastFooterTotals = null;
    let scRestoringFilters = false;
    const SC_FOOTER_KEYS = @json($wsrFooterKeys);

    const table = $('#kt_WeekdaySales_table');
    const apiUrl = "{{ route('weekday-sales-report') }}";
    const exportExcelUrl = "{{ route('weekday-sales-export-excel') }}";
    const exportPdfUrl = "{{ route('weekday-sales-export-pdf') }}";
    const exportFailedMsg = @json(__('report::general.export_failed_message'));
    const comparisonCategoriesUrl = "{{ route('comparison-categories') }}";
    const comparisonSubcategoriesUrl = "{{ route('comparison-subcategories') }}";
    const comparisonUnitsUrl = "{{ route('comparison-units') }}";
    const comparisonPaymentMethodsUrl = "{{ route('comparison-payment-methods') }}";
    const WSR_LABELS = {
        periodA: @json(__('report::general.sales_comparison_group_period_a')),
        periodB: @json(__('report::general.sales_comparison_group_period_b')),
        variance: @json(__('report::general.sales_comparison_group_variance')),
        singleThis: @json(__('report::general.weekday_report_header_single_this_month')),
        singleLast: @json(__('report::general.weekday_report_header_single_last_month')),
        kpiQty: @json(__('report::general.weekday_report_kpi_qty')),
        kpiRevenue: @json(__('report::general.weekday_report_kpi_revenue')),
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

    function applyWsrViewMode(viewMode) {
        const isByDay = viewMode === 'by_day' || viewMode === 'by_date';
        const isByDateProduct = viewMode === 'by_date_product';
        if (dataTable) {
            // 0 is the "product_name" column which we reuse to show the weekday label.
            // Hide the rest of context columns when grouping by day.
            dataTable.column(0).visible(true, false);
            if (isByDay) {
                for (let i = 1; i <= 5; i++) {
                    dataTable.column(i).visible(false, false);
                }
            } else if (isByDateProduct) {
                // show: date (col 1), branch (col 3), SKU (col 4); hide subcategory and customer
                dataTable.column(1).visible(true, false);
                dataTable.column(2).visible(false, false);
                dataTable.column(3).visible(true, false);
                dataTable.column(4).visible(true, false);
                dataTable.column(5).visible(false, false);
            } else {
                for (let i = 1; i <= 5; i++) {
                    dataTable.column(i).visible(true, false);
                }
            }
            dataTable.columns.adjust().draw(false);
        }

        // Header relabels for special views.
        const $h1 = $('#kt_WeekdaySales_table thead tr').first().find('th');
        if ($h1 && $h1.length >= 2) {
            if (viewMode === 'by_day') {
                $h1.eq(0).text(@json(__('report::general.weekday_report_column_day')));
            } else if (viewMode === 'by_date' || viewMode === 'by_date_product') {
                $h1.eq(0).text(@json(__('report::fields.transaction_date')));
            } else {
                $h1.eq(0).text(@json(__('report::fields.product_name')));
            }
            if (viewMode === 'by_date_product') {
                $h1.eq(1).text(@json(__('report::fields.transaction_date')));
            } else {
                $h1.eq(1).text(@json(__('report::fields.category')));
            }
        }
    }

    function applyWsrTableLayout(mode) {
        const single = mode === 'single';
        if (dataTable) {
            for (let i = 12; i <= 24; i++) {
                dataTable.column(i).visible(!single, false);
            }
            dataTable.columns.adjust().draw(false);
        }
        const scope = $('input[name="weekday_report_scope"]:checked').val() || '';
        $('#wsrHeadP2, #wsrHeadVar').toggleClass('d-none', single);
        $('.wsr-th-p2, .wsr-th-var').toggleClass('d-none', single);
        if (single) {
            $('#wsrHeadP1').text(scope === 'single_this_month' ? WSR_LABELS.singleThis : WSR_LABELS.singleLast);
        } else {
            $('#wsrHeadP1').text(WSR_LABELS.periodA);
            $('#wsrHeadP2').text(WSR_LABELS.periodB);
            $('#wsrHeadVar').text(WSR_LABELS.variance);
        }
    }

    function syncWsrPeriodPanelLock() {
        const scope = $('input[name="weekday_report_scope"]:checked').val() || 'compare_this_vs_last_month';
        const custom = scope === 'custom_periods';
        const fp = $('#filterPanels').val() || [];
        const periodsPanelOn = fp.indexOf('periods') !== -1;
        const locked = !custom && periodsPanelOn;
        $('#wsrAutoPeriodNote').toggleClass('d-none', !locked);
        $('.wsr-period-manual-fields').toggleClass('d-none', locked);
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
        $('#scWrap-quick').toggleClass('d-none', v.indexOf('quick') === -1);
        $('#scWrap-branch').toggleClass('d-none', v.indexOf('branch') === -1);
        $('#scWrap-customer').toggleClass('d-none', v.indexOf('customer') === -1);
        $('#scWrap-product').toggleClass('d-none', v.indexOf('product') === -1);
        $('#scWrap-category').toggleClass('d-none', v.indexOf('category') === -1);
        $('#scWrap-subcategory').toggleClass('d-none', v.indexOf('subcategory') === -1);
        $('#scWrap-unit').toggleClass('d-none', v.indexOf('unit') === -1);
        $('#scWrap-payment').toggleClass('d-none', v.indexOf('payment') === -1);
        $('#scWrap-periods').toggleClass('d-none', v.indexOf('periods') === -1);
        syncWsrPeriodPanelLock();
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
            // Comma-separated: jQuery DataTables uses traditional: true so arrays become weekday=1&weekday=2
            // and PHP keeps only the last value. CSV avoids that while staying backward-compatible in PHP.
            weekday: $('.wsr-weekday:checked').map(function() { return $(this).val(); }).get().join(','),
            weekday_report_scope: $('input[name="weekday_report_scope"]:checked').val() || 'single_this_month',
            wsr_view: $('input[name="wsr_view"]:checked').val() || 'by_date_product'
        };
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
                    const mode = json.wsr_table_mode || 'full';
                    const viewMode = json.wsr_view_mode || ($('input[name="wsr_view"]:checked').val() || 'by_product');
                    const kpi = json.wsr_kpi || null;
                    wsrLastKpi = kpi;
                    setTimeout(function() {
                        applyWsrTableLayout(mode);
                        applyWsrViewMode(viewMode);
                        refreshWsrKpiAndCharts(kpi);
                    }, 0);
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
                syncWsrPeriodPanelLock();
            });
        });
        $('input[name="weekday_report_scope"]').on('change', function() {
            syncWsrPeriodPanelLock();
            if (dataTable) {
                dataTable.ajax.reload();
            }
        });
        $('input[name="wsr_view"]').on('change', function() {
            if (dataTable) {
                dataTable.ajax.reload();
            }
        });
        $('#wsrWeekdaysAll').on('click', function() {
            $('.wsr-weekday').prop('checked', true);
            if (dataTable) dataTable.ajax.reload();
        });
        $('#wsrWeekdaysNone').on('click', function() {
            $('.wsr-weekday').prop('checked', false);
            if (dataTable) dataTable.ajax.reload();
        });
        $('#applyFilter').on('click', function() {
            if (dataTable) dataTable.ajax.reload();
        });
        $('#wsrExportExcel').on('click', function() {
            scTriggerFileExport(exportExcelUrl);
        });
        $('#wsrExportPdf').on('click', function() {
            scTriggerFileExport(exportPdfUrl);
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
            $('#wsrScopeCompareCmLm').prop('checked', true);
            $('#wsrScopeSingleThis').prop('checked', true);
            $('#wsrViewByDateProduct').prop('checked', true);
            syncWsrPeriodPanelLock();
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
