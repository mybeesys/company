@extends('establishment::layouts.master')

@section('title', __('menuItemLang.establishments'))
@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="establishment" url="establishment/create" module="establishment" :search="false">
                <x-slot:filters>
                    <x-tables.filters-dropdown>
                        <x-establishment::establishments.filters />
                    </x-tables.filters-dropdown>
                </x-slot:filters>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="establishment" module="establishment" />
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
        const table = $('#kt_establishment_table');
        const dataUrl = '{{ route('establishments.index') }}';
        $(document).ready(function() {
            initDatatable();
            handleFormFiltersDatatable();
            $('[name="status"], [name="deleted_records"]').select2({
                minimumResultsForSearch: -1
            });

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var name = $(this).data('name');
                let deleteUrl = $(this).data('deleted') ?
                    `{{ url('/establishment/force-delete/${id}') }}` :
                    `{{ url('/establishment/${id}') }}`;

                showAlert(`{{ __('establishment::general.delete_confirm', ['name' => ':name']) }}`.replace(
                        ':name',
                        name),
                    "{{ __('establishment::general.delete') }}",
                    "{{ __('establishment::general.cancel') }}", undefined,
                    true, "warning").then(function(t) {
                    if (t.isConfirmed) {
                        ajaxRequest(deleteUrl, 'DELETE').done(function() {
                            dataTable.ajax.reload();
                        });
                    }
                });
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
                        data: 'address',
                        name: 'address'
                    },
                    {
                        data: 'contact_details',
                        name: 'contact_details'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
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

        $(document).on('click', '.restore-btn', function(e) {
            var id = $(this).data('id');
            ajaxRequest(`{{ url('/establishment/restore/${id}') }}`, 'POST').done(function() {
                dataTable.ajax.reload();
            });
        })

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const status = $('[data-kt-filter="status"]');
            const deleted = $('[data-kt-filter="deleted_records"]');

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url('{{ route('establishments.index') }}?' + $.param({
                    deleted_records: deletedValue
                })).load();

                const statusValue = status.val();
                dataTable.column(4).search(statusValue).draw();
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
