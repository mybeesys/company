@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))
@section('content')

    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="employee" url="employee/create" module="employee">
                <x-slot:filters>
                    <x-tables.filters-dropdown>
                        <x-employee::employees.filters />
                    </x-tables.filters-dropdown>
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="employee" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="employee" module="employee" />
        </x-cards.card-body>
    </x-cards.card>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_employee_table');
        const dataUrl = '{{ route('employees.index') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_employee_table');
            handleSearchDatatable();
            handleFormFiltersDatatable();
            $('[name="status"], [name="deleted_records"]').select2({
                minimumResultsForSearch: -1
            });
        });

        $(document).on('click', '.restore-btn', function(e) {
            var id = $(this).data('id');
            ajaxRequest(`{{ url('/employee/restore/${id}') }}`, 'POST');
        })

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl = $(this).data('deleted') ?
                `{{ url('/employee/force-delete/${id}') }}` :
                `{{ url('/employee/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    name),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE');
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
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'name_en',
                        name: 'name_en'
                    },
                    {
                        data: 'phoneNumber',
                        name: 'phoneNumber'
                    },
                    {
                        data: 'employmentStartDate',
                        name: 'employmentStartDate'
                    },
                    {
                        data: 'employmentEndDate',
                        name: 'employmentEndDate'
                    },
                    {
                        data: 'isActive',
                        name: 'isActive'
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

                dataTable.ajax.url('{{ route('employees.index') }}?' + $.param({
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
