@extends('layouts.app')

@section('title', __('menuItemLang.product-inventory-recoed'))

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
        <div class="row g-5 g-xl-8 mb-5">
            {{-- المخزون الافتتاحي --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.opening_inventory')</div>
                        <div class="fs-5 fw-bolder text-gray-800">{{ $openingInventory }}</div>
                    </div>
                </div>
            </div>

            {{-- كمية الشراء --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.purchased_quantity')</div>
                        <div class="fs-5 fw-bolder text-primary">{{ $purchasedQuantity }}</div>
                    </div>
                </div>
            </div>

            {{-- الكمية المباعة --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.sales_quantity')</div>
                        <div class="fs-5 fw-bolder text-danger">{{ $salesQuantity }}</div>
                    </div>
                </div>
            </div>

            {{-- التالف (Waste) --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.waste')</div>
                        <div class="fs-5 fw-bolder text-warning">{{ $waste }}</div>
                    </div>
                </div>
            </div>

            {{-- مردود المشتريات --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.purchase_returns')</div>
                        <div class="fs-5 fw-bolder text-info">{{ $purchaseReturns }}</div>
                    </div>
                </div>
            </div>

            {{-- المحولة  --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.transferred_quantity')</div>
                        <div class="fs-5 fw-bolder text-success">{{ $transferredQuantity }}</div>
                    </div>
                </div>
            </div>

            {{-- المنتجة (PREP) --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.production_quantity')</div>
                        <div class="fs-5 fw-bolder text-dark">{{ $productionQuantity }}</div>
                    </div>
                </div>
            </div>

            {{-- المجرودة (Audited/Stock Count) --}}
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card card-flush h-md-100">
                    <div class="card-body">
                        <div class="fw-bold text-gray-400 mb-1">@lang('report::fields.counted_quantity')</div>
                        <div class="fs-5 fw-bolder text-muted">0</div>
                    </div>
                </div>
            </div>

            {{-- الكمية في المخزون (Closing Stock) --}}
            <div class="col-xl-4 col-md-8 col-sm-12">
                <div class="card card-flush h-md-100 bg-success bg-opacity-10">
                    <div class="card-body">
                        <div class="fw-bold text-gray-800 mb-1">@lang('report::fields.quantity_on_inventory')</div>
                        <div class="fs-5 fw-bolder text-success">{{ $quantityOnInventory }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <h3>@lang('menuItemLang.product-inventory-record')</h3>
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
                <form id="inventoryFilterForm">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <div class="row g-5">
                                <div class="col-md-6">
                                    <label for="inventoryDateRange" class="form-label">@lang('report::purchase.Date Range')</label>
                                    <input type="text" class="form-control form-control-solid" id="inventoryDateRange" name="inventory_date_range" />
                                </div>
                                <div class="col-md-6">
                                    <label for="processTypeFilter" class="form-label">@lang('report::purchase.Process Type')</label>
                                    <select class="form-select form-select-solid" id="processTypeFilter" name="process_type[]" data-control="select2" data-placeholder="@lang('report::purchase.All Processes')" multiple>
                                        <option></option>
                                        <option value="sell">@lang('report::purchase.sell')</option>
                                        <option value="purchases">@lang('report::purchase.purchase')</option>
                                        <option value="PREP">@lang('report::purchase.prep')</option>
                                        <option value="WASTE">@lang('report::purchase.waste')</option>
                                        <option value="TRANSFER">@lang('report::purchase.transfer')</option>
                                        <option value="purchases-return">@lang('report::purchase.purchases-return')</option>
                                        <option value="sell-return">@lang('report::purchase.sell-return')</option>


                                    </select>
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
                <x-tables.table :columns=$columns model="ProductSales" module="report" :idColumn="false" />
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
    let dataUrl = "{{ route('inventory.record', ['product_id' => $product_id, 'establishment_id' => $establishment_id]) }}";


    function getFilterParams() {
        const branchId = $('#branchFilter').val() || [];
        const productId = $('#productFilter').val() || [];
        const processType = $('#processTypeFilter').val() || [];
        const dateRange = $('#inventoryDateRange').val();

        const queryParams = {
            branch_id: branchId,
            product_id: productId,
            process_type: processType,
            inventory_date_range: dateRange
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
        populateProducts();
        initDatatable();
        exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_ProductSales_table');
        handleSearchDatatable();
        $('.form-select').select2();

        $('#inventoryDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

        $('#inventoryDateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#inventoryDateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
        });

        $('#clearFilter').on('click', function() {
            $('#inventoryFilterForm')[0].reset();
            $('.form-select').val(null).trigger('change');
            $('#inventoryDateRange').val('');
            dataTable.ajax.url(dataUrl).load();
        });
    });

    function initDatatable() {
        dataTable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                data: function(d) {
                    Object.assign(d, getFilterParams());
                }
            },
            info: false,
            columns: [{
                    data: 'product_name',
                    name: 'product_name'
                },
                {
                    data: 'establishment_name',
                    name: 'establishment_name'
                },
                {
                    data: 'transfer_in_out',
                    name: 'transfer_in_out'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'quantity',
                    name: 'quantity'
                },
                {
                    data: 'entity',
                    name: 'entity'
                },

                {
                    data: 'transfer_date',
                    name: 'transfer_date'
                }, {
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