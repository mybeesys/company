@extends('layouts.app')

@section('title', __('accounting::lang.journalEntry'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        /* Make journal table compact to avoid horizontal scroll */
        #kt_journalEntry_table {
            table-layout: auto;
            width: 100% !important;
        }

        #kt_journalEntry_table th,
        #kt_journalEntry_table td {
            white-space: normal !important;
            word-break: break-word;
        }
    </style>


@stop
@section('content')

    <div class="row g-3 align-items-end">

        <div class="col-md-3">
            <label>{{ __('accounting::lang.from_date') }}</label>
            <input type="date" id="from_date" class="form-control" />
        </div>
        <div class="col-md-3">
            <label>{{ __('accounting::lang.to_date') }}</label>
            <input type="date" id="to_date" class="form-control" />
        </div>


        <div class="col-md-3">
            <label>{{ __('accounting::lang.created_by') }}</label>
            <select id="created_by_filter" class="form-select" data-control="select2">
                <option value="">{{ __('accounting::lang.all') }}</option>
                @foreach (Modules\Employee\Models\Employee::select('id', 'name')->get() as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex justify-content-end gap-2">
            <button type="button" id="filter_button" class="btn btn-primary">
                {{ __('accounting::lang.filter') }}
            </button>
            <button type="button" id="reset_button" class="btn btn-light">
                {{ __('accounting::lang.reset') }}
            </button>
        </div>
    </div>
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="journalEntry" url="journal-entry-create" module="accounting">
                <x-slot:filters>

                </x-slot:filters>
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


        function handleFormFiltersDatatable() {
            $('#filter_button').on('click', function() {
                const from_date = $('#from_date').val();
                const to_date = $('#to_date').val();
                const created_by = $('#created_by_filter').val();

                dataTable.ajax.url(`{{ route('journal-entry-index') }}?` + $.param({
                    from_date,
                    to_date,
                    created_by
                })).load();
            });

            $('#reset_button').on('click', function() {
                $('#from_date, #to_date').val('');
                $('#created_by_filter').val('').trigger('change');
                dataTable.ajax.url(`{{ route('journal-entry-index') }}`).load();
            });
        }

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
                        data: 'sub_type',
                        name: 'sub_type'
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
                scrollX: false,
                responsive: true,
                autoWidth: false,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
                }
            });
        };
    </script>
@endsection
