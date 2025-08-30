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
</style>
@stop

@section('content')

<div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">

        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <h3>@lang('menuItemLang.product-purchase-report')</h3>
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
                                    <label for="productFilter" class="form-label">@lang('report::purchase.Product')</label>
                                    <select class="form-select form-select-solid" id="productFilter" name="product_id[]" data-control="select2" data-placeholder="@lang('report::purchase.All Products')" multiple>
                                        <option></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="saleDateRange" class="form-label">@lang('report::purchase.Sale Date Range')</label>
                                    <input type="text" class="form-control form-control-solid" id="saleDateRange" name="sale_date_range" />
                                </div>
                            </div>
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

{{-- @include('general::filter-sales-purchases.filterModal') --}}


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
    let apiUrl = "{{ route('product-purchase-report') }}";

    function getFilterParams() {
        const branchId = $('#branchFilter').val() || [];
        const supplierId = $('#supplierFilter').val() || [];
        const productId = $('#productFilter').val() || [];
        const dateRange = $('#saleDateRange').val();

        const queryParams = {
            branch_id: branchId,
            supplier_id: supplierId,
            product_id: productId,
            sale_date_range: dateRange
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

    $(document).ready(function() {
        if (!table.length) return;

        populateBranches();
        populateSuppliers();
        populateProducts();
        initDatatable();
        exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], '#kt_ProductSales_table');
        handleSearchDatatable();
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

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
        });

        $('#clearFilter').on('click', function() {
            $('#reportFilterForm')[0].reset();
            $('.form-select').val(null).trigger('change');
            $('#saleDateRange').val('');
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
                    data: 'product_name',
                    name: 'product_name',
                },
                {
                    data: 'establishment_name',
                    name: 'establishment_name'
                },
                {
                    data: 'price',
                    name: 'price'
                },
                {
                    data: 'SKU',
                    name: 'SKU'
                },
                {
                    data: 'customer',
                    name: 'customer'
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
                    data: 'sell_qty',
                    name: 'sell_qty'
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