@extends('layouts.app')

@section('title', __('menuItemLang.product-purchase-report'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')

    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="ProductSales" url="create-purchases-invoice" :addButton="false" module="report">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:export>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="bi bi-funnel fs-2"></i>
                    </button>
                    <button type="button" class="btn btn-warning" id="clearFilter">@lang('sales::lang.Remove filter')</button>


                    <x-tables.export-menu id="purchases" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="ProductSales" module="report" :idColumn="false" />
        </x-cards.card-body>
    </div>



    {{-- @include('general::filter-sales-purchases.filterModal') --}}




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
        const table = $('#kt_ProductSales_table');;
        const dataUrl = '{{ route('product-purchase-report') }}';
        let currentLang = "{{ app()->getLocale() }}";
        let dueDateRangeValue = '';
        let sale_date_range = '';
        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_ProductSales_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();
            $('#favorite-filter').change(function() {
                dataTable.ajax.reload();
            });
        });




        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,
                    data: function(d) {
                        // d.favorite = $('#favorite-filter').val();
                        // d.customer = $('#customer').val();
                        // d.payment_status = $('#payment_status').val();
                        // d.due_date_range = dueDateRangeValue;
                        // d.sale_date_range = sale_date_range;
                    }
                },
                info: false,

                columns: [
                    {
                        data: 'product_name',
                        name: 'product_name'
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

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const status = $('[data-kt-filter="status"]');
            const deleted = $('[data-kt-filter="deleted_records"]');

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url('{{ route('product-purchase-report') }}?' + $.param({
                    deleted_records: deletedValue
                })).load();

                const statusValue = status.val();
                dataTable.column(6).search(statusValue).draw();
            });

            resetButton.on('click', function(e) {
                status.val(null).trigger('change');
                deleted.val(null).trigger('change');
                dataTable.search('').columns().search('').ajax.url(dataUrl)
                    .load();
            });
        };
    </script>
@endsection
