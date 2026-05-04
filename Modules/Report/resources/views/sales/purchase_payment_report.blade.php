@extends('layouts.app')

@section('title', __('menuItemLang.purchase-payment-report'))

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

    .report-page-shell .card {
        border-radius: 14px;
    }

    .report-title-wrap h3 {
        margin: 0;
        font-weight: 700;
    }

    .report-title-wrap .report-subtitle {
        color: #99a1b7;
        font-size: 12px;
        margin-top: 4px;
    }

    .report-filter-card {
        border: 1px solid #eef1f5;
        border-radius: 14px;
        background: #fafcff;
    }

    .report-table-card {
        border: 1px solid #eef1f5;
        border-radius: 14px;
        padding: 10px;
        background: #fff;
    }

    #ppayColumnPickerMenu {
        min-width: 280px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .ppay-table-toolbar .ppay-gear-btn {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .ppay-table-toolbar .ppay-gear-btn i {
        color: #ebb81e;
    }

    .ppay-table-toolbar .ppay-gear-btn:hover,
    .ppay-table-toolbar .ppay-gear-btn:focus,
    .ppay-table-toolbar .ppay-gear-btn:active,
    .ppay-table-toolbar .ppay-gear-btn.show {
        background-color: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .ppay-table-toolbar .ppay-gear-btn:hover i,
    .ppay-table-toolbar .ppay-gear-btn:focus i,
    .ppay-table-toolbar .ppay-gear-btn:active i {
        color: #ebb81e;
    }
</style>
@stop

@section('content')

<div class="tab-content report-page-shell" id="myTabContent">
    <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">

        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title w-100">
                    <div class="d-flex align-items-center position-relative my-1 report-title-wrap">
                        <div>
                            <h3 class="mb-0">@lang('menuItemLang.purchase-payment-report')</h3>
                            <div class="report-subtitle">@lang('report::purchase.purchase_payment_report_details')</div>
                        </div>
                    </div>
                </div>
            </x-cards.card-header>

            {{-- Inline Filters --}}
            <div class="card-body border-top p-5 report-filter-card">
                <form id="reportFilterForm">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label for="branchFilter" class="form-label">@lang('report::purchase.Branch')</label>
                                    <select class="form-select form-select-solid" id="branchFilter" name="branch_id[]" data-control="select2" data-placeholder="@lang('report::general.All Branches')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="deviceFilter" class="form-label">@lang('report::purchase.Device')</label>
                                    <select class="form-select form-select-solid" id="deviceFilter" name="device_id[]" data-control="select2" data-placeholder="@lang('report::general.All devices')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="supplierFilter" class="form-label">@lang('report::purchase.Supplier')</label>
                                    <select class="form-select form-select-solid" id="supplierFilter" name="supplier_id[]" data-control="select2" data-placeholder="@lang('report::general.All Suppliers')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label for="paymentDateRange" class="form-label">@lang('report::purchase.Payment Date Range')</label>
                                    <input type="text" class="form-control form-control-solid" id="paymentDateRange" name="payment_date_range" />
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentMethodFilter" class="form-label">@lang('report::purchase.Payment Method')</label>
                                    <select class="form-select form-select-solid" id="paymentMethodFilter" name="payment_method[]" data-control="select2" data-placeholder="@lang('report::general.All Methods')" multiple>
                                        <option></option>
                                        <option value="cash">@lang('report::purchase.Cash')</option>
                                        <option value="due">@lang('report::purchase.Payment_Due')</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentStatusFilter" class="form-label">@lang('report::purchase.Payment Status')</label>
                            <select class="form-select form-select-solid" id="paymentStatusFilter" name="payment_status[]" data-control="select2" data-placeholder="@lang('report::general.All Statuses')" multiple>
                                <option></option>
                                <option value="paid">@lang('report::purchase.paid')</option>
                                <option value="partial">@lang('report::purchase.partial')</option>
                                <option value="due">@lang('report::purchase.due')</option>
                            </select>
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

            <x-cards.card-body class="table-responsive report-table-card border-top">
                <div class="ppay-table-toolbar d-flex flex-wrap align-items-center gap-3 py-5 px-1 px-lg-0">
                    <div class="d-flex align-items-center position-relative flex-grow-1 min-w-200px">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4 z-index-1"></i>
                        <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                            placeholder="@lang('report::general.ProductSales_search')" />
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-sm-auto">
                        <button type="button" class="btn btn-sm btn-light-success" id="purchasePaymentExportExcelBtn"
                            title="@lang('report::general.purchase_payment_export_full_hint')">
                            <i class="bi bi-file-earmark-excel fs-5"></i>
                            @lang('report::general.export_excel_btn')
                        </button>
                        <button type="button" class="btn btn-sm btn-light-danger" id="purchasePaymentExportPdfBtn"
                            title="@lang('report::general.purchase_payment_export_full_hint')">
                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                            @lang('report::general.export_pdf_btn')
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-icon ppay-gear-btn" type="button"
                                id="ppayColumnPickerToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false"
                                title="@lang('report::general.purchase_payment_table_columns_hint')">
                                <i class="bi bi-gear-fill fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-4 shadow-lg" id="ppayColumnPickerMenu"
                                aria-labelledby="ppayColumnPickerToggle">
                                <div class="fw-bold mb-3">@lang('report::general.purchase_payment_table_columns')</div>
                                <div class="text-muted fs-8 mb-3">@lang('report::general.purchase_payment_table_columns_hint')</div>
                                @foreach ($purchasePaymentColumnPicker as $meta)
                                    <div class="form-check form-check-custom form-check-solid mb-2">
                                        <input class="form-check-input ppay-col-toggle" type="checkbox"
                                            id="ppay_col_{{ $meta['key'] }}"
                                            data-ppay-idx="{{ $loop->index }}"
                                            data-ppay-key="{{ $meta['key'] }}"
                                            checked />
                                        <label class="form-check-label fw-semibold text-gray-700 cursor-pointer" for="ppay_col_{{ $meta['key'] }}">
                                            @lang('report::fields.' . $meta['field'])
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
    let apiUrl = "{{ route('purchase-payment-report') }}";
    const purchasePaymentExportExcelUrl = "{{ route('purchase-payment-export-excel') }}";
    const purchasePaymentExportPdfUrl = "{{ route('purchase-payment-export-pdf') }}";
    const PPAY_COLUMN_KEYS = @json($purchasePaymentExportColumnKeys);
    const PPAY_COLUMN_VISIBILITY_STORAGE = 'purchase_payment_column_visibility_v1';

    function getFilterParams() {
        const branchId = $('#branchFilter').val() || [];
        const deviceId = $('#deviceFilter').val() || [];
        const supplierId = $('#supplierFilter').val() || [];
        const paymentMethod = $('#paymentMethodFilter').val() || [];
        const paymentStatus = $('#paymentStatusFilter').val() || [];
        const dateRange = $('#paymentDateRange').val();

        return {
            branch_id: branchId,
            device_id: deviceId,
            supplier_id: supplierId,
            payment_method: paymentMethod,
            payment_status: paymentStatus,
            payment_date_range: dateRange
        };
    }

    function getPurchasePaymentVisibleColumnKeys() {
        const keys = [];
        PPAY_COLUMN_KEYS.forEach(function(key, idx) {
            if (dataTable.column(idx).visible()) {
                keys.push(key);
            }
        });
        return keys.length ? keys : [PPAY_COLUMN_KEYS[0]];
    }

    function purchasePaymentFullExportUrl(base) {
        const params = $.extend({}, getFilterParams(), {
            export_columns: getPurchasePaymentVisibleColumnKeys()
        });
        const q = $.param(params, false);
        return q ? (base + '?' + q) : base;
    }

    function ppayLoadColumnVisibility() {
        try {
            return JSON.parse(localStorage.getItem(PPAY_COLUMN_VISIBILITY_STORAGE) || '{}');
        } catch (e) {
            return {};
        }
    }

    function ppaySaveColumnVisibility() {
        const state = {};
        PPAY_COLUMN_KEYS.forEach(function(key, idx) {
            state[key] = dataTable.column(idx).visible();
        });
        try {
            localStorage.setItem(PPAY_COLUMN_VISIBILITY_STORAGE, JSON.stringify(state));
        } catch (e) {}
    }

    function applyPpayColumnVisibilityFromStorage() {
        const state = ppayLoadColumnVisibility();
        let visibleCount = 0;
        PPAY_COLUMN_KEYS.forEach(function(key, idx) {
            if (state[key] !== false) visibleCount++;
        });
        if (visibleCount === 0) {
            PPAY_COLUMN_KEYS.forEach(function(key, idx) {
                dataTable.column(idx).visible(true, false);
                $('#ppay_col_' + key).prop('checked', true);
            });
            ppaySaveColumnVisibility();
            return;
        }
        PPAY_COLUMN_KEYS.forEach(function(key, idx) {
            const visible = state[key] !== false;
            dataTable.column(idx).visible(visible, false);
            $('#ppay_col_' + key).prop('checked', visible);
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

    function populateDevices() {
        $.ajax({
            url: "{{ route('devices') }}",
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const deviceSelect = $('#deviceFilter');
                    deviceSelect.empty();
                    response.data.forEach(device => {
                        const newOption = new Option(device.name, device.id, false, false);
                        deviceSelect.append(newOption);
                    });
                    deviceSelect.trigger('change');
                }
            },
            error: function(error) {
                console.error("Error fetching devices:", error);
            }
        });
    }
    $(document).ready(function() {
        if (!table.length) return;

        populateBranches();
        populateSuppliers();
        populateDevices();
        initDatatable();
        applyPpayColumnVisibilityFromStorage();
        dataTable.columns.adjust().draw(false);
        handleSearchDatatable();

        $('#ppayColumnPickerMenu').on('change', '.ppay-col-toggle', function() {
            const idx = parseInt($(this).data('ppay-idx'), 10);
            const visible = $(this).is(':checked');
            if (!visible) {
                let n = 0;
                PPAY_COLUMN_KEYS.forEach(function(_, i) {
                    if (dataTable.column(i).visible()) n++;
                });
                if (n <= 1) {
                    $(this).prop('checked', true);
                    return;
                }
            }
            dataTable.column(idx).visible(visible, true);
            ppaySaveColumnVisibility();
            dataTable.columns.adjust().draw(false);
        });

        $('.form-select').select2();

        $('#paymentDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

        $('#paymentDateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#paymentDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
        });

        $('#clearFilter').on('click', function() {
            $('#reportFilterForm')[0].reset();
            $('.form-select').val(null).trigger('change');
            $('#paymentDateRange').val('');
            dataTable.ajax.url(apiUrl).load();
        });

        $('#purchasePaymentExportExcelBtn').on('click', function() {
            window.location.href = purchasePaymentFullExportUrl(purchasePaymentExportExcelUrl);
        });
        $('#purchasePaymentExportPdfBtn').on('click', function() {
            window.location.href = purchasePaymentFullExportUrl(purchasePaymentExportPdfUrl);
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
                    data: 'payment_ref_no',
                    name: 'payment_ref_no'
                },
                {
                    data: 'establishment_name',
                    name: 'establishment_name'
                },
                {
                    data: 'device_name',
                    name: 'device_name'
                },
                {
                    data: 'supplier',
                    name: 'c.name'
                },
                {
                    data: 'paid_on',
                    name: 'paid_on'
                },
                {
                    data: 'final_total',
                    name: 'final_total'
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'remaining_amount',
                    name: 'remaining_amount'
                },
                {
                    data: 'method',
                    name: 'method'
                },
                {
                    data: 'payment_status',
                    name: 't.payment_status'
                },
                {
                    data: 'ref_no',
                    name: 'ref_no'
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
