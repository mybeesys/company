@extends('layouts.app')

@section('title', __('menuItemLang.customer_receipts'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')


    {{-- @if (count($transactions) == 0)
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
                        <span class="fw-bolder"> @lang('sales::lang.You do not have any Receipts')</span> <br>
                        @lang('sales::lang.create_suggestion_Receipts')
                    </h4>
                    <a href="{{ route('create-receipts') }}"
                        class="btn btn-primary fv-row flex-md-root my-3 min-w-150px mw-250px">@lang('sales::general.add_receipts')</a>
                </div>

            </div>
        </div>
    @else --}}
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="receipts" url="create-receipts" module="sales" :addButton="false">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:export>
                    <a class="btn btn-primary fv-row flex-md-root min-w-150px mw-150px" data-bs-toggle="modal"
                        data-bs-target="#payment-vouchers-Modal">
                        @lang('sales::general.add_receipts')
                    </a>
                    <x-tables.export-menu id="sell" />

                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="sell" module="accounting" />
        </x-cards.card-body>
    </div>
    {{-- @endif --}}


    @include('accounting::payment-vouchers.payment-vouchers-Modal')



@stop

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_sell_table');;
        const dataUrl = '{{ route('payment-vouchers') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_sell_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();

            $('#cash_account').select2({
              
            });
        });




        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: dataUrl,

                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'account',
                        name: 'account'
                    },
                    {
                        data: 'operation_date',
                        name: 'operation_date'
                    },

                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'created_by',
                        name: 'created_by'
                    },
                    {
                        data: 'note',
                        name: 'note'
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

                dataTable.ajax.url('{{ route('payment-vouchers') }}?' + $.param({
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
