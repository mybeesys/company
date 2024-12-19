@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('css')
    <style>
        .confirm-btn:hover span,
        .confirm-btn:active span {
            color: #007bff;
        }

        .confirm-btn span {
            color: #99a1b7;
        }
    </style>
@endsection

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="payroll_group" module="employee" :addButton="false">
                <x-slot:export>
                    <x-tables.export-menu id="payroll_group" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="payroll_group" module="employee" />
        </x-cards.card-body>
    </x-cards.card>
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>

    <script>
        let dataTable;
        const table = $('#kt_payroll_group_table');
        const dataUrl = '{{ route('schedules.payrolls-groups.index') }}';

        $(document).ready(function() {
            initDatatable();

        });
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = `{{ url('/schedule/payroll-group/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(
                    ':name', '{{ __('employee::general.this_payroll_group') }}'),
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

        $(document).on('click', '.confirm-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let confirmUrl = `{{ url('/schedule/payroll-group/confirm/${id}') }}`;

            showAlert(`{{ __('employee::general.payroll_group_confirm_confirmation') }}`.replace(
                    ':name', '{{ __('employee::general.this_payroll_group') }}'),
                "{{ __('employee::general.confirm') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(confirmUrl, 'POST').done(function() {
                        dataTable.destroy();
                        initDatatable();
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
                        data: 'name',
                        name: 'name',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'date',
                        name: 'date',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'state',
                        name: 'state',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'gross_total',
                        name: 'gross_total',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'net_total',
                        name: 'net_total',
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
    </script>
@endsection
