@extends('layouts.app')

@section('title', __('menuItemLang.product-purchase-report'))

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

    #kt_ProductSales_table tbody td.ppr-purchase-qty-cell {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    #pprColumnPickerMenu {
        min-width: 280px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .ppr-table-toolbar .ppr-gear-btn {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .ppr-table-toolbar .ppr-gear-btn i {
        color: #ebb81e;
    }

    .ppr-table-toolbar .ppr-gear-btn:hover,
    .ppr-table-toolbar .ppr-gear-btn:focus,
    .ppr-table-toolbar .ppr-gear-btn:active,
    .ppr-table-toolbar .ppr-gear-btn.show {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .ppr-table-toolbar .ppr-gear-btn:hover i,
    .ppr-table-toolbar .ppr-gear-btn:focus i,
    .ppr-table-toolbar .ppr-gear-btn:active i {
        color: #ebb81e;
    }
</style>
@stop

@section('content')

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">

        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title w-100">
                    <div class="d-flex align-items-center position-relative my-1">
                        <h3 class="mb-0">@lang('menuItemLang.product-purchase-report')</h3>
                    </div>
                </div>
            </x-cards.card-header>

            {{-- Inline Filters --}}
            <div class="card-body border-top p-5">
                <form id="reportFilterForm">
                    <div class="row g-5">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="productPurchaseReportMode" class="form-label">@lang('report::general.product_purchase_report_mode')</label>
                            <select class="form-select form-select-solid" id="productPurchaseReportMode" name="report_mode">
                                <option value="detail">@lang('report::general.product_purchase_report_mode_detail')</option>
                                <option value="summary">@lang('report::general.product_purchase_report_mode_summary')</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="branchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                            <select class="form-select form-select-solid" id="branchFilter" name="branch_id[]" data-control="select2" data-placeholder="@lang('report::general.All Branches')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="supplierFilter" class="form-label">@lang('report::purchase.Supplier')</label>
                            <select class="form-select form-select-solid" id="supplierFilter" name="supplier_id[]" data-control="select2" data-placeholder="@lang('report::general.All Suppliers')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="productFilter" class="form-label">@lang('report::purchase.Product')</label>
                            <select class="form-select form-select-solid" id="productFilter" name="product_id[]" data-control="select2" data-placeholder="@lang('report::purchase.All Products')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="categoryFilter" class="form-label">@lang('report::fields.category')</label>
                            <select class="form-select form-select-solid" id="categoryFilter" name="category_id[]" data-control="select2" data-placeholder="@lang('report::general.All_categories')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="subcategoryFilter" class="form-label">@lang('report::fields.subcategory')</label>
                            <select class="form-select form-select-solid" id="subcategoryFilter" name="subcategory_id[]" data-control="select2" data-placeholder="@lang('report::general.All_subcategories')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="unitFilter" class="form-label">@lang('report::fields.line_unit')</label>
                            <select class="form-select form-select-solid" id="unitFilter" name="unit_id[]" data-control="select2" data-placeholder="@lang('report::general.All_units')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="paymentMethodFilter" class="form-label">@lang('report::fields.payment_method')</label>
                            <select class="form-select form-select-solid" id="paymentMethodFilter" name="payment_method[]" data-control="select2" data-placeholder="@lang('report::general.All Methods')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="saleDateRange" class="form-label">@lang('report::purchase.Sale Date Range')</label>
                            <input type="text" class="form-control form-control-solid" id="saleDateRange" name="sale_date_range" />
                        </div>
                    </div>
                    {{-- Filter Buttons --}}
                    <div class="row mt-5">
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilter">
                                <i class="bi bi-funnel fs-2"></i> @lang('report::general.Apply Filter')
                            </button>
                            <button type="button" class="btn btn-warning" id="clearFilter">@lang('report::general.Remove filter')</button>
                        </div>
                    </div>
                </form>
            </div>

            <x-cards.card-body class="table-responsive border-top">
                <div class="ppr-table-toolbar d-flex flex-wrap align-items-center gap-3 py-5 px-1 px-lg-0">
                    <div class="d-flex align-items-center position-relative flex-grow-1 min-w-200px">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4 z-index-1"></i>
                        <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                            placeholder="@lang('report::general.ProductSales_search')" />
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-sm-auto">
                        <button type="button" class="btn btn-sm btn-light-success" id="productPurchasesExportExcelBtn"
                            title="@lang('report::general.product_purchase_export_full_hint')">
                            <i class="bi bi-file-earmark-excel fs-5"></i>
                            @lang('report::general.export_excel_btn')
                        </button>
                        <button type="button" class="btn btn-sm btn-light-danger" id="productPurchasesExportPdfBtn"
                            title="@lang('report::general.product_purchase_export_full_hint')">
                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                            @lang('report::general.export_pdf_btn')
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon ppr-gear-btn" type="button"
                                id="pprColumnPickerToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="@lang('report::general.product_purchase_table_columns_hint')">
                                <i class="bi bi-gear-fill fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg" id="pprColumnPickerMenu"
                                aria-labelledby="pprColumnPickerToggle">
                                <div class="fw-bold mb-3">@lang('report::general.product_purchase_table_columns')</div>
                                <div class="text-muted fs-8 mb-3">@lang('report::general.product_purchase_table_columns_hint')</div>
                                @foreach ($columns as $col)
                                    <div class="form-check form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input ppr-col-toggle" type="checkbox"
                                            id="ppr_col_{{ $col['name'] }}"
                                            data-ppr-idx="{{ $loop->index }}"
                                            data-ppr-key="{{ $col['name'] }}"
                                            checked />
                                        <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="ppr_col_{{ $col['name'] }}">
                                            @lang('report::fields.' . $col['name'])
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pb-5 px-1 px-lg-0">
                    <x-tables.table :columns="$columns" model="ProductSales" module="report" :idColumn="false" :actionColumn="false" />
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
<script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>
<script>
    "use strict";
    let dataTable;
    const table = $('#kt_ProductSales_table');
    let apiUrl = "{{ route('product-purchase-report') }}";
    const productPurchasesExportExcelUrl = "{{ route('product-purchase-export-excel') }}";
    const productPurchasesExportPdfUrl = "{{ route('product-purchase-export-pdf') }}";
    const purchaseCategoriesUrl = "{{ route('comparison-categories') }}";
    const purchaseSubcategoriesUrl = "{{ route('purchase-report-subcategories') }}";
    const purchaseUnitsUrl = "{{ route('purchase-report-units') }}";
    const purchasePaymentMethodsUrl = "{{ route('purchase-report-payment-methods') }}";
    const PPR_COLUMN_KEYS = @json(array_column($columns, 'name'));
    const PPR_COLUMN_VISIBILITY_STORAGE = 'ppr_column_visibility_v1';

    function getFilterParams() {
        return {
            report_mode: $('#productPurchaseReportMode').val() || 'detail',
            branch_id: $('#branchFilter').val() || [],
            supplier_id: $('#supplierFilter').val() || [],
            product_id: $('#productFilter').val() || [],
            category_id: $('#categoryFilter').val() || [],
            subcategory_id: $('#subcategoryFilter').val() || [],
            unit_id: $('#unitFilter').val() || [],
            payment_method: $('#paymentMethodFilter').val() || [],
            sale_date_range: $('#saleDateRange').val()
        };
    }

    function getProductPurchasesVisibleColumnKeys() {
        const keys = [];
        PPR_COLUMN_KEYS.forEach(function(key, idx) {
            if (dataTable.column(idx).visible()) {
                keys.push(key);
            }
        });
        return keys.length ? keys : [PPR_COLUMN_KEYS[0]];
    }

    function productPurchasesFullExportUrl(base) {
        const params = $.extend({}, getFilterParams(), {
            export_columns: getProductPurchasesVisibleColumnKeys()
        });
        const q = $.param(params, false);
        return q ? (base + '?' + q) : base;
    }

    function pprLoadColumnVisibility() {
        try {
            return JSON.parse(localStorage.getItem(PPR_COLUMN_VISIBILITY_STORAGE) || '{}');
        } catch (e) {
            return {};
        }
    }

    function pprSaveColumnVisibility() {
        const state = {};
        PPR_COLUMN_KEYS.forEach(function(key, idx) {
            state[key] = dataTable.column(idx).visible();
        });
        try {
            localStorage.setItem(PPR_COLUMN_VISIBILITY_STORAGE, JSON.stringify(state));
        } catch (e) {}
    }

    function applyPprColumnVisibilityFromStorage() {
        const state = pprLoadColumnVisibility();
        let visibleCount = 0;
        PPR_COLUMN_KEYS.forEach(function(key, idx) {
            if (state[key] !== false) visibleCount++;
        });
        if (visibleCount === 0) {
            PPR_COLUMN_KEYS.forEach(function(key, idx) {
                dataTable.column(idx).visible(true, false);
                $('#ppr_col_' + key).prop('checked', true);
            });
            pprSaveColumnVisibility();
            return;
        }
        PPR_COLUMN_KEYS.forEach(function(key, idx) {
            const visible = state[key] !== false;
            dataTable.column(idx).visible(visible, false);
            $('#ppr_col_' + key).prop('checked', visible);
        });
    }

    function populateBranches() {
        $.ajax({
            url: "{{ route('branches') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const branchSelect = $('#branchFilter');
                    branchSelect.empty();
                    response.data.forEach(branch => {
                        const newOption = new Option(branch.name, branch.id, false, false);
                        branchSelect.append(newOption);
                    });
                    branchSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching branches:", error);
            }
        });
    }

    function populateSuppliers() {
        $.ajax({
            url: "{{ route('getSuppliers') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const supplierSelect = $('#supplierFilter');
                    supplierSelect.empty();
                    response.data.forEach(supplier => {
                        const newOption = new Option(supplier.name, supplier.id, false, false);
                        supplierSelect.append(newOption);
                    });
                    supplierSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching suppliers:", error);
            }
        });
    }

    function populateProducts() {
        $.ajax({
            url: "{{ route('retrieveProducts') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const productSelect = $('#productFilter');
                    productSelect.empty();
                    response.data.forEach(product => {
                        const newOption = new Option(product.name, product.id, false, false);
                        productSelect.append(newOption);
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
        $.ajax({
            url: purchaseCategoriesUrl,
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

    function populateSubcategories() {
        const catIds = $('#categoryFilter').val() || [];
        $.ajax({
            url: purchaseSubcategoriesUrl,
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
                    sel.val(null).trigger('change');
                }
            },
            error: function(err) {
                console.error('Error fetching subcategories:', err);
            }
        });
    }

    function populateUnits() {
        $.ajax({
            url: purchaseUnitsUrl,
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
        $.ajax({
            url: purchasePaymentMethodsUrl,
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

    $(document).ready(function() {
        if (!table.length) return;

        $('#categoryFilter').on('change', function() {
            populateSubcategories();
        });

        populateBranches();
        populateSuppliers();
        populateProducts();
        populateCategories();
        populateUnits();
        populatePaymentMethods();
        initDatatable();
        applyPprColumnVisibilityFromStorage();
        dataTable.columns.adjust().draw(false);
        handleSearchDatatable();

        $('#pprColumnPickerMenu').on('change', '.ppr-col-toggle', function() {
            const idx = parseInt($(this).data('ppr-idx'), 10);
            const visible = $(this).is(':checked');
            if (!visible) {
                let n = 0;
                PPR_COLUMN_KEYS.forEach(function(_, i) {
                    if (dataTable.column(i).visible()) n++;
                });
                if (n <= 1) {
                    $(this).prop('checked', true);
                    return;
                }
            }
            dataTable.column(idx).visible(visible, true);
            pprSaveColumnVisibility();
            dataTable.columns.adjust().draw(false);
        });

        $('.form-select').select2();

        $('#saleDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

        $('#saleDateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#saleDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $('#productPurchaseReportMode').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
        });

        $('#clearFilter').on('click', function() {
            $('#reportFilterForm')[0].reset();
            $('.form-select').val(null).trigger('change');
            $('#saleDateRange').val('');
            $('#productPurchaseReportMode').val('detail');
            dataTable.ajax.url(apiUrl).load();
        });

        $('#productPurchasesExportExcelBtn').on('click', function() {
            window.location.href = productPurchasesFullExportUrl(productPurchasesExportExcelUrl);
        });
        $('#productPurchasesExportPdfBtn').on('click', function() {
            window.location.href = productPurchasesFullExportUrl(productPurchasesExportPdfUrl);
        });
    });

    function initDatatable() {
        dataTable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: apiUrl,
                data: function(d) {
                    Object.assign(d, getFilterParams());
                }
            },
            info: false,
            columns: [{
                    data: 'establishment_name',
                    name: 'establishment_name'
                },
                {
                    data: 'product_name',
                    name: 'product_name'
                },
                {
                    data: 'SKU',
                    name: 'SKU'
                },
                {
                    data: 'purchased_quantity',
                    name: 'purchased_quantity',
                    className: 'ppr-purchase-qty-cell'
                },
                {
                    data: 'category',
                    name: 'category'
                },
                {
                    data: 'subcategory',
                    name: 'subcategory'
                },
                {
                    data: 'price',
                    name: 'price'
                },
                {
                    data: 'line_unit',
                    name: 'line_unit'
                },
                {
                    data: 'supplier',
                    name: 'supplier'
                },
                {
                    data: 'invoice_payment_methods',
                    name: 'invoice_payment_methods',
                    orderable: false
                },
                {
                    data: 'ref_no',
                    name: 'ref_no'
                },
                {
                    data: 'transaction_date',
                    name: 'transaction_date'
                },
                {
                    data: 'unit_price',
                    name: 'unit_price'
                },
                {
                    data: 'unit_sale_price',
                    name: 'unit_sale_price'
                },
                {
                    data: 'discount_amount',
                    name: 'discount_amount'
                },
                {
                    data: 'tax_value',
                    name: 'tax_value'
                },
                {
                    data: 'subtotal',
                    name: 'subtotal'
                }
            ],
            order: [],
            scrollX: true,
            pageLength: 10,
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });
        window.dataTable = dataTable;
    };
</script>
@endsection
