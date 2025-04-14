@extends('layouts.app')

@section('title', __('menuItemLang.sell-return'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')


    @if (count($transaction) == 0)
        <div class="card1 h-md-100 my-5" dir="ltr">
            <div class="card-body d-flex flex-column flex-center">
                <div class="mb-2 px-20" style="place-items: center;">


                    <div class="py-10 text-center">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px"
                            alt="">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px"
                            alt="">
                    </div>
                    <h4 class="fw-semibold text-gray-800 text-center  lh-lg">
                        <span class="fw-bolder"> @lang('sales::lang.no_sell_return')</span> <br>
                        @lang('sales::lang.suggestion_sell_return')
                    </h4>
                </div>

            </div>
        </div>
    @else
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <x-tables.table-header model="sell-return" url="create-sell-return-invoice" module="sales" >


                    <x-slot:filters>
                    </x-slot:filters>
                    <x-slot:export>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel fs-2"></i>
                        </button>
                        <x-tables.export-menu id="sell" />
                    </x-slot:export>
                </x-tables.table-header>
            </x-cards.card-header>

            <x-cards.card-body class="table-responsive">
                <x-tables.table :columns=$columns model="sell" module="sales" />
            </x-cards.card-body>
        </div>
    @endif


    @include('general::filter-sales-purchases.filterModal')



@stop

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_sell_table');;
        const dataUrl = '{{ route('sell-return') }}';
        let currentLang = "{{ app()->getLocale() }}";
        let dueDateRangeValue = '';
        let sale_date_range = '';
        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_sell_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();




            $("#quotation-items").select2(
                // width: "resolve",
            );
        });





        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,
                    data: function(d) {
                        d.favorite = $('#favorite-filter').val();
                        d.customer = $('#customer').val();
                        d.payment_status = $('#payment_status').val();
                        d.due_date_range = dueDateRangeValue;
                        d.sale_date_range = sale_date_range;
                    }
                },

                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'client',
                        name: 'client'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'due_date',
                        name: 'due_date'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    // {
                    //     data: 'total_before_tax',
                    //     name: 'total_before_tax'
                    // },
                    // {
                    //     data: 'tax_amount',
                    //     name: 'tax_amount'
                    // },
                    {
                        data: 'final_total',
                        name: 'final_total'
                    },
                    {
                        data: 'paid_amount',
                        name: 'paid_amount'
                    },
                    {
                        data: 'remaining_amount',
                        name: 'remaining_amount'
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

                dataTable.ajax.url('{{ route('sell-return') }}?' + $.param({
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
