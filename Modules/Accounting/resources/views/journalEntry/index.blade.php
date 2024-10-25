@extends('layouts.app')

@section('title', __('accounting::lang.journalEntry'))
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
            <x-tables.table-header model="journalEntry" url="journal-entry-create" module="accounting">
                <x-slot:filters>
                    {{-- <x-tables.filters-dropdown /> --}}
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="journalEntry" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="journalEntry" module="accounting" />
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
        const table = $('#kt_journalEntry_table');
        const dataUrl = '{{ route('journal-entry-index') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_journalEntry_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();

        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var ref_no = $(this).data('ref_no');
            let deleteUrl =

                `{{ url('/journal-entry-destroy/${id}') }}`;

            showAlert(`{{ __('accounting::general.delete_confirm', ['ref_no' => ':ref_no']) }}`.replace(':ref_no',
                    ref_no),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'get');
                    dataTable.ajax.url('{{ route('journal-entry-index') }}').load();

                }
            });
        });


        // function showAlert(text, confirmButtonText, cancelButtonText = '', confirmButton = 'btn-danger', cancelButton =
        //     false, icon) {
        //     return Swal.fire({
        //         text: text,
        //         icon: icon,
        //         showCancelButton: cancelButton,
        //         buttonsStyling: false,
        //         confirmButtonText: confirmButtonText,
        //         cancelButtonText: cancelButtonText,
        //         customClass: {
        //             confirmButton: `btn fw-bold ${confirmButton}`,
        //             cancelButton: "btn fw-bold btn-active-light-primary"
        //         }
        //     });
        // }

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
                        data: 'operation_date',
                        name: 'operation_date'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
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
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
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

                dataTable.ajax.url('{{ route('journal-entry-index') }}?' + $.param({
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
