@extends('establishment::layouts.master')

@section('title', __('menuItemLang.establishments'))
@section('content')
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#establishments_table_tab">@lang('establishment::general.establishments_table')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#establishments_tree_tab">@lang('establishment::general.establishments_tree')</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="establishments_table_tab" role="tabpanel">
                <x-establishment::establishments.table :columns=$columns />
            </div>
            <div class="tab-pane fade show" id="establishments_tree_tab" role="tabpanel">
                <div class="px-20 d-flex justify-content-end gap-2">
                    <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="expand_all">@lang('accounting::lang.expand_all')</button>
                    <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold"
                        id="collapse_all">@lang('accounting::lang.collapse_all')</button>
                </div>
                <div id="est_tree">
                    <ul>
                        @foreach ($establishments as $establishment)
                            <x-establishment::establishments.tree :establishment=$establishment :name="get_name_by_lang()" />
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>

    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_establishment_table');
        const dataUrl = '{{ route('establishments.index') }}';
        $(document).ready(function() {
            $('#est_tree').jstree({
                "core": {
                    "themes": {
                        "responsive": true,
                        "variant": "large"
                    },
                },
                "types": {
                    "default": {
                        "icon": "fa fa-folder"
                    },
                    "file": {
                        "icon": "fa fa-file"
                    }
                },
                "plugins": ["types", "search"]
            });

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

        $(document).on('click', '#expand_all', function(e) {
            $('#est_tree').jstree("open_all");
        })
        $(document).on('click', '#collapse_all', function(e) {
            $('#est_tree').jstree("close_all");
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
                        data: 'is_main',
                        name: 'is_main'
                    },
                    {
                        data: 'parent_id',
                        name: 'parent_id'
                    },
                    {
                        data: 'city',
                        name: 'city'
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
