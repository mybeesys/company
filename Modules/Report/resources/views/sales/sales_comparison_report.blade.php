@extends('layouts.app')

@php
    use Modules\Report\Utils\SalesComparisonPeriodResolver;

    $scColumnPickerContextKeys = [
        'product_name', 'category', 'subcategory', 'establishment_name', 'SKU', 'customer',
    ];

    /** One UI toggle per metric: toggles Period A + Period B + related variance column(s). Indices match DataTable columns order. */
    $scColumnPickerGroups = [
        ['id' => 'qty', 'label_key' => 'sales_comparison_col_group_qty', 'indices' => [6, 12, 18, 19]],
        ['id' => 'avg_unit_price', 'label_key' => 'sales_comparison_col_group_avg_unit_price', 'indices' => [7, 13]],
        ['id' => 'discount', 'label_key' => 'sales_comparison_col_group_discount', 'indices' => [8, 14, 22]],
        ['id' => 'tax', 'label_key' => 'sales_comparison_col_group_tax', 'indices' => [9, 15, 23]],
        ['id' => 'line_total', 'label_key' => 'sales_comparison_col_group_line_total', 'indices' => [10, 16, 20, 21]],
        ['id' => 'line_count', 'label_key' => 'sales_comparison_col_group_line_count', 'indices' => [11, 17, 24]],
    ];

    $scColumnGroupsForJs = array_values(array_map(static function (array $g): array {
        return ['id' => $g['id'], 'indices' => $g['indices']];
    }, $scColumnPickerGroups));

    $scColumnPickerKeys = array_merge(
        $scColumnPickerContextKeys,
        [
            'qty_period_a', 'avg_unit_price_period_a', 'discount_period_a', 'tax_period_a', 'subtotal_period_a', 'lines_period_a',
            'qty_period_b', 'avg_unit_price_period_b', 'discount_period_b', 'tax_period_b', 'subtotal_period_b', 'lines_period_b',
            'qty_difference', 'qty_change_percent', 'subtotal_difference', 'subtotal_change_percent', 'discount_difference', 'tax_difference', 'lines_difference',
        ]
    );
@endphp

@section('title', __('menuItemLang.sales-comparison-report'))

@section('css')
<style>
    .dropend .dropdown-toggle::after {
        border-left: 0;
        border-right: 0;
    }

    .select2-container .select2-selection--multiple {
        height: auto !important;
        min-height: 44px;
    }

    .select2-container .select2-selection--multiple .select2-selection__rendered {
        white-space: normal !important;
    }

    .sc-table-wrap {
        border-radius: 12px;
        border: 1px solid #e9edf3;
    }

    .sc-table thead tr.sc-h1 th {
        vertical-align: middle;
    }

    .sc-table thead .sc-gh-context {
        background: #eef1f5 !important;
        color: #1f2937;
    }

    .sc-table thead .sc-gh-p1 {
        background: #bfdbfe !important;
        color: #1e3a8a;
        text-align: center;
    }

    .sc-table thead .sc-gh-p2 {
        background: #fed7aa !important;
        color: #7c2d12;
        text-align: center;
    }

    .sc-table thead .sc-gh-var {
        background: #ddd6fe !important;
        color: #4c1d95;
        text-align: center;
    }

    .sc-table thead tr.sc-h2 th {
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .sc-table .sc-cell-dim {
        background: rgba(249, 250, 251, 0.95);
    }

    .sc-table .sc-cell-p1 {
        background: rgba(219, 234, 254, 0.45);
    }

    .sc-table .sc-cell-p2 {
        background: rgba(255, 237, 213, 0.45);
    }

    .sc-table .sc-cell-var {
        background: rgba(237, 233, 254, 0.45);
    }

    .sc-table tbody td.sc-diff-up {
        font-weight: 600;
        color: #0f5132;
    }

    .sc-table tbody td.sc-diff-down {
        font-weight: 600;
        color: #842029;
    }

    .sc-filters-card {
        background: #f8f9fb;
        border-radius: 12px;
        border: 1px solid #e9edf3 !important;
    }

    .sc-filters-card .card-body {
        background: transparent;
    }

    .sc-filter-field {
        min-height: 100%;
    }

    /* Select2 Bootstrap5 theme (Metronic): border + white field so it doesn't merge with card #f8f9fb */
    .sc-filters-card .select2-container--bootstrap5 {
        width: 100% !important;
    }

    .sc-filters-card .select2-container--bootstrap5 .select2-selection.form-select-solid,
    .sc-filters-card .select2-container--bootstrap5 .select2-selection--single.form-select-solid,
    .sc-filters-card .select2-container--bootstrap5 .select2-selection--multiple.form-select-solid {
        border: 1px solid #c5cdd8 !important;
        background-color: #ffffff !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06) !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }

    .sc-filters-card .select2-container--bootstrap5.select2-container--focus:not(.select2-container--disabled) .form-select-solid,
    .sc-filters-card .select2-container--bootstrap5.select2-container--open:not(.select2-container--disabled) .form-select-solid {
        border-color: #7c8aa1 !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(0, 158, 247, 0.14) !important;
    }

    .sc-filters-card .select2-container--bootstrap5 .select2-selection--multiple .select2-selection__rendered .select2-selection__choice {
        background-color: #eef2f7 !important;
        border: 1px solid #d0d8e4 !important;
    }

    #scChartsSection {
        overflow: hidden;
    }

    .sc-chart-slot {
        height: 240px;
        max-height: 240px;
        position: relative;
        width: 100%;
    }

    .sc-chart-slot canvas {
        display: block !important;
        width: 100% !important;
        height: 240px !important;
        max-height: 240px !important;
    }

    .sc-table-toolbar {
        margin-top: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .sc-table-search-pill {
        width: 100%;
        max-width: 680px;
        flex: 1 1 auto;
        min-height: 0;
        border: 1px solid #e1e4ea;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .sc-table-search-pill:focus-within {
        border-color: #009ef7;
        box-shadow: 0 0 0 3px rgba(0, 158, 247, 0.12);
        background: #fff;
    }

    .sc-table-search-pill .sc-table-search-pill__icon {
        font-size: 1rem;
        line-height: 1;
        color: #7e8299;
        margin-inline-start: 0.15rem;
    }

    .sc-table-search-pill input.form-control {
        height: 34px;
        min-height: 34px;
        padding: 0.2rem 0.5rem 0.2rem 0.15rem;
        font-size: 0.8125rem;
        line-height: 1.35;
    }

    [dir="rtl"] .sc-table-search-pill input.form-control {
        padding: 0.2rem 0.15rem 0.2rem 0.5rem;
    }

    .sc-table-search-pill input::placeholder {
        color: #a1a5b7;
    }

    .sc-export-toolbar {
        gap: 0.65rem;
    }

    .sc-export-toolbar__label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: #7e8299;
        text-transform: uppercase;
    }

    [dir="rtl"] .sc-export-toolbar__label {
        letter-spacing: 0;
    }

    .sc-export-btn-group {
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        border-radius: 10px;
        overflow: hidden;
    }

    .sc-export-btn {
        font-weight: 600;
        font-size: 0.8125rem;
        padding: 0.5rem 1rem;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background 0.15s ease, color 0.15s ease, filter 0.15s ease;
    }

    .sc-export-btn:focus {
        box-shadow: none;
    }

    .sc-export-btn--excel {
        color: #0d4f2d;
        background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
        border-right: 1px solid rgba(16, 185, 129, 0.35);
    }

    [dir="rtl"] .sc-export-btn--excel {
        border-right: none;
        border-left: 1px solid rgba(16, 185, 129, 0.35);
    }

    .sc-export-btn--excel:hover {
        color: #064e3b;
        filter: brightness(0.97);
    }

    .sc-export-btn--pdf {
        color: #7f1d1d;
        background: linear-gradient(180deg, #fff5f5 0%, #fee2e2 100%);
    }

    .sc-export-btn--pdf:hover {
        color: #450a0a;
        filter: brightness(0.97);
    }

    .sc-export-btn .sc-export-btn__icon {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .sc-table tfoot tr.sc-footer-totals td {
        font-weight: 700;
        font-size: 0.8125rem;
        border-top: 2px solid #e9edf3;
        white-space: nowrap;
    }
</style>
@stop

@section('content')

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">

        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-3 gap-md-5 flex-wrap">
                <div class="card-title flex-grow-1 me-2">
                    <div class="d-flex align-items-center position-relative my-1">
                        <h3 class="mb-0">@lang('menuItemLang.sales-comparison-report')</h3>
                    </div>
                </div>

                <div class="card-toolbar d-flex flex-row align-items-center sc-export-toolbar">
                    <span class="sc-export-toolbar__label d-none d-sm-inline">@lang('report::general.export_actions_label')</span>
                    <div class="btn-group sc-export-btn-group" role="group" aria-label="@lang('report::general.export_actions_label')">
                        <button type="button"
                            class="btn sc-export-btn sc-export-btn--excel"
                            id="scExportExcel"
                            title="@lang('report::general.export_excel_hint')">
                            <i class="bi bi-file-earmark-spreadsheet sc-export-btn__icon" aria-hidden="true"></i>
                            <span>@lang('report::general.export_excel_btn')</span>
                        </button>
                        <button type="button"
                            class="btn sc-export-btn sc-export-btn--pdf"
                            id="scExportPdf"
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
                                <div class="col-12 col-sm-6 col-xl-3 sc-filter-field">
                                    <div class="form-check form-switch form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input" type="checkbox" id="showChartsToggle" value="1" checked />
                                        <label class="form-check-label fw-semibold ms-3" for="showChartsToggle">@lang('report::general.sales_comparison_show_charts')</label>
                                    </div>
                                    <div class="text-muted fs-8">@lang('report::general.sales_comparison_charts_help')</div>
                                </div>
                                <div class="col-12 col-xl-6 d-flex flex-wrap justify-content-xl-end align-items-end gap-2 pt-2 pt-xl-0 sc-filter-field">
                                    <button type="button" class="btn btn-primary" id="applyFilter">
                                        <i class="bi bi-funnel fs-2"></i> @lang('report::general.Apply Filter')
                                    </button>
                                    <button type="button" class="btn btn-warning" id="clearFilter">@lang('report::general.Remove filter')</button>
                                </div>
                            </div>

                            <div id="scFilterPanelsHost" class="row g-4 mt-2 align-items-stretch">
                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-quick">
                                    <div id="scPanel-quick" data-sc-panel="quick">
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
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-branch">
                                    <div id="scPanel-branch" data-sc-panel="branch">
                                        <label for="branchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="branchFilter" name="branch_id[]" data-placeholder="@lang('report::general.All Branches')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-customer">
                                    <div id="scPanel-customer" data-sc-panel="customer">
                                        <label for="customerFilter" class="form-label">@lang('report::purchase.Customer')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="customerFilter" name="customer_id[]" data-placeholder="@lang('report::purchase.All Customer')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-product">
                                    <div id="scPanel-product" data-sc-panel="product">
                                        <label for="productFilter" class="form-label">@lang('report::purchase.Product')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="productFilter" name="product_id[]" data-placeholder="@lang('report::purchase.All Products')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-category">
                                    <div id="scPanel-category" data-sc-panel="category">
                                        <label for="categoryFilter" class="form-label">@lang('report::general.filter_panel_category')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="categoryFilter" name="category_id[]" data-placeholder="@lang('report::general.All_categories')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-subcategory">
                                    <div id="scPanel-subcategory" data-sc-panel="subcategory">
                                        <label for="subcategoryFilter" class="form-label">@lang('report::general.filter_panel_subcategory')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="subcategoryFilter" name="subcategory_id[]" data-placeholder="@lang('report::general.All_subcategories')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-unit">
                                    <div id="scPanel-unit" data-sc-panel="unit">
                                        <label for="unitFilter" class="form-label">@lang('report::general.filter_panel_unit')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="unitFilter" name="unit_id[]" data-placeholder="@lang('report::general.All_units')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6 col-xl-3 d-none sc-filter-field" id="scWrap-payment">
                                    <div id="scPanel-payment" data-sc-panel="payment">
                                        <label for="paymentMethodFilter" class="form-label">@lang('report::purchase.Payment Method')</label>
                                        <select class="form-select form-select-solid sc-filter-multi" id="paymentMethodFilter" name="payment_method[]" data-placeholder="@lang('report::general.All Methods')" multiple>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 sc-filter-field" id="scWrap-periods">
                                    <div id="scPanel-periods" data-sc-panel="periods">
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
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="scChartsSection" class="card-body border-top py-4 px-5">
                <div class="row g-4 g-xl-5">
                    <div class="col-12 col-xl-6">
                        <h4 class="fs-6 fw-bold mb-3">@lang('report::general.sales_comparison_chart_totals')</h4>
                        <div class="sc-chart-slot">
                            <canvas id="scChartTotals" aria-label="totals"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <h4 class="fs-6 fw-bold mb-3">@lang('report::general.sales_comparison_chart_top_products')</h4>
                        <div class="sc-chart-slot">
                            <canvas id="scChartProducts" aria-label="products"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <x-cards.card-body class="sc-table-wrap sc-table-body px-5 pb-5 pt-8">
                <div class="sc-table-toolbar d-flex flex-wrap align-items-center gap-2">
                    <div class="sc-table-search-pill d-flex align-items-stretch gap-1 ps-2 pe-1 py-0">
                        <i class="ki-outline ki-magnifier sc-table-search-pill__icon align-self-center flex-shrink-0" aria-hidden="true"></i>
                        <input type="text"
                            id="scTableSearch"
                            data-kt-filter="search"
                            class="form-control border-0 bg-transparent shadow-none"
                            placeholder="@lang('report::general.SalesComparison_search')"
                            autocomplete="off" />
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2 flex-shrink-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon sc-gear-btn" type="button"
                                id="scColumnPickerToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="@lang('report::general.sales_comparison_table_columns_hint')">
                                <i class="bi bi-gear-fill fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg" id="scColumnPickerMenu"
                                aria-labelledby="scColumnPickerToggle">
                                <div class="fw-bold mb-3">@lang('report::general.sales_comparison_table_columns')</div>
                                <div class="text-muted fs-8 mb-3">@lang('report::general.sales_comparison_table_columns_hint')</div>
                                <div class="text-uppercase text-muted fs-9 fw-bold mb-2 mt-1">@lang('report::general.sales_comparison_col_section_context')</div>
                                @foreach ($scColumnPickerContextKeys as $colKey)
                                    <div class="form-check form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input sc-col-toggle" type="checkbox"
                                            id="sc_col_{{ $colKey }}"
                                            data-sc-idx="{{ $loop->index }}"
                                            data-sc-key="{{ $colKey }}"
                                            checked />
                                        <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="sc_col_{{ $colKey }}">
                                            @lang('report::fields.' . $colKey)
                                        </label>
                                    </div>
                                @endforeach
                                <div class="text-uppercase text-muted fs-9 fw-bold mb-2 mt-4">@lang('report::general.sales_comparison_col_section_metrics')</div>
                                @foreach ($scColumnPickerGroups as $g)
                                    <div class="form-check form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input sc-col-toggle" type="checkbox"
                                            id="sc_col_grp_{{ $g['id'] }}"
                                            data-sc-group="{{ $g['id'] }}"
                                            checked />
                                        <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="sc_col_grp_{{ $g['id'] }}">
                                            @lang('report::general.' . $g['label_key'])
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table align-middle table-striped table-row-bordered fs-6 gy-5 sc-table" id="kt_SalesComparison_table" style="width:100%">
                    <thead>
                        <tr class="text-gray-800 fw-bold gs-0 sc-h1">
                            <th rowspan="2" class="text-start min-w-150px sc-gh-context px-2">@lang('report::fields.product_name')</th>
                            <th rowspan="2" class="text-start min-w-100px sc-gh-context px-2">@lang('report::fields.category')</th>
                            <th rowspan="2" class="text-start min-w-120px sc-gh-context px-2">@lang('report::fields.subcategory')</th>
                            <th rowspan="2" class="text-start min-w-130px sc-gh-context px-2">@lang('report::fields.establishment_name')</th>
                            <th rowspan="2" class="text-start min-w-90px sc-gh-context px-2">@lang('report::fields.SKU')</th>
                            <th rowspan="2" class="text-start min-w-130px sc-gh-context px-2">@lang('report::fields.customer')</th>
                            <th colspan="6" class="sc-gh-p1 py-3">@lang('report::general.sales_comparison_group_period_a')</th>
                            <th colspan="6" class="sc-gh-p2 py-3">@lang('report::general.sales_comparison_group_period_b')</th>
                            <th colspan="7" class="sc-gh-var py-3">@lang('report::general.sales_comparison_group_variance')</th>
                        </tr>
                        <tr class="text-gray-700 sc-h2">
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
    let chartTotals = null;
    let chartProducts = null;
    let chartRefreshTimer = null;
    let scLastFooterTotals = null;
    let scRestoringFilters = false;
    let scPendingTableSearch = '';
    const SC_FILTER_STORAGE_KEY = 'salesComparisonReport:v1';
    const SC_COLUMN_VISIBILITY_STORAGE_KEY = 'salesComparisonReport:columns:v2';
    const SC_FOOTER_KEYS = @json($scColumnPickerKeys);
    const SC_CONTEXT_KEYS = @json($scColumnPickerContextKeys);
    const SC_COLUMN_GROUPS = @json($scColumnGroupsForJs);

    const table = $('#kt_SalesComparison_table');
    const apiUrl = "{{ route('sales-comparison-report') }}";
    const chartUrl = "{{ route('sales-comparison-chart-data') }}";
    const comparisonCategoriesUrl = "{{ route('comparison-categories') }}";
    const comparisonSubcategoriesUrl = "{{ route('comparison-subcategories') }}";
    const comparisonUnitsUrl = "{{ route('comparison-units') }}";
    const comparisonPaymentMethodsUrl = "{{ route('comparison-payment-methods') }}";
    const exportExcelUrl = "{{ route('sales-comparison-export-excel') }}";
    const exportPdfUrl = "{{ route('sales-comparison-export-pdf') }}";
    const exportFailedMsg = @json(__('report::general.export_failed_message'));

    const str = {
        p1: @json(__('report::general.sales_comparison_group_period_a')),
        p2: @json(__('report::general.sales_comparison_group_period_b')),
        qty: @json(__('report::fields.sell_qty')),
        revenue: @json(__('report::fields.subtotal')),
        tax: @json(__('report::fields.tax_value')),
        discount: @json(__('report::fields.discount_amount')),
        lines: @json(__('report::general.chart_metric_line_count')),
    };

    function scSelect2Single($el) {
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
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
        if ($el.data('select2')) {
            $el.select2('destroy');
        }
        $el.select2({
            width: '100%',
            placeholder: ph,
            closeOnSelect: false
        });
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
        setTimeout(function() {
            $(window).trigger('resize');
        }, 100);
    }

    function toggleCustomRanges() {
        const showA = $('#periodAPreset').val() === 'custom';
        const showB = $('#periodBPreset').val() === 'custom';
        $('#periodACustomWrap').toggleClass('d-none', !showA);
        $('#periodBCustomWrap').toggleClass('d-none', !showB);
    }

    function bindRangePicker(selector) {
        $(selector).daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });
        $(selector).on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });
        $(selector).on('cancel.daterangepicker', function() {
            $(this).val('');
        });
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
            period_b_range: $('#periodBRange').val()
        };
    }

    function syncScChartsSectionVisibility() {
        const on = $('#showChartsToggle').is(':checked');
        $('#scChartsSection').toggleClass('d-none', !on);
    }

    function scLoadColumnVisibility() {
        try {
            return JSON.parse(localStorage.getItem(SC_COLUMN_VISIBILITY_STORAGE_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function scSaveColumnVisibility() {
        if (!dataTable) return;
        const state = {};
        SC_CONTEXT_KEYS.forEach(function(key, idx) {
            state[key] = dataTable.column(idx).visible();
        });
        SC_COLUMN_GROUPS.forEach(function(g) {
            state[g.id] = g.indices.every(function(i) {
                return dataTable.column(i).visible();
            });
        });
        try {
            localStorage.setItem(SC_COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function scCountVisibleDataColumns() {
        let n = 0;
        for (let i = 0; i < SC_FOOTER_KEYS.length; i++) {
            if (dataTable.column(i).visible()) n++;
        }
        return n;
    }

    function applyScColumnVisibilityFromStorage() {
        if (!dataTable) return;
        const state = scLoadColumnVisibility();
        SC_CONTEXT_KEYS.forEach(function(key, idx) {
            const visible = state[key] !== false;
            dataTable.column(idx).visible(visible, false);
            $('#sc_col_' + key).prop('checked', visible);
        });
        SC_COLUMN_GROUPS.forEach(function(g) {
            const visible = state[g.id] !== false;
            g.indices.forEach(function(i) {
                dataTable.column(i).visible(visible, false);
            });
            $('#sc_col_grp_' + g.id).prop('checked', visible);
        });
        if (scCountVisibleDataColumns() === 0) {
            SC_FOOTER_KEYS.forEach(function(_, idx) {
                dataTable.column(idx).visible(true, false);
            });
            $('#scColumnPickerMenu .sc-col-toggle').prop('checked', true);
            scSaveColumnVisibility();
        }
    }

    function saveSalesComparisonFilterState() {
        try {
            const state = Object.assign({}, getFilterParams(), {
                filterPanels: $('#filterPanels').val() || [],
                quickComparison: $('#quickComparison').val() || '',
                showCharts: $('#showChartsToggle').is(':checked'),
                scTableSearch: ($('#scTableSearch').val() || '').trim()
            });
            localStorage.setItem(SC_FILTER_STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            console.warn('sales comparison filter save failed', e);
        }
    }

    function restoreSalesComparisonFilterStateOrDefaults() {
        let state = null;
        try {
            const raw = localStorage.getItem(SC_FILTER_STORAGE_KEY);
            if (raw) {
                state = JSON.parse(raw);
            }
        } catch (e) {
            state = null;
        }
        if (!state || typeof state !== 'object') {
            scPendingTableSearch = '';
            setDefaultPeriods();
            $('#showChartsToggle').prop('checked', true);
            syncScChartsSectionVisibility();
            return $.Deferred().resolve().promise();
        }

        scPendingTableSearch = (state.scTableSearch || '').trim();
        scRestoringFilters = true;
        $('#filterPanels').val(state.filterPanels && state.filterPanels.length ? state.filterPanels : ['periods']).trigger('change');
        $('#periodAPreset').val(state.period_a_preset || 'last_month').trigger('change.select2');
        $('#periodBPreset').val(state.period_b_preset || 'this_month').trigger('change.select2');
        $('#periodARange').val(state.period_a_range || '');
        $('#periodBRange').val(state.period_b_range || '');
        $('#quickComparison').val(state.quickComparison || '').trigger('change.select2');
        $('#branchFilter').val(state.branch_id || null).trigger('change');
        $('#customerFilter').val(state.customer_id || null).trigger('change');
        $('#productFilter').val(state.product_id || null).trigger('change');
        $('#categoryFilter').val(state.category_id || null).trigger('change');
        $('#unitFilter').val(state.unit_id || null).trigger('change');
        $('#paymentMethodFilter').val(state.payment_method || null).trigger('change');
        $('#showChartsToggle').prop('checked', state.showCharts !== false);
        syncScChartsSectionVisibility();
        $('#scTableSearch').val(scPendingTableSearch);

        const subIds = Object.prototype.hasOwnProperty.call(state, 'subcategory_id') ? state.subcategory_id : undefined;
        return populateSubcategories(subIds).always(function() {
            scRestoringFilters = false;
        });
    }

    function applyScFooterToDom(tfoot) {
        const f = scLastFooterTotals;
        const $row = $(tfoot).find('tr.sc-footer-totals');
        const $cells = $row.find('td');
        if (!$cells.length) {
            return;
        }
        const parseNum = (s) => {
            if (s == null || s === '' || s === '—') return null;
            const n = parseFloat(String(s).replace(/,/g, ''));
            return isNaN(n) ? null : n;
        };
        SC_FOOTER_KEYS.forEach(function(key, i) {
            const $td = $cells.eq(i);
            $td.removeClass('sc-cell-dim sc-cell-p1 sc-cell-p2 sc-cell-var sc-diff-up sc-diff-down text-center');
            if (i <= 5) {
                $td.addClass('sc-cell sc-cell-dim');
            } else if (i <= 11) {
                $td.addClass('sc-cell sc-cell-p1');
            } else if (i <= 17) {
                $td.addClass('sc-cell sc-cell-p2');
            } else {
                $td.addClass('sc-cell sc-cell-var');
            }
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
            const mStar = /filename\\*=UTF-8''([^;\\s]+)/i.exec(cd);
            const mQuot = /filename="([^"]+)"/i.exec(cd);
            const mPlain = /filename=([^;\\s]+)/i.exec(cd);
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

    function fmtNum(n) {
        if (n == null || isNaN(n)) return '';
        return Number(n).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function destroyCharts() {
        if (chartTotals) {
            chartTotals.destroy();
            chartTotals = null;
        }
        if (chartProducts) {
            chartProducts.destroy();
            chartProducts = null;
        }
    }

    const scChartCommonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 350 },
        layout: { padding: { top: 6, bottom: 6, left: 4, right: 10 } },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 10, padding: 12, usePointStyle: true }
            }
        }
    };

    function refreshSalesComparisonCharts() {
        if (!$('#showChartsToggle').is(':checked')) return;
        clearTimeout(chartRefreshTimer);
        chartRefreshTimer = setTimeout(function() {
            $.ajax({
                url: chartUrl,
                type: 'GET',
                data: getFilterParams(),
                success: function(res) {
                    if (!res.success) {
                        destroyCharts();
                        return;
                    }
                    const ta = res.totals.a;
                    const tb = res.totals.b;
                    const metricLabels = [str.qty, str.revenue, str.tax, str.discount, str.lines];
                    const aVals = [ta.qty, ta.subtotal, ta.tax, ta.discount, ta.line_count];
                    const bVals = [tb.qty, tb.subtotal, tb.tax, tb.discount, tb.line_count];

                    destroyCharts();

                    const ctxT = document.getElementById('scChartTotals');
                    if (ctxT && typeof Chart !== 'undefined') {
                        chartTotals = new Chart(ctxT, {
                            type: 'bar',
                            data: {
                                labels: metricLabels,
                                datasets: [
                                    {
                                        label: str.p1,
                                        data: aVals,
                                        backgroundColor: 'rgba(59, 130, 246, 0.65)',
                                        borderColor: 'rgb(37, 99, 235)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: str.p2,
                                        data: bVals,
                                        backgroundColor: 'rgba(249, 115, 22, 0.65)',
                                        borderColor: 'rgb(234, 88, 12)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: Object.assign({}, scChartCommonOptions, {
                                indexAxis: 'x',
                                plugins: Object.assign({}, scChartCommonOptions.plugins, {
                                    tooltip: {
                                        callbacks: {
                                            label: function(ctx) {
                                                return ctx.dataset.label + ': ' + fmtNum(ctx.raw);
                                            }
                                        }
                                    }
                                }),
                                scales: {
                                    x: { grid: { display: false } },
                                    y: {
                                        ticks: { callback: function(v) { return fmtNum(v); }, maxTicksLimit: 6 }
                                    }
                                }
                            })
                        });
                    }

                    const ctxP = document.getElementById('scChartProducts');
                    if (ctxP && typeof Chart !== 'undefined' && res.top_products && res.top_products.length) {
                        const labels = res.top_products.map(function(p) {
                            const s = p.name || '';
                            return s.length > 32 ? s.slice(0, 30) + '…' : s;
                        });
                        const pa = res.top_products.map(function(p) { return p.subtotal_a; });
                        const pb = res.top_products.map(function(p) { return p.subtotal_b; });
                        chartProducts = new Chart(ctxP, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: str.p1,
                                        data: pa,
                                        backgroundColor: 'rgba(59, 130, 246, 0.65)',
                                        borderColor: 'rgb(37, 99, 235)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: str.p2,
                                        data: pb,
                                        backgroundColor: 'rgba(249, 115, 22, 0.65)',
                                        borderColor: 'rgb(234, 88, 12)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: Object.assign({}, scChartCommonOptions, {
                                indexAxis: 'x',
                                plugins: Object.assign({}, scChartCommonOptions.plugins, {
                                    tooltip: {
                                        callbacks: {
                                            label: function(ctx) {
                                                return ctx.dataset.label + ': ' + fmtNum(ctx.raw);
                                            }
                                        }
                                    }
                                }),
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: {
                                            autoSkip: true,
                                            maxRotation: 45,
                                            minRotation: 0,
                                            callback: function(v, i) {
                                                const full = (res.top_products && res.top_products[i]) ? (res.top_products[i].name || labels[i] || '') : (labels[i] || '');
                                                return full.length > 14 ? full.slice(0, 12) + '…' : full;
                                            }
                                        }
                                    },
                                    y: {
                                        ticks: { callback: function(v) { return fmtNum(v); }, maxTicksLimit: 6 }
                                    }
                                }
                            })
                        });
                    } else if (ctxP && typeof Chart !== 'undefined') {
                        chartProducts = new Chart(ctxP, {
                            type: 'bar',
                            data: { labels: ['—'], datasets: [{ label: str.p1, data: [0] }, { label: str.p2, data: [0] }] },
                            options: Object.assign({}, scChartCommonOptions, { indexAxis: 'x' })
                        });
                    }
                },
                error: function() {
                    destroyCharts();
                }
            });
        }, 200);
    }

    function styleVarianceCells(row, data) {
        const parseNum = (s) => {
            if (s == null || s === '—') return null;
            const n = parseFloat(String(s).replace(/,/g, ''));
            return isNaN(n) ? null : n;
        };
        const q = parseNum(data.qty_difference);
        const r = parseNum(data.subtotal_difference);
        const qCell = $('td', row).eq(18);
        const rCell = $('td', row).eq(20);
        if (q !== null) {
            qCell.removeClass('sc-diff-up sc-diff-down');
            if (q > 0) qCell.addClass('sc-diff-up');
            else if (q < 0) qCell.addClass('sc-diff-down');
        }
        if (r !== null) {
            rCell.removeClass('sc-diff-up sc-diff-down');
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
                    const branchSelect = $('#branchFilter');
                    branchSelect.empty();
                    response.data.forEach(branch => {
                        branchSelect.append(new Option(branch.name, branch.id, false, false));
                    });
                    branchSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching branches:", error);
            }
        });
    }

    function populateCustomers() {
        return $.ajax({
            url: "{{ route('getCustomers') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const customerSelect = $('#customerFilter');
                    customerSelect.empty();
                    response.data.forEach(customer => {
                        customerSelect.append(new Option(customer.name, customer.id, false, false));
                    });
                    customerSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching customers:", error);
            }
        });
    }

    function populateProducts() {
        return $.ajax({
            url: "{{ route('retrieveProducts') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const productSelect = $('#productFilter');
                    productSelect.empty();
                    response.data.forEach(product => {
                        productSelect.append(new Option(product.name, product.id, false, false));
                    });
                    productSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching products:", error);
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
            },
            error: function(err) {
                console.error('Error fetching categories:', err);
            }
        });
    }

    /** @param {string[]|null|undefined} selectedIdsAfterLoad If undefined, clear selection after load (normal category change). */
    function populateSubcategories(selectedIdsAfterLoad) {
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
                    if (selectedIdsAfterLoad !== undefined) {
                        sel.val(selectedIdsAfterLoad).trigger('change');
                    } else {
                        sel.val(null).trigger('change');
                    }
                }
            },
            error: function(err) {
                console.error('Error fetching subcategories:', err);
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
            },
            error: function(err) {
                console.error('Error fetching units:', err);
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
            },
            error: function(err) {
                console.error('Error fetching payment methods:', err);
            }
        });
    }

    function initDatatable() {
        const initialSearch = scPendingTableSearch || '';
        dataTable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            search: { search: initialSearch },
            ajax: {
                url: apiUrl,
                data: function(d) {
                    Object.assign(d, getFilterParams());
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
            info: false,
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
            order: [],
            scrollX: true,
            pageLength: 10,
            createdRow: function(row, data) {
                styleVarianceCells(row, data);
            },
            drawCallback: function() {
                if (typeof KTMenu !== 'undefined') {
                    KTMenu.createInstances();
                }
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

        $('.sc-filter-multi').each(function() {
            scSelect2Multi($(this));
        });

        $('.sc-select2-single').each(function() {
            scSelect2Single($(this));
        });

        bindRangePicker('#periodARange');
        bindRangePicker('#periodBRange');

        $('#periodAPreset, #periodBPreset').on('change', function() {
            toggleCustomRanges();
        });

        $.when(
            populateBranches(),
            populateCustomers(),
            populateProducts(),
            populateCategories(),
            populateUnits(),
            populatePaymentMethods()
        ).always(function() {
            restoreSalesComparisonFilterStateOrDefaults().always(function() {
            syncFilterPanels();
            toggleCustomRanges();
            initDatatable();
            applyScColumnVisibilityFromStorage();
            dataTable.columns.adjust().draw(false);
            handleSearchDatatable();

            $('#scColumnPickerMenu').on('change', '.sc-col-toggle', function() {
                const $cb = $(this);
                const visible = $cb.is(':checked');
                const groupId = $cb.data('sc-group');
                let indices = [];
                if (groupId) {
                    const g = SC_COLUMN_GROUPS.find(function(x) { return x.id === groupId; });
                    indices = g ? g.indices.slice() : [];
                } else {
                    const idx = parseInt($cb.data('sc-idx'), 10);
                    if (!isNaN(idx)) indices = [idx];
                }
                if (!indices.length) return;
                if (!visible) {
                    let nAfter = 0;
                    SC_FOOTER_KEYS.forEach(function(_, i) {
                        const vis = dataTable.column(i).visible();
                        const willHide = indices.indexOf(i) >= 0;
                        if (vis && !willHide) nAfter++;
                    });
                    if (nAfter < 1) {
                        $cb.prop('checked', true);
                        return;
                    }
                }
                indices.forEach(function(i) {
                    dataTable.column(i).visible(visible, true);
                });
                scSaveColumnVisibility();
                dataTable.columns.adjust().draw(false);
            });

            $('#scTableSearch').on('blur', function() {
                saveSalesComparisonFilterState();
            });

            $('#scExportExcel').on('click', function() {
                scTriggerFileExport(exportExcelUrl);
            });
            $('#scExportPdf').on('click', function() {
                scTriggerFileExport(exportPdfUrl);
            });

            $('#quickComparison').on('change', function() {
                const code = $(this).val();
                if (!code) return;
                applyQuickComparison(code);
                saveSalesComparisonFilterState();
                dataTable.ajax.reload();
                if ($('#showChartsToggle').is(':checked')) {
                    refreshSalesComparisonCharts();
                }
            });

            $('#applyFilter').on('click', function() {
                saveSalesComparisonFilterState();
                dataTable.ajax.reload();
                if ($('#showChartsToggle').is(':checked')) {
                    refreshSalesComparisonCharts();
                }
            });

            $('#clearFilter').on('click', function() {
                try {
                    localStorage.removeItem(SC_FILTER_STORAGE_KEY);
                } catch (e) {}
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
                $('#scTableSearch').val('');
                scPendingTableSearch = '';
                if (dataTable) {
                    dataTable.search('').draw();
                }
                $('#showChartsToggle').prop('checked', true);
                syncScChartsSectionVisibility();
                destroyCharts();
                dataTable.ajax.reload();
                requestAnimationFrame(function() {
                    refreshSalesComparisonCharts();
                });
            });

            $('#showChartsToggle').on('change', function() {
                saveSalesComparisonFilterState();
                const on = $(this).is(':checked');
                $('#scChartsSection').toggleClass('d-none', !on);
                if (on) {
                    requestAnimationFrame(function() {
                        refreshSalesComparisonCharts();
                    });
                } else {
                    destroyCharts();
                }
            });

            if ($('#showChartsToggle').is(':checked')) {
                requestAnimationFrame(function() {
                    refreshSalesComparisonCharts();
                });
            }
            });
        });
    });
</script>
@endsection
