@extends('employee::layouts.master')

@section('title', __('menuItemLang.dashboard_roles'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="dashboard_role" url="dashboard-role" module="employee">
                <x-slot:export>
                    <x-tables.export-menu id="dashboard_role" />
                </x-slot:export>
                </x-employee::tables.table-header>
                </x-employee::card-header>
                <x-cards.card-body class="table-responsive">
                    <x-tables.table :columns=$columns model="dashboard_role" />
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
        const table = $('#kt_dashboard_role_table');
        const dataUrl = '{{ route('dashboard-roles.index') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3], '#kt_dashboard_role_table');
            handleSearchDatatable();
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl =
                `{{ url('/dashboard-roles/${id}') }}`;

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
                        className: 'text-start'
                    },
                    {
                        data: 'permissionSetName',
                        name: 'permissionSetName'
                    },
                    {
                        data: 'rank',
                        name: 'rank'
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
    </script>
@endsection
