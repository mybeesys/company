@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))
@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="employee" url="employee/create" :addButton="auth()->user()->hasDashboardPermission('employees.employee.create')" module="employee">
                <x-slot:filters>
                    <x-tables.filters-dropdown>
                        <x-employee::employees.filters />
                    </x-tables.filters-dropdown>
                </x-slot:filters>
                @can('printAll', \Modules\Employee\Models\Employee::class)
                    <x-slot:export>
                        <x-tables.export-menu id="employee" />
                    </x-slot:export>
                @endcan
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="employee" module="employee" />
        </x-cards.card-body>
    </x-cards.card>
    <div id="print-area" style="display: none;"></div>
    <x-employee::employees.edit-pos-employee-permissions-modal :permissions=$permissions />
    <x-employee::employees.edit-dashboard-employee-permissions-modal :modules=$modules />
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-dashboard-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/edit-employee-permissions.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_employee_table');
        const dataUrl = '{{ route('employees.index') }}';

        $(document).ready(function() {

            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_employee_table', "{{ session('locale') }}", [1], [0]);
            handleSearchDatatable();
            handleFormFiltersDatatable();
            $('[name="status"], [name="deleted_records"]').select2({
                minimumResultsForSearch: -1
            });
            dashboardRolePermissionsForm();
            assignPosPermissionsToEmployee("{{ url('/permission/get-employee-pos-permissions/') }}",
                "{{ route('permissions.assign.employee', ':id') }}");
            assignDashboardPermissionsToEmployee("{{ url('/permission/get-employee-dashboard-permissions/') }}",
                "{{ route('permissions.assign.user', ':id') }}");

            let tableElement = $("#dashboard-permissions-table");
            let modalTable = tableElement.DataTable({
                paging: false,
                info: false,
                responsive: true,
                ordering: false,
                autoWidth: false,
                scrollY: '400px',
                scrollCollapse: true,
            });

            $('#employee_dashboard_permissions_edit').on('shown.bs.modal', function() {
                modalTable.columns.adjust();
                $(window).on('resize', function() {
                    modalTable.columns.adjust();
                });
            });


        });

        $(document).on('click', '.restore-btn', function(e) {
            var id = $(this).data('id');
            ajaxRequest(`{{ url('/employee/restore/${id}') }}`, 'POST').done(function() {
                dataTable.ajax.reload();
            });
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
                        data: 'employee_image',
                        name: 'employee_image'
                    },
                    {
                        data: 'phone_number',
                        name: 'phone_number'
                    },
                    {
                        data: 'employment_start_date',
                        name: 'employment_start_date'
                    },
                    {
                        data: 'employment_end_date',
                        name: 'employment_end_date'
                    },
                    {
                        data: 'pos_is_active',
                        name: 'pos_is_active'
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

        $(document).on('click', '.print-btn', function(event) {
            event.preventDefault();

            const printUrl = "{{ url('/employee/:employeeId/print') }}";
            const employeeId = $(this).data('id');
            const url = printUrl.replace(':employeeId', employeeId);

            ajaxRequest(url, "GET", {}, false, false, false).done(function(response) {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'absolute';
                iframe.style.top = '-9999px';
                document.body.appendChild(iframe);

                iframe.contentDocument.open();
                iframe.contentDocument.write(response);
                iframe.contentDocument.close();

                iframe.contentWindow.focus();
                iframe.contentWindow.print();

                setTimeout(() => {
                    document.body.removeChild(iframe);
                }, 1000);
            }).fail(function() {
                console.error('Error fetching print view:', error);
            });
        });
    </script>
@endsection
