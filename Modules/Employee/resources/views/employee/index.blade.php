@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <!--begin::Products-->
    <div class="card card-flush" {{-- dir="rtl" direction="rtl" style="direction:rtl;" --}}>
        <!--begin::Card header-->
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                    <input type="text" data-kt-filter="search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="@lang('employee::general.employee_search')" />
                </div>
                <!--end::Search-->
                <!--begin::Export buttons-->
                <div id="kt_employee_table_export" class="d-none"></div>
                <!--end::Export buttons-->
            </div>


            <!--end::Card title-->
            <!--begin::Card toolbar-->
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
                <!--begin::Add product-->
                <a href="apps/ecommerce/catalog/add-product.html" class="btn btn-primary">@lang('employee::general.add_employee')
                </a>
                <!--end::Add product-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0 table-responsive">
            <!--begin::Table-->
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_employee_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-300px">@lang('employee::fields.employee')</th>
                        <th class="text-start min-w-150px">@lang('employee::fields.phone')</th>
                        <th class="text-start min-w-150px text-nowrap">@lang('employee::fields.employment_start_date')</th>
                        <th class="text-start min-w-150px text-nowrap">@lang('employee::fields.employment_end_date')</th>
                        <th class="text-start min-w-100px">@lang('employee::fields.status')</th>
                        <th class="text-start min-w-70px">@lang('employee::fields.actions')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <a href="apps/ecommerce/catalog/edit-product.html" class="symbol symbol-50px">
                                        <span class="symbol-label"
                                            style="background-image:url(assets/media//stock/ecommerce/1.png);"></span>
                                    </a>
                                    <div class="ms-5">
                                        <a href="apps/ecommerce/catalog/edit-product.html"
                                            class="text-gray-800 text-hover-primary fs-5 fw-bold"
                                            data-kt-ecommerce-product-filter="product_name">{{ $employee->fullName }}</a>
                                    </div>
                                </div>
                            </td>
                            <td class="text-start">
                                <span class="fw-bold">{{ $employee->phoneNumber }}</span>
                            </td>
                            <td class="text-start">
                                <span class="fw-bold">{{ $employee->employmentStartDate }}</span>
                            </td>
                            <td class="text-start">{{ $employee->employmentEndtDate }}</td>

                            <td class="text-start" data-order="Inactive">

                                <!--begin::Badges-->
                                <div @class([
                                    'badge',
                                    'badge-light-success' => $employee->isActive,
                                    'badge-light-danger' => !$employee->isActive,
                                ])>
                                    {{ $employee->isActive ? __('employee::fields.active') : __('employee::fields.inActive') }}
                                </div>
                                <!--end::Badges-->
                            </td>
                            <td class="text-center">
                                <a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">@lang('employee::fields.actions')
                                    <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                <!--begin::Menu-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                    data-kt-menu="true">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="apps/ecommerce/catalog/edit-product.html"
                                            class="menu-link px-3">@lang('employee::fields.edit')</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3"
                                            data-kt-ecommerce-product-filter="delete_row">@lang('employee::fields.delete')</a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu-->
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Products-->

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
                const tableRows = $('#kt_employee_table tbody tr');

                tableRows.each(function() {
                    const dateRow = $(this).find('td');
                    const employmentStartDate = moment(dateRow.eq(2).html(), "YYYY MMM DD").format('yy-MM-DD');
                    dateRow.eq(2).attr('data-order', employmentStartDate);

                    const employmentEndDate = moment(dateRow.eq(3).html(), "YYYY MMM DD").format('yy-MM-DD');
                    dateRow.eq(3).attr('data-order', employmentEndDate);
                });

                datatable = $(table).DataTable({
                    columnDefs: [{
                        'orderable': false,
                        'targets': 5
                    }],
                    scrollX: true,
                    info: false,
                    order: [],
                    pageLength: 10,
                });
            };

            var exportButtons = function() {
                new $.fn.dataTable.Buttons(table, {
                    buttons: [{
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            },
                        },
                        {
                            extend: 'pdf',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
                            },
                            customize: function(doc) {
                                doc.defaultStyle.font = 'Arial';
                            },
                        },
                        {
                            extend: 'print',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4]
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

