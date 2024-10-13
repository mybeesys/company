@extends('employee::layouts.master')

@section('title', __('menuItemLang.pos_roles'))

@section('content')
    <div class="card card-flush">
        <x-employee::card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-employee::tables.table-header model="role" url="pos-role">
                <x-slot:export>
                    <x-employee::tables.export-menu id="role" />
                </x-slot:export>
            </x-employee::tables.table-header>
        </x-employee::card-header>
        <x-employee::card-body class="table-responsive">
            <x-employee::tables.table :columns=$columns model="role" />
        </x-employee::card-body>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_role_table');
        const dataUrl = '{{ route('roles.index') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons();
            handleSearchDatatable();
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl =
                `{{ url('/pos-role/${id}') }}`;

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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'department',
                        name: 'department'
                    },
                    {
                        data: 'rank',
                        name: 'rank'
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
