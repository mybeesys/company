@extends('layouts.app')

@section('title', __('menuItemLang.product-inventory-report'))
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
            <x-slot:export>
                <x-tables.export-menu id="ProductSales" />
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
<script type="text/javascript" src="/vfs_fonts.js"></script>

<script>
    "use strict";
    let dataTable;
    const table = $('#kt_ProductSales_table');;
    const dataUrl = "{{ route('product-inventory-report') }}";
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

                },
                /*success: function(data) {
                    console.log('Data received from server:', data); // Log the response
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error); // Log the error message
                    console.error('Status:', status); // Log the status
                    console.error('XHR:', xhr); // Log the entire XHR object for more details

                    // Optionally, log the response text for additional context
                    if (xhr.responseText) {
                        console.error('Response Text:', xhr.responseText);
                    }
                }*/
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
                    data: 'process',
                    name: 'process'
                },
                {
                    data: 'quantity',
                    name: 'quantity'
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

    function handleFormFiltersDatatable() {
        const filters = $('[data-kt-filter="filter"]');
        const resetButton = $('[data-kt-filter="reset"]');
        const status = $('[data-kt-filter="status"]');
        const deleted = $('[data-kt-filter="deleted_records"]');

        filters.on('click', function(e) {
            const deletedValue = deleted.val();

            dataTable.ajax.url("{{ route('product-inventory-report') }}?" + $.param({
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