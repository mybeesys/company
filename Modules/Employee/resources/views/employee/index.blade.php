@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <div class="card card-flush">
        <x-employee::card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-employee::employees.table-header />
        </x-employee::card-header>
        <x-employee::card-body class="table-responsive">
            <x-employee::employees.table />
        </x-employee::card-body>
    </div>
@endsection

@section('script')
    @parent
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_employee_table');
        const dataUrl = '{{ route('employees.index') }}';

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
            initDatatable();
            exportButtons();
            handleSearchDatatable();
            handleFormFiltersDatatable();
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
                columns: [{
                        data: 'id',
                        name: 'id',
                        className: 'text-start'
                    },
                    {
                        data: 'firstName',
                        name: 'firstName'
                    },
                    {
                        data: 'lastName',
                        name: 'lastName'
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

        function exportButtons() {
            new $.fn.dataTable.Buttons(table, {
                buttons: [{
                        extend: 'excelHtml5',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        },
                    },
                    {
                        extend: 'pdf',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        },
                        customize: function(doc) {
                            doc.defaultStyle.font = 'Arial';
                        },
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6]
                        },
                    },
                ]
            }).container().appendTo($('#kt_employee_table_buttons'));

            const exportButtons = $('#kt_employee_table_export_menu [data-kt-export]');
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

        function ajaxRequest(url, method) {
            $.ajax({
                url: url,
                data: {
                    "_token": "{{ csrf_token() }}"
                },
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
                dataTable.ajax.reload();
            }
        }
    </script>
@endsection
