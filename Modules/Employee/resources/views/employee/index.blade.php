@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                    <input type="text" data-kt-filter="search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="@lang('employee::general.employee_search')" />
                </div>
                <div id="kt_employee_table_export" class="d-none"></div>
            </div>
            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                    <!--begin::Export dropdown-->
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
                        @lang('employee::general.report_export')
                    </button>
                    <!--begin::Menu-->
                    <div id="kt_employee_table_export_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="excel">
                                @lang('employee::general.export_as_excel')
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="pdf">
                                @lang('employee::general.export_as_pdf')
                            </a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="print">
                                @lang('employee::general.print')
                            </a>
                        </div>
                    </div>
                    <!--end::Menu-->
                    <!--end::Export dropdown-->

                    <!--begin::Hide default export buttons-->
                    <div id="kt_employee_table_buttons" class="d-none"></div>
                    <!--end::Hide default export buttons-->
                </div>

                <a href={{ url('/employee/create') }} class="btn btn-primary">@lang('employee::general.add_employee')
                </a>
            </div>
        </div>

        <div class="card-body pt-0 table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_employee_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-75px text-start">@lang('employee::fields.number')</th>
                        <th class="min-w-150px">@lang('employee::fields.first_name')</th>
                        <th class="min-w-150px">@lang('employee::fields.last_name')</th>
                        <th class="text-start min-w-150px">@lang('employee::fields.phone')</th>
                        <th class="text-start min-w-150px text-nowrap">@lang('employee::fields.employment_start_date')</th>
                        <th class="text-start min-w-150px text-nowrap">@lang('employee::fields.employment_end_date')</th>
                        <th class="text-start min-w-100px">@lang('employee::fields.status')</th>
                        <th class="text-start min-w-70px">@lang('employee::fields.actions')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";

        $(document).ready(function() {
            KTEmployeeTable.init();
        });

        pdfMake.fonts = {
            Arial: {
                normal: 'ARIAL.TTF',
                bold: 'ARIALBD.TTF',
                italics: 'ARIALI.TTF',
                bolditalics: 'ARIALBI.TTF'
            }
        };
        var KTEmployeeTable = (function() {
            var table;
            var datatable;

            var initDatatable = function() {
                datatable = $(table).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('employees.index') }}', // Update the route accordingly
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

            var exportButtons = function() {
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

            var handleSearchDatatable = function() {
                const filterSearch = $('[data-kt-filter="search"]');
                filterSearch.on('keyup', function(e) {
                    datatable.search(e.target.value).draw();
                });
            };

            return {
                init: function() {
                    table = $('#kt_employee_table');
                    if (!table.length) {
                        return;
                    }
                    initDatatable();
                    exportButtons();
                    handleSearchDatatable();
                }
            };
        })();
    </script>
@endsection
