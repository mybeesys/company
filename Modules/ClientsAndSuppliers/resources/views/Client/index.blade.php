@extends('layouts.app')

@section('title', __('clientsandsuppliers::lang.' . $business_type))
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
            <x-tables.table-header model="{{ $business_type }}" url="{{ $create_url }}" module="clientsandsuppliers">
                <x-slot:filters>
                    {{-- <x-tables.filters-dropdown /> --}}
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="{{ $business_type }}" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="{{ $business_type }}" module="clientsandsuppliers" />
        </x-cards.card-body>
    </div>

@stop

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    {{-- <script type="text/javascript" src="vfs_fonts.js"></script> --}}
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_{{ $business_type }}_table');;
        const dataUrl = '{{ route("$business_type") }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_{{ $business_type }}_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();

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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'mobile_number',
                        name: 'mobile_number'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'commercial_register',
                        name: 'commercial_register'
                    },
                    {
                        data: 'tax_number',
                        name: 'tax_number'
                    },
                    {
                        data: 'status',
                        name: 'status'
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

                dataTable.ajax.url('{{ route("$business_type") }}?' + $.param({
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
