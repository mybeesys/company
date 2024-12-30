@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('css')
    @parent
    <style>
        div {
            scrollbar-width: auto;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            @can('viewAny', \Modules\Employee\Models\Payroll::class)
                <li class="nav-item">
                    <a class="nav-link nav-link-payroll justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                        href="#payroll_table_tab">@lang('menuItemLang.payroll')</a>
                </li>
            @endcan
            @can('viewAny', \Modules\Employee\Models\PayrollGroup::class)
                <li class="nav-item">
                    <a @class([
                        'nav-link nav-link-payroll-group justify-content-center text-active-gray-800',
                        'active' => !auth()->user()->hasDashboardPermission('employees.payrolls.show'),
                    ]) data-bs-toggle="tab" href="#payroll_group_tab">@lang('menuItemLang.payroll_group')</a>
                </li>
            @endcan
        </ul>
        <div class="tab-content" id="myTabContent">
            @can('viewAny', \Modules\Employee\Models\Payroll::class)
                <div class="tab-pane fade show active" id="payroll_table_tab" role="tabpanel">
                    <x-cards.card>
                        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                            <x-tables.table-header model="payroll" module="employee" :addButton="auth()->user()->hasDashboardPermission('employees.payroll.create')">
                                <x-slot:filters>
                                    <x-tables.filters-dropdown>
                                        <x-employee::payroll.filters />
                                    </x-tables.filters-dropdown>
                                </x-slot:filters>
                                @can('print', \Modules\Employee\Models\Payroll::class)
                                    <x-slot:export>
                                        <x-tables.export-menu id="payroll" />
                                    </x-slot:export>
                                @endcan
                            </x-tables.table-header>
                        </x-cards.card-header>
                        <x-cards.card-body class="table-responsive">
                            <x-tables.table :columns=[] :actionColumn="false" :idColumn="false" model="payroll" :idColumn=true
                                module="employee" />
                        </x-cards.card-body>
                    </x-cards.card>
                </div>
            @endcan
            @can('viewAny', \Modules\Employee\Models\PayrollGroup::class)
                <div @class([
                    'tab-pane fade show',
                    'active' => !auth()->user()->hasDashboardPermission('employees.payrolls.show'),
                ]) id="payroll_group_tab" role="tabpanel">
                    <x-cards.card>
                        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                            <x-tables.table-header model="payroll_group" module="employee" :addButton="false">
                                @can('print', \Modules\Employee\Models\PayrollGroup::class)
                                    <x-slot:export>
                                        <x-tables.export-menu id="payroll_group" />
                                    </x-slot:export>
                                @endcan
                            </x-tables.table-header>
                        </x-cards.card-header>
                        <x-cards.card-body class="table-responsive">
                            <x-tables.table :columns=$payroll_group_columns model="payroll_group" module="employee" />
                        </x-cards.card-body>
                    </x-cards.card>
                </div>
            @endcan
        </div>
    </div>

    <x-employee::payroll.add-payroll-modal :employees=$employees :establishments=$establishments />
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>

    <script>
        let payroll_dataTable;
        let payroll_group_dataTable;
        const payroll_table = $('#kt_payroll_table');
        const payroll_dataUrl = '{{ route('schedules.payrolls.index') }}';
        const payroll_group_table = $('#kt_payroll_group_table');
        const payroll_group_dataUrl = '{{ route('schedules.payrolls-groups.index') }}';

        $(document).ready(function() {
            initPayrollGroupDatatable();
            handleFormFiltersDatatable()
            initDatatable();
            addPayrollModal();
            $('[name="employee"]').select2({});
            $('[name="establishment"]').select2({
                minimumResultsForSearch: -1,
            });
            $('#date').flatpickr({
                plugins: [
                    monthSelectPlugin({
                        shorthand: true, // Displays the month in shorthand format (e.g., "Jan", "Feb")
                        dateFormat: "Y-m", // Format the value as "YYYY-MM"
                        altFormat: "F Y", // Displayed format, e.g., "January 2024"
                    })
                ]
            });
            $('#date_filter').flatpickr({
                plugins: [
                    monthSelectPlugin({
                        shorthand: true, // Displays the month in shorthand format (e.g., "Jan", "Feb")
                        dateFormat: "Y-m", // Format the value as "YYYY-MM"
                        altFormat: "F Y", // Displayed format, e.g., "January 2024"
                    })
                ]
            });

            $('[name="status"]').select2({
                minimumResultsForSearch: -1
            });
            $('#add_payroll_modal_form').on('submit', function(e) {
                e.preventDefault();

                let employee_ids = $('[name="employee"]').val();
                let establishment = $('[name="establishment"]').val();
                let date = $('#date').val();

                let baseUrl = `{{ route('schedules.payrolls.create') }}`;
                let queryParams =
                    `?employee_ids=${encodeURIComponent(employee_ids)}&establishment_ids=${encodeURIComponent(establishment)}&date=${encodeURIComponent(date)}`;

                window.location.href = baseUrl + queryParams;
            });

            $('#emp-select-all-btn').on('click', function() {
                $('[name="employee"]').select2('destroy');
                $('[name="employee"]').select2();
                let allValues = $('[name="employee"] option').map(function() {
                    return $(this).val();
                }).get().filter(function(value) {
                    return value !== '';
                });
                $('[name="employee"]').val(allValues).trigger('change');
            });

            $('#emp-deselect-all-btn').on('click', function() {
                $('[name="employee"]').select2('destroy');
                $('[name="employee"]').select2();
                $('[name="employee"]').val(null).trigger('change');
            });

            $('#est-select-all-btn').on('click', function() {
                $('[name="establishment"]').select2('destroy');
                $('[name="establishment"]').select2();
                let allValues = $('[name="establishment"] option').map(function() {
                    return $(this).val();
                }).get().filter(function(value) {
                    return value !== '';
                });
                $('[name="establishment"]').val(allValues).trigger('change');
            });

            $('#est-deselect-all-btn').on('click', function() {
                $('[name="establishment"]').select2('destroy');
                $('[name="establishment"]').select2();
                $('[name="establishment"]').val(null).trigger('change');
            });
        });

        function addPayrollModal() {
            $('#add_payroll_button').on('click', function(e) {
                e.preventDefault();
                $('#add_payroll_modal').modal('toggle');
            })
        }

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const date_filter = $('[name="date_filter"]');

            filters.on('click', function(e) {
                const dateFilterValue = date_filter.val();

                payroll_dataTable.ajax.url('{{ route('schedules.payrolls.index') }}?' + $.param({
                    date_filter: dateFilterValue
                })).load();
            });

            resetButton.on('click', function(e) {
                date_filter.val(null).trigger('change');
                payroll_dataTable.search('').columns().search('').ajax.url(payroll_dataUrl)
                    .load();
            });
        };

        function initDatatable() {
            fetch("{{ route('schedules.payrolls.get-columns') }}")
                .then(response => response.json())
                .then(columns => {
                    console.log(columns);
                    
                    let mainColCount = columns.filter(col => col.group === 'main').length;
                    let allowanceColCount = columns.filter(col => col.group === 'allowances').length;
                    allowanceColCount = allowanceColCount == 0 ? 1 : allowanceColCount;
                    let deductionColCount = columns.filter(col => col.group === 'deductions').length;

                    deductionColCount = deductionColCount == 0 ? 1 : deductionColCount;

                    // Create the header structure before initializing DataTable
                    const $thead = payroll_table.find('thead');
                    $thead.empty();

                    const mainHeaderRow = $('<tr></tr>');

                    mainHeaderRow.append(
                        `<th rowspan="2" class="text-start min-w-50px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.id')</th>
                        <th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.employee')</th>
                        <th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.payroll_group_name')</th>
                        <th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.date')</th>
                        <th rowspan="2" class="text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.regular_worked_hours')</th>
                        <th rowspan="2" class="text-start min-w-75px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.overtime_hours')</th>
                        <th rowspan="2" class="text-start min-w-125px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.total_hours')</th>
                        <th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.total_worked_days')</th>
                        <th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle border text-gray-800 fs-6">@lang('employee::fields.basic_total_wage')</th>
                        `
                    );
                                        
                    // Create group header row
                    mainHeaderRow.append(`  
                    <th colspan="${allowanceColCount}" class="text-center border">@lang('employee::fields.allowances')</th> 
                    <th colspan="${deductionColCount}" class="text-center border">@lang('employee::fields.deductions')</th>                    
            `);
                    mainHeaderRow.append(`
                    <th rowspan="2" class="text-start min-w-150px px-3 py-1 border align-middle text-gray-800 fs-6">@lang('employee::fields.total_wage_due')</th>
                    <th rowspan="2" class="text-center align-middle min-w-125px border">@lang('employee::fields.actions')</th>
            `);

                    $thead.append(mainHeaderRow);

                    const subHeaderRow = $('<tr></tr>');

                    const filteredColumns = columns.filter(col => col.group !== 'main');

                    const subHeader = filteredColumns.map(col =>
                        `<th class="${col.class}">${col.translated_name}</th>`).join('');

                    subHeaderRow.append(subHeader);

                    $thead.append(subHeaderRow);

                    payroll_dataTable = payroll_table.DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: payroll_dataUrl,
                        info: false,
                        columns: [
                            ...columns.map(col => ({
                                data: col.name,
                                name: col.name,
                                className: col.class
                            })),
                        ],
                        order: [],
                        scrollX: true,
                        pageLength: 10,
                        drawCallback: function() {
                            KTMenu.createInstances();
                        },
                    });
                });
        }

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var payroll_group_id = $(this).data('id');
            let payroll_group_deleteUrl = `{{ url('/schedule/payroll-group/${payroll_group_id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(
                    ':name', '{{ __('employee::general.this_payroll_group') }}'),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(payroll_group_deleteUrl, 'DELETE').done(function() {
                        payroll_group_dataTable.ajax.reload();
                    });
                }
            });
        });

        $(document).on('click', '.nav-link-payroll-group', function() {
            payroll_group_dataTable.ajax.reload();
        });

        $(document).on('click', '.nav-link-payroll', function() {
            payroll_dataTable.ajax.reload();
        });

        $(document).on('click', '.confirm-btn', function(e) {
            e.preventDefault();
            var payroll_group_id = $(this).data('id');
            let payroll_group_confirmUrl = `{{ url('/schedule/payroll-group/confirm/${payroll_group_id}') }}`;

            showAlert(`{{ __('employee::general.payroll_group_confirm_confirmation') }}`.replace(
                    ':name', '{{ __('employee::general.this_payroll_group') }}'),
                "{{ __('employee::general.confirm') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(payroll_group_confirmUrl, 'POST').done(function() {
                        payroll_group_dataTable.destroy();
                        initPayrollGroupDatatable();
                    });
                }
            });
        });

        function initPayrollGroupDatatable() {
            payroll_group_dataTable = $(payroll_group_table).DataTable({
                processing: true,
                serverSide: true,
                ajax: payroll_group_dataUrl,
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
