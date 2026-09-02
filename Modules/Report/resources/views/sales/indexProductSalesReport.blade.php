@extends('layouts.app')

@section('title', __('menuItemLang.product-sales-report'))

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

    /* الكمية المباعة (العمود الرابع) أوضح */
    #kt_ProductSales_table tbody td.psr-sell-qty-cell {
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    #psrColumnPickerMenu {
        min-width: 280px;
        max-height: 70vh;
        overflow-y: auto;
    }

    /* ترس بلون #ebb81e فقط؛ الزر بدون خلفية وبدون لون عند hover */
    .psr-table-toolbar .psr-gear-btn {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .psr-table-toolbar .psr-gear-btn i {
        color: #ebb81e;
    }

    .psr-table-toolbar .psr-gear-btn:hover,
    .psr-table-toolbar .psr-gear-btn:focus,
    .psr-table-toolbar .psr-gear-btn:active,
    .psr-table-toolbar .psr-gear-btn.show {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .psr-table-toolbar .psr-gear-btn:hover i,
    .psr-table-toolbar .psr-gear-btn:focus i,
    .psr-table-toolbar .psr-gear-btn:active i {
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
                        <h3 class="mb-0">@lang('menuItemLang.product-sales-report')</h3>
                    </div>
                </div>
            </x-cards.card-header>

            <x-cards.card-body class="table-responsive border-top">
                <div class="psr-table-toolbar d-flex flex-wrap align-items-center gap-3 py-5 px-1 px-lg-0">
                    <div class="d-flex align-items-center position-relative flex-grow-1 min-w-200px">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4 z-index-1"></i>
                        <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                            placeholder="@lang('report::general.ProductSales_search')" />
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-sm-auto">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#reportFilterModal" title="@lang('report::general.Apply Filter')">
                            <i class="bi bi-funnel"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border" id="clearFilter"
                            title="@lang('report::general.Remove filter')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        @dashboardcan(\Modules\Report\Support\ReportPermissions::PRODUCT_SALES_PRINT)
                        <button type="button" class="btn btn-sm btn-export-excel" id="productSalesExportExcelBtn"
                            title="@lang('report::general.product_sales_export_full_hint')">
                            <i class="bi bi-file-earmark-excel fs-5"></i>
                            @lang('report::general.export_excel_btn')
                        </button>
                        <button type="button" class="btn btn-sm btn-export-pdf" id="productSalesExportPdfBtn"
                            title="@lang('report::general.product_sales_export_full_hint')">
                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                            @lang('report::general.export_pdf_btn')
                        </button>
                        @enddashboardcan
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon psr-gear-btn" type="button"
                                id="psrColumnPickerToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="@lang('report::general.product_sales_table_columns_hint')">
                                <i class="bi bi-gear-fill fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg" id="psrColumnPickerMenu"
                                aria-labelledby="psrColumnPickerToggle">
                                <div class="fw-bold mb-3">@lang('report::general.product_sales_table_columns')</div>
                                <div class="text-muted fs-8 mb-3">@lang('report::general.product_sales_table_columns_hint')</div>
                                @foreach ($columns as $col)
                                    <div class="form-check form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input psr-col-toggle" type="checkbox"
                                            id="psr_col_{{ $col['name'] }}"
                                            data-psr-idx="{{ $loop->index }}"
                                            data-psr-key="{{ $col['name'] }}"
                                            checked />
                                        <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="psr_col_{{ $col['name'] }}">
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

<div class="modal fade" id="reportFilterModal" tabindex="-1" aria-labelledby="reportFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportFilterModalLabel">
                    <i class="bi bi-funnel me-2"></i>@lang('report::general.Apply Filter')
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('messages.cancel')"></button>
            </div>
            <div class="modal-body">
                <form id="reportFilterForm">
                    <div class="row g-5">
                        <div class="col-12 col-sm-6">
                            <label for="productSalesReportMode" class="form-label">@lang('report::general.product_sales_report_mode')</label>
                            <select class="form-select form-select-solid" id="productSalesReportMode" name="report_mode">
                                <option value="detail">@lang('report::general.product_sales_report_mode_detail')</option>
                                <option value="summary">@lang('report::general.product_sales_report_mode_summary')</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="branchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                            <select class="form-select form-select-solid" id="branchFilter" name="branch_id[]" data-control="select2" data-placeholder="@lang('report::general.All Branches')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="customerFilter" class="form-label">@lang('report::purchase.Customer')</label>
                            <select class="form-select form-select-solid" id="customerFilter" name="customer_id[]" data-control="select2" data-placeholder="@lang('report::purchase.All Customer')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="productFilter" class="form-label">@lang('report::purchase.Product')</label>
                            <select class="form-select form-select-solid" id="productFilter" name="product_id[]" data-control="select2" data-placeholder="@lang('report::purchase.All Products')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="categoryFilter" class="form-label">@lang('report::fields.category')</label>
                            <select class="form-select form-select-solid" id="categoryFilter" name="category_id[]" data-control="select2" data-placeholder="@lang('report::general.All_categories')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="subcategoryFilter" class="form-label">@lang('report::fields.subcategory')</label>
                            <select class="form-select form-select-solid" id="subcategoryFilter" name="subcategory_id[]" data-control="select2" data-placeholder="@lang('report::general.All_subcategories')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="unitFilter" class="form-label">@lang('report::fields.line_unit')</label>
                            <select class="form-select form-select-solid" id="unitFilter" name="unit_id[]" data-control="select2" data-placeholder="@lang('report::general.All_units')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="paymentMethodFilter" class="form-label">@lang('report::fields.payment_method')</label>
                            <select class="form-select form-select-solid" id="paymentMethodFilter" name="payment_method[]" data-control="select2" data-placeholder="@lang('report::general.All Methods')" multiple>
                                <option></option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="saleDateRange" class="form-label">@lang('report::purchase.Sale Date Range')</label>
                            <input type="text" class="form-control form-control-solid" id="saleDateRange" name="sale_date_range" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="applyFilter">
                    <i class="bi bi-funnel me-1"></i> @lang('report::general.Apply Filter')
                </button>
            </div>
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
    let apiUrl = "{{ route('product-sales-report') }}";
    const productSalesExportExcelUrl = "{{ route('product-sales-export-excel') }}";
    const productSalesExportPdfUrl = "{{ route('product-sales-export-pdf') }}";
    const comparisonCategoriesUrl = "{{ route('comparison-categories') }}";
    const comparisonSubcategoriesUrl = "{{ route('comparison-subcategories') }}";
    const comparisonUnitsUrl = "{{ route('comparison-units') }}";
    const comparisonPaymentMethodsUrl = "{{ route('comparison-payment-methods') }}";
    const PSR_COLUMN_KEYS = @json(array_column($columns, 'name'));
    const PSR_COLUMN_VISIBILITY_STORAGE = 'psr_column_visibility_v1';

    function getFilterParams() {
        return {
            report_mode: $('#productSalesReportMode').val() || 'detail',
            branch_id: $('#branchFilter').val() || [],
            customer_id: $('#customerFilter').val() || [],
            product_id: $('#productFilter').val() || [],
            category_id: $('#categoryFilter').val() || [],
            subcategory_id: $('#subcategoryFilter').val() || [],
            unit_id: $('#unitFilter').val() || [],
            payment_method: $('#paymentMethodFilter').val() || [],
            sale_date_range: $('#saleDateRange').val()
        };
    }

    function getProductSalesVisibleColumnKeys() {
        const keys = [];
        PSR_COLUMN_KEYS.forEach(function(key, idx) {
            if (dataTable.column(idx).visible()) {
                keys.push(key);
            }
        });
        return keys.length ? keys : [PSR_COLUMN_KEYS[0]];
    }

    function productSalesFullExportUrl(base) {
        const params = $.extend({}, getFilterParams(), {
            export_columns: getProductSalesVisibleColumnKeys()
        });
        // false: مصفوفات كـ branch_id[]=1&export_columns[]=x يقرأها Laravel/PHP كمصفوفات.
        // true يولّد export_columns=a&export_columns=b فيُبقى PHP آخر قيمة فقط → تصدير خاطئ أو ناقص.
        const q = $.param(params, false);
        return q ? (base + '?' + q) : base;
    }

    function psrLoadColumnVisibility() {
        try {
            return JSON.parse(localStorage.getItem(PSR_COLUMN_VISIBILITY_STORAGE) || '{}');
        } catch (e) {
            return {};
        }
    }

    function psrSaveColumnVisibility() {
        const state = {};
        PSR_COLUMN_KEYS.forEach(function(key, idx) {
            state[key] = dataTable.column(idx).visible();
        });
        try {
            localStorage.setItem(PSR_COLUMN_VISIBILITY_STORAGE, JSON.stringify(state));
        } catch (e) {}
    }

    function applyPsrColumnVisibilityFromStorage() {
        const state = psrLoadColumnVisibility();
        let visibleCount = 0;
        PSR_COLUMN_KEYS.forEach(function(key, idx) {
            const visible = state[key] !== false;
            if (visible) visibleCount++;
        });
        if (visibleCount === 0) {
            PSR_COLUMN_KEYS.forEach(function(key, idx) {
                dataTable.column(idx).visible(true, false);
                $('#psr_col_' + key).prop('checked', true);
            });
            psrSaveColumnVisibility();
            return;
        }
        PSR_COLUMN_KEYS.forEach(function(key, idx) {
            const visible = state[key] !== false;
            dataTable.column(idx).visible(visible, false);
            $('#psr_col_' + key).prop('checked', visible);
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

    function populateCustomers() {
        $.ajax({
            url: "{{ route('getCustomers') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const customerSelect = $('#customerFilter');
                    customerSelect.empty();
                    response.data.forEach(customer => {
                        const newOption = new Option(customer.name, customer.id, false, false);
                        customerSelect.append(newOption);
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

    function populateSubcategories() {
        const catIds = $('#categoryFilter').val() || [];
        $.ajax({
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
        $.ajax({
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

    $(document).ready(function() {
        if (!table.length) return;

        $('#categoryFilter').on('change', function() {
            populateSubcategories();
        });

        populateBranches();
        populateCustomers();
        populateProducts();
        populateCategories();
        populateUnits();
        populatePaymentMethods();
        initDatatable();
        applyPsrColumnVisibilityFromStorage();
        dataTable.columns.adjust().draw(false);
        handleSearchDatatable();

        $('#psrColumnPickerMenu').on('change', '.psr-col-toggle', function() {
            const idx = parseInt($(this).data('psr-idx'), 10);
            const visible = $(this).is(':checked');
            if (!visible) {
                let n = 0;
                PSR_COLUMN_KEYS.forEach(function(_, i) {
                    if (dataTable.column(i).visible()) n++;
                });
                if (n <= 1) {
                    $(this).prop('checked', true);
                    return;
                }
            }
            dataTable.column(idx).visible(visible, true);
            psrSaveColumnVisibility();
            dataTable.columns.adjust().draw(false);
        });

        $('#reportFilterModal .form-select').select2({
            dropdownParent: $('#reportFilterModal')
        });

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

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
            const modalEl = document.getElementById('reportFilterModal');
            if (modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
            }
        });

        $('#clearFilter').on('click', function() {
            $('#reportFilterForm')[0].reset();
            $('#reportFilterModal .form-select').val(null).trigger('change');
            $('#saleDateRange').val('');
            $('#productSalesReportMode').val('detail');
            dataTable.ajax.url(apiUrl).load();
        });

        $('#productSalesExportExcelBtn').on('click', function() {
            window.location.href = productSalesFullExportUrl(productSalesExportExcelUrl);
        });
        $('#productSalesExportPdfBtn').on('click', function() {
            window.location.href = productSalesFullExportUrl(productSalesExportPdfUrl);
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
                    data: 'sell_qty',
                    name: 'sell_qty',
                    className: 'psr-sell-qty-cell'
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
                    data: 'customer',
                    name: 'customer'
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