@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <div class="card card-flush">
        <x-employee::card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-employee::tables.table-header model="role">
                <x-slot:export>
                    <x-employee::tables.export-menu id="role" />
                </x-slot:export>
                <a href='#' data-bs-toggle="modal" data-bs-target="#kt_modal_add_role"
                    class="btn btn-primary">@lang('employee::general.add_role')
                </a>
            </x-employee::tables.table-header>
        </x-employee::card-header>
        <x-employee::card-body class="table-responsive">
            <x-employee::tables.table :columns=$columns model="role" />
        </x-employee::card-body>
    </div>
    <x-employee::roles.add-edit-role-modal :departments=$departments action="add" />
    <x-employee::roles.add-edit-role-modal :departments=$departments action="edit" />
@endsection

@section('script')
    @parent
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_role_table');
        const dataUrl = '{{ route('roles.index') }}';

        pdfMake.fonts = {
            Arial: {
                normal: 'ARIAL.TTF',
                bold: 'ARIALBD.TTF',
                italics: 'ARIALI.TTF',
                bolditalics: 'ARIALBI.TTF'
            }
        };

        const errorAlert = function() {
            return showAlert(
                "{{ __('employee::responses.something_wrong_happened') }}",
                "{{ __('employee::general.try_again') }}",
                undefined, undefined,
                false, "error"
            );
        };

        $(document).ready(function() {
            if (!table.length) return;
            form('add_role_form', "{{ route('roles.create.validation') }}");
            initDatatable();
            exportButtons();
            handleSearchDatatable();
        });

        $(document).on('click', '.restore-btn', function(e) {
            var id = $(this).data('id');
            ajaxRequest(`{{ url('/employee/restore/${id}') }}`, 'POST');
        })

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl =
                `{{ url('/role/${id}') }}`;

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

        function exportButtons() {
            new $.fn.dataTable.Buttons(table, {
                buttons: [{
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [0, 1, 2, 3]
                        },
                    },
                    {
                        extend: 'pdf',
                        exportOptions: {
                            columns: [0, 1, 2, 3]
                        },
                        customize: function(doc) {
                            doc.defaultStyle.font = 'Arial';
                        },
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: [0, 1, 2, 3]
                        },
                    },
                ]
            }).container().appendTo($('#kt_role_table_buttons'));

            const exportButtons = $('#kt_role_table_export_menu [data-kt-export]');
            exportButtons.on('click', function(e) {
                e.preventDefault();
                const exportValue = $(this).attr('data-kt-export');
                $('.dt-buttons .buttons-' + exportValue).click();
            });
        };

        function handleSearchDatatable() {
            const filterSearch = $('[data-kt-filter="search"]');
            filterSearch.on('keyup', function(e) {
                dataTable.search(e.target.value).draw();
            });
        };

        function ajaxRequest(url, method, data = {}) {
            data._token = "{{ csrf_token() }}";
            $.ajax({
                url: url,
                data: data,
                dataType: "json",
                type: method,
                success: handleAjaxResponse,
                error: errorAlert
            });
        }

        function handleAjaxError(xhr, status, error) {
            errorAlert;
        }

        function handleAjaxResponse(response) {
            if (response.error) {
                errorAlert;
            } else {
                showAlert(response.message, "{{ __('employee::general.close') }}", undefined, "btn-primary", false,
                    "success")
                $('#kt_modal_add_role').modal('hide');
                $('#kt_modal_edit_role').modal('hide');
                dataTable.ajax.reload();
            }
        }

        $('#add_role_form').submit(function(e) {
            e.preventDefault();
            const addUrl = "{{ route('roles.store') }}";
            ajaxRequest(addUrl, 'POST', {
                name: $('#name').val(),
                rank: $('#rank').val(),
                department: $('#department').val()
            });
        });

        $('#edit_role_form').submit(function(e) {
            e.preventDefault();
            const id = $('#kt_modal_edit_role #role_id').val();
            const updateUrl = "{{ route('roles.update', ':id') }}".replace(':id', id);
            ajaxRequest(updateUrl, 'PATCH', {
                name: $("#kt_modal_edit_role #name").val(),
                rank: $("#kt_modal_edit_role #rank").val(),
                department: $("#kt_modal_edit_role #department").val()
            });
        });

        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            $("#kt_modal_edit_role #role_id").val($(this).data('id'));
            $("#kt_modal_edit_role #name").val($(this).data('name'));
            $("#kt_modal_edit_role #rank").val($(this).data('rank'));
            $("#kt_modal_edit_role #department").val($(this).data('department'));
        });
    </script>
@endsection
