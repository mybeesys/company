@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            @include('employee::components.employees.table-header')
        </div>
        <div class="card-body pt-0 table-responsive">
            @include('employee::components.employees.table')
        </div>
    </div>
@endsection

@section('script')
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

            Swal.fire({
                text: `{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    name),
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "{{ __('employee::general.delete') }}",
                cancelButtonText: "{{ __('employee::general.cancel') }}",
                customClass: {
                    confirmButton: "btn fw-bold btn-danger",
                    cancelButton: "btn fw-bold btn-active-light-primary"
                }
            }).then(function(t) {
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
                error: handleAjaxError
            });
        }

        function handleAjaxError(xhr, status, error) {
            Swal.fire({
                text: "{{ __('employee::responses.something_wrong_happened') }}",
                icon: "error",
                confirmButtonText: "{{ __('employee::general.try_again') }}",
                customClass: {
                    confirmButton: "btn btn-danger"
                }
            });
        }

        function handleAjaxResponse(response) {
            if (response.error) {
                Swal.fire({
                    text: response.error,
                    icon: "error",
                    confirmButtonText: "{{ __('employee::responses.try_again') }}",
                    customClass: {
                        confirmButton: "btn btn-danger"
                    }
                });
            } else {
                Swal.fire({
                    text: response.message,
                    icon: "success",
                    confirmButtonText: "{{ __('employee::general.close') }}",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
                dataTable.ajax.reload();
            }
        }
    </script>
@endsection
