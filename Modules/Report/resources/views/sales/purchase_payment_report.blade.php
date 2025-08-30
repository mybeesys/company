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
</style>
@stop

@section('content')

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">

        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <h3>@lang('menuItemLang.purchase-payment-report')</h3>
                    </div>
                </div>

                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                    <x-tables.table-header model="ProductSales" url="create-purchases-invoice" :addButton="false" module="report">
                        <x-slot:export>
                            <x-tables.export-menu id="purchases" />
                        </x-slot:export>
                    </x-tables.table-header>
                </div>
            </x-cards.card-header>

            {{-- Inline Filters --}}
            <div class="card-body border-top p-5">
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

            <x-cards.card-body class="table-responsive">
                <x-tables.table :columns="$columns" model="ProductSales" module="report" :idColumn="false" />
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
<script type="text/javascript" src="/vfs_fonts.js"></script>

<script>
    "use strict";
    let dataTable;
    const table = $('#kt_ProductSales_table');
    let currentLang = "{{ app()->getLocale() }}";
    let apiUrl = "{{ route('purchase-payment-report') }}";

    function getFilterParams() {
        const branchId = $('#branchFilter').val() || [];
        const deviceId = $('#deviceFilter').val() || [];
        const supplierId = $('#supplierFilter').val() || [];
        const paymentMethod = $('#paymentMethodFilter').val() || [];
        const paymentStatus = $('#paymentStatusFilter').val() || [];
        const dateRange = $('#paymentDateRange').val();

        const queryParams = {
            branch_id: branchId,
            device_id: deviceId,
            supplier_id: supplierId,
            payment_method: paymentMethod,
            payment_status: paymentStatus,
            payment_date_range: dateRange
        };
        return queryParams;
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
                    const supplierSelect = $('#deviceFilter');
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

    $(document).ready(function() {
        if (!table.length) return;

        populateBranches();
        populateSuppliers();
        populateDevices();
        initDatatable();
        exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], '#kt_ProductSales_table');
        handleSearchDatatable();

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
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [],
            scrollX: true,
            pageLength: 10,
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });
    };
</script>
@endsection