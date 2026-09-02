@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))
@section('css')
@parent
<style>
    #kt_employee_table thead th {
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }

    #kt_employee_table tbody td {
        vertical-align: middle;
        padding-top: 0.55rem;
        padding-bottom: 0.55rem;
        height: auto !important;
        line-height: 1.35;
    }

    #kt_employee_table tbody tr {
        height: auto !important;
    }

    #kt_employee_table .emp-row-profile {
        max-width: 260px;
    }

    #kt_employee_table .emp-row-profile .symbol {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
    }

    #kt_employee_table .emp-row-profile .emp-avatar {
        width: 40px;
        height: 40px;
        object-fit: cover;
        display: block;
    }

    #kt_employee_table .emp-row-profile .d-flex.flex-column {
        min-width: 0;
    }

    #kt_employee_table .emp-contact {
        display: inline-block;
        font-variant-numeric: tabular-nums;
    }

    #kt_employee_table .emp-period {
        min-width: 110px;
    }

    #kt_employee_table .dataTables_empty {
        padding: 2rem 1rem;
    }
</style>
@endsection
@section('content')
<x-cards.card>
    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
        <x-tables.table-header model="employee" url="employee/create" :addButton="dashboard_can(\Modules\Employee\Support\EmployeePermissions::EMPLOYEE_CREATE)" module="employee">
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
        <x-tables.table :columns=$columns model="employee" module="employee" :idColumn="false" />
    </x-cards.card-body>
</x-cards.card>
<div id="print-area" style="display: none;"></div>
<x-employee::employees.edit-pos-employee-permissions-modal :permissions=$permissions :posRoles=$posRoles :establishments=$establishments />
<x-employee::employees.edit-dashboard-employee-permissions-modal :modules=$modules :permissions=$permissions :dashboardRoles=$dashboardRoles />
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
    const dataUrl = '{{ route("employees.index") }}';

    $(document).ready(function() {

        if (!table.length) return;
        initDatatable();
        exportButtons([0, 1, 2, 3], '#kt_employee_table', "{{ session('locale') }}", [], []);
        handleSearchDatatable();
        handleFormFiltersDatatable();
        $('[name="status"], [name="deleted_records"]').select2({
            minimumResultsForSearch: -1
        });
        dashboardRolePermissionsForm();
        assignPosPermissionsToEmployee("{{ url('/permission/get-employee-pos-permissions/') }}",
            "{{ url('/permission/:id/assign-pos-permissions') }}");
        assignDashboardPermissionsToEmployee("{{ url('/permission/get-employee-dashboard-permissions/') }}",
            "{{ url('/permission/:id/assign-dashboard-permissions') }}");

        $('#employee_dashboard_permissions_edit').on('shown.bs.modal', function() {
            if (typeof emsPermissionsUi === 'function') {
                emsPermissionsUi();
            }
            if (typeof initDashboardPermissionHints === 'function') {
                initDashboardPermissionHints(this);
            }
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
                    data: 'employee',
                    name: 'name',
                },
                {
                    data: 'contact',
                    name: 'phone_number',
                },
                {
                    data: 'employment_period',
                    name: 'employment_start_date',
                },
                {
                    data: 'pos_is_active',
                    name: 'pos_is_active',
                    className: 'text-center',
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                }
            ],
            order: [[0, 'asc']],
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

            dataTable.ajax.url('{{ route("employees.index") }}?' + $.param({
                deleted_records: deletedValue
            })).load();

            const statusValue = status.val();
            dataTable.column(3).search(statusValue).draw();
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