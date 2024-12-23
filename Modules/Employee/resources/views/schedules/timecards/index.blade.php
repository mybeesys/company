@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="timecard" url="schedule/timecard/create" module="employee" :search=false>
                <x-slot:filters>
                    <x-tables.filters-dropdown>
                        <x-employee::timecards.filters />
                    </x-tables.filters-dropdown>
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="timecard" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="timecard" module="employee" />
        </x-cards.card-body>
    </x-cards.card>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_timecard_table');
        const dataUrl = '{{ route('schedules.timecards.index') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4], '#kt_timecard_table', "{{ session('locale') }}", []);
            handleSearchDatatable();
            handleFormFiltersDatatable();
            $('#date').flatpickr();
            $('[name="employee_status"]').select2({
                minimumResultsForSearch: -1,
            });
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl =
                `{{ url('schedule/timecard/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    '{{ __('employee::general.this_timecard') }}'),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        dataTable.ajax.reload();
                    });
                }
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
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'employee',
                        name: 'employee',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'clock_in_time',
                        name: 'clock_in_time',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'clock_out_time',
                        name: 'clock_out_time',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'hours_worked',
                        name: 'hours_worked',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'overtime_hours',
                        name: 'overtime_hours',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6',
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
            const date = $('#date');
            const status = $('[data-kt-filter="employee_status"]');

            filters.on('click', function(e) {
                const dateValue = date.val();
                const statusValue = status.val();

                dataTable.ajax.url(dataUrl + '?' + $.param({
                    date: dateValue,
                    employee_status: statusValue
                })).load();
            });

            resetButton.on('click', function(e) {
                status.val(null).trigger('change');
                date.val(null).trigger('change');
                dataTable.ajax.url(dataUrl)
                    .load();
            });
        };
    </script>
@endsection
