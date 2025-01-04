@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <form id="payroll-form" action="{{ route('schedules.payrolls.store') }}" method="POST">
            @csrf
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 mb-10">
                <x-tables.table-header model="createPayroll" :search="false" :addButton="false" module="employee">
                    <x-slot:elements>
                        <div class="col text-nowrap">
                            <h1>@lang('employee::general.add_payroll') @lang('employee::general.for'): <span class="text-primary">{{ $date }}</span>
                            </h1>
                            <span>@lang('employee::fields.establishment'): {{ implode(',', $establishments) }} </span>
                        </div>
                        <x-form.input-div class="w-100 min-w-200px">
                            <x-form.input :errors=$errors placeholder="{{ __('employee::fields.payroll_group_name') }}"
                                required value="{{ $payroll_group?->name }}" name="payroll_group_name" />
                        </x-form.input-div>
                        <x-form.input-div class="w-100 min-w-200px">
                            <x-form.select name="payroll_group_state" :options="[
                                ['id' => 'draft', 'name' => __('employee::fields.draft')],
                                ['id' => 'final', 'name' => __('employee::fields.final_invoice')],
                            ]" :errors="$errors"
                                value="{{ $payroll_group?->state }}" data_allow_clear="false" required
                                placeholder="{{ __('employee::fields.status') }}" />
                        </x-form.input-div>
                    </x-slot:elements>
                </x-tables.table-header>
            </x-cards.card-header>
            <x-cards.card-body class="table-responsive">
                <x-tables.table :actionColumn="false" :columns="[]" model="createPayroll" :idColumn=false
                    module="employee" />
            </x-cards.card-body>

            <input type="hidden" name="employee_ids" value="{{ request()->get('employee_ids') }}">
            <input type="hidden" name="establishment_ids" value="{{ request()->get('establishment_ids') }}">
            <input type="hidden" name="date" value="{{ request()->get('date') }}">
            <input type="hidden" name="payroll_group_id"
                value="{{ isset($payroll_group?->id) ? $payroll_group?->id : null }}">
            <x-form.form-buttons cancelUrl="{{ url('/schedule/payroll') }}" class="px-10 py-5" />
        </form>
    </x-cards.card>
    <x-employee::payroll.adjustment-modal :allowances_types="$allowances_types" :deductions_types="$deductions_types" />

@endsection

@section('script')
    @parent
    <script src="{{ url('/modules/employee/js/adjustment-type.js') }}"></script>
    <script>
        var urlParams = new URLSearchParams(window.location.search);
        let columns;
        let dataTable;
        let employeeId;
        let establishment_ids;
        let adjustmentType_type;
        let date;
        let allowancesCount = 0;
        let deductionsCount = 0;
        let firstEnter = true;
        const table = $('#kt_createPayroll_table');
        const baseUrl = '{{ route('schedules.payrolls.create') }}';

        $(document).ready(function() {

            let employee_ids = urlParams.get('employee_ids');
            establishment_ids = urlParams.get('establishment_ids');
            date = urlParams.get('date');

            const queryParams = new URLSearchParams({
                employee_ids: employee_ids,
                establishment_ids: establishment_ids,
                date: date
            });
            const dataUrl = `${baseUrl}?${queryParams.toString()}`;
            initDatatable(dataUrl);

            payrollAllowanceModal();
            payrollDeductionModal();
            addAllowancesForm("{{ route('adjustments.store-allowance-cache') }}", dataUrl);
            addDeductionForm("{{ route('adjustments.store-deduction-cache') }}", dataUrl)

            let addAllowanceTypeUrl = "";
            $('[name="payroll_group_state"]').select2({
                minimumResultsForSearch: -1,
            })
            adjustmentRepeater('deduction');
            adjustmentRepeater('allowance');

            lock();

            $(document).on('change', '[name^="employee_establishment"]', function() {
                const employeeId = $(this).data('employee-id');
                const establishmentId = $(this).val();


                let $hiddenInput = $(`#establishment_change_${employeeId}`);
                if ($hiddenInput.length === 0) {
                    $('<input>')
                        .attr('type', 'hidden')
                        .attr('id', `establishment_change_${employeeId}`)
                        .attr('name', `establishment_changes[${employeeId}]`)
                        .val(establishmentId)
                        .appendTo('#payroll-form');
                } else {
                    $hiddenInput.val(establishmentId);
                }

                // Reload the datatable
                dataTable.ajax.reload(null, false);
            });

        });

        function lock() {
            const lockKey = `payroll_creation_lock_${date}_(${establishment_ids})`;
            const extendLockUrl = '{{ route('schedules.payrolls.extendLock') }}';

            function extendLock() {
                let data = {
                    lockKey: lockKey,
                    _token: '{{ csrf_token() }}'
                };
                ajaxRequest(extendLockUrl, 'POST', data, false, false, false);
            }

            // Extend the lock every 15 seconds
            const lockInterval = setInterval(extendLock, 15000);
        }

        function addAllowancesForm(storePayrollAllowanceUrl, dataUrl) {
            $('#payroll_allowance_modal_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({
                    name: 'employee_id',
                    value: employeeId
                }, {
                    name: 'date',
                    value: date
                });
                ajaxRequest(storePayrollAllowanceUrl, 'POST', data, true, true)
                    .done(function() {
                        firstEnter = false;

                        $.getJSON(dataUrl, {
                            firstEnter: firstEnter
                        }, function(response) {
                            $(table).empty();
                            dataTable.destroy();
                            initDatatable(dataUrl);
                        });
                        $('#payroll_allowance_modal').modal('toggle');
                    });
            });
        }

        function addDeductionForm(storePayrollDeductionUrl, dataUrl) {
            $('#payroll_deduction_modal_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({
                    name: 'employee_id',
                    value: employeeId
                }, {
                    name: 'date',
                    value: date
                });
                ajaxRequest(storePayrollDeductionUrl, 'POST', data, true, true)
                    .done(function() {
                        firstEnter = false;

                        // Reload the table with fresh data
                        $.getJSON(dataUrl, {
                            firstEnter: firstEnter
                        }, function(response) {

                            $(table).empty();
                            dataTable.destroy();
                            initDatatable(dataUrl);
                        });

                        $('#payroll_deduction_modal').modal('toggle');
                    });
            });
        }

        function payrollAllowanceModal() {
            $(document).on('click', '.add-allowances-button', function(e) {
                e.preventDefault();
                employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');
                const data = $(this).data();

                const amount = [];
                const types = [];
                const amountType = [];
                const allowanceTypes = [];
                const allowancesIds = [];

                const sortedKeys = Object.keys(data).sort((a, b) => {
                    const numA = parseInt(a.split('-')[1] || 0, 10); // Extract number after "-"
                    const numB = parseInt(b.split('-')[1] || 0, 10);
                    return numA - numB;
                });

                for (const key of sortedKeys) {
                    if (key.startsWith('allowanceId')) {
                        allowancesIds.push(data[key]);
                    } else if (key.startsWith('amount')) {
                        amount.push(data[key]);
                    } else if (key.startsWith('allowanceType')) {
                        allowanceTypes.push(data[key]);
                    } else if (key.startsWith('amType')) {
                        amountType.push(data[key]);
                    }
                }

                const repeaterList = $('[data-repeater-list="allowance_repeater"]');

                repeaterList.empty();

                allowancesIds.forEach((allowanceId, index) => {
                    $('[data-repeater-create]').trigger('click');
                    const newItem = repeaterList.find('[data-repeater-item]').last();
                    newItem.find('input[name*="[amount]"]').val(amount[index]);
                    newItem.find('select[name*="[amount_type]"]').val(amountType[index])
                        .trigger('change');
                    newItem.find('select[name*="[adjustment_type]"]').val(allowanceTypes[index])
                        .trigger('change');
                    newItem.find('input[name*="[id]"]').val(allowanceId);
                });
                adjustmentType_type = "allowance";
                setTimeout(() => {
                    $('#payroll_allowance_modal').modal('toggle');
                }, 300);
            });
        }

        function payrollDeductionModal() {
            $(document).on('click', '.add-deductions-button', function(e) {
                e.preventDefault();
                employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');

                const data = $(this).data();
                const amount = [];
                const types = [];
                const amountType = [];
                const deductionTypes = [];
                const deductionsIds = [];

                const sortedKeys = Object.keys(data).sort((a, b) => {
                    const numA = parseInt(a.split('-')[1] || 0, 10); // Extract number after "-"
                    const numB = parseInt(b.split('-')[1] || 0, 10);
                    return numA - numB;
                });

                for (const key of sortedKeys) {
                    if (key.startsWith('deductionId')) {
                        deductionsIds.push(data[key]);
                    } else if (key.startsWith('amount')) {
                        amount.push(data[key]);
                    } else if (key.startsWith('deductionType')) {
                        deductionTypes.push(data[key]);
                    } else if (key.startsWith('amType')) {
                        amountType.push(data[key]);
                    }
                }

                const repeaterList = $('[data-repeater-list="deduction_repeater"]');

                repeaterList.empty();

                deductionsIds.forEach((deductionId, index) => {
                    $('[data-repeater-create]').trigger('click');
                    const newItem = repeaterList.find('[data-repeater-item]').last();
                    newItem.find('input[name*="[amount]"]').val(amount[index]);
                    newItem.find('select[name*="[amount_type]"]').val(amountType[index])
                        .trigger('change');
                    newItem.find('select[name*="[adjustment_type]"]').val(deductionTypes[index])
                        .trigger('change');
                    newItem.find('input[name*="[id]"]').val(deductionId);
                });
                adjustmentType_type = "deduction";
                setTimeout(() => {
                    $('#payroll_deduction_modal').modal('toggle');
                }, 300);
            });
        }

        function adjustmentRepeater(type) {
            $(`#${type}_repeater`).repeater({
                show: function() {
                    const $this = $(this);
                    $this.slideDown();

                    $this.find(`select[name^="${type}_repeater"][name$="[amount_type]"]`)
                        .select2({
                            minimumResultsForSearch: -1
                        });

                    $this.find(`input[name^="${type}_repeater"][name$="[applicable_date]"]`)
                        .flatpickr({
                            plugins: [
                                monthSelectPlugin({
                                    shorthand: true,
                                    dateFormat: "Y-m",
                                    altFormat: "F Y"
                                })
                            ]
                        });
                    const customOptions = new Map();

                    initializeSelect2($this.find(
                            `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                        ), customOptions, true, "{{ session('locale') }}",
                        "{{ route('adjustment_types.store') }}");
                },

                hide: function(deleteElement) {
                    $(this).slideUp(deleteElement);
                }
            });
        }


        function initDatatable(dataUrl) {
            $.getJSON(dataUrl, {
                firstEnter: firstEnter
            }, function(response) {
                // let allowancesCount = 0;
                // let dedctionsCount = 0;
                // if (response.data.length > 0) {
                const firstRow = response.data[1];

                // const allAllowances = response.data
                //     .filter(row => row.allowances_array) // Filter rows with allowances_array
                //     .flatMap(row => Object.values(row.allowances_array)); // Combine all allowances

                // Remove duplicates
                // const uniqueAllowances = Array.from(new Set(allAllowances));


                // const allDeductions = response.data
                //     .filter(row => row.deductions_array) // Filter rows with allowances_array
                //     .flatMap(row => Object.values(row.deductions_array)); // Combine all allowances

                // // Remove duplicates
                // const uniqueDeductions = Array.from(new Set(allDeductions));


                const allowancesCount = response.data.reduce((max, row) => {
                    const currentCount = row.allowances_array ? Object.keys(row.allowances_array)
                        .length : 0;
                    return Math.max(max, currentCount);
                }, 0);

                const uniqueAllowances = response.data
                    .filter(row => row.allowances_array) // Filter rows with allowances_array
                    .reduce((acc, row) => {
                        Object.entries(row.allowances_array).forEach(([key, value]) => {
                            if (!acc[key]) {
                                acc[key] = value; // Add the key-value pair if it doesn't already exist
                            }
                        });
                        return acc;
                    }, {});


                // Find the maximum length of deductions_array (if needed)
                const deductionsCount = response.data.reduce((max, row) => {
                    const currentCount = row.deductions_array ? Object.keys(row.deductions_array)
                        .length : 0;
                    return Math.max(max, currentCount);
                }, 0);

                const uniqueDeductions = response.data
                    .filter(row => row.deductions_array) // Filter rows with allowances_array
                    .reduce((acc, row) => {
                        Object.entries(row.deductions_array).forEach(([key, value]) => {
                            if (!acc[key]) {
                                acc[key] = value; // Add the key-value pair if it doesn't already exist
                            }
                        });
                        return acc;
                    }, {});

                // Update the table header (thead)
                const tableHead = $('#kt_createPayroll_table thead');
                tableHead.empty(); // Clear existing thead

                // Create the first row (main headers)
                const mainHeaderRow = $('<tr></tr>');
                mainHeaderRow.append(
                    '<th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.employee')</th>'
                );
                mainHeaderRow.append(
                    '<th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.establishment')</th>'
                );
                mainHeaderRow.append(
                    '<th colspan="5" class="text-center min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.basic_wage')</th>'
                );
                mainHeaderRow.append(
                    '<th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.wage_due')</th>'
                );

                // Dynamic Allowances Header
                mainHeaderRow.append(
                    `<th colspan="${allowancesCount +1}" class="text-center min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.allowances')</th>`
                );

                // Dynamic Deductions Header
                mainHeaderRow.append(
                    `<th colspan="${deductionsCount +1}" class="text-center min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.deductions')</th>`
                );

                // mainHeaderRow.append(
                //     '<th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_wage_before_tax')</th>'
                // );
                mainHeaderRow.append(
                    '<th rowspan="2" class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_wage')</th>'
                );

                tableHead.append(mainHeaderRow);

                // Create the second row (sub-headers)
                const subHeaderRow = $('<tr></tr>');
                subHeaderRow.append(`
                    <th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.regular_worked_hours')</th>
                    <th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.overtime_hours')</th>
                    <th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_hours')</th>
                    <th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_worked_days')</th>
                    <th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_wage')</th>
                    `);


                // Add dynamic allowance sub-headers                
                if (allowancesCount > 0) {
                    Object.keys(uniqueAllowances).forEach(key => {
                        subHeaderRow.append(
                            `<th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">${key.replace('_', ' ').toUpperCase()}</th>`
                        );
                    });
                }
                subHeaderRow.append(
                    `<th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_allowances')</th>`
                );
                // Add dynamic deduction sub-headers
                if (deductionsCount > 0) {
                    Object.keys(uniqueDeductions).forEach(key => {
                        subHeaderRow.append(
                            `<th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">${key.replace('_', ' ').toUpperCase()}</th>`
                        );
                    });
                }
                subHeaderRow.append(
                    `<th class="text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6 border border-2">@lang('employee::fields.total_deductions')</th>`
                );

                tableHead.append(subHeaderRow);

                // Initialize DataTable (from previous code)
                initDataTableColumns(response.data, dataUrl);
            });
        }

        function decodeHTML(html = 0) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }

        function initDataTableColumns(data, dataUrl) {
            let columns = [{
                    data: 'employee',
                    name: 'employee',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'establishment',
                    name: 'establishment',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'regular_worked_hours',
                    name: 'regular_worked_hours',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'overtime_hours',
                    name: 'overtime_hours',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'total_hours',
                    name: 'total_hours',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'total_worked_days',
                    name: 'total_worked_days',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'basic_total_wage',
                    name: 'basic_total_wage',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
                {
                    data: 'wage_due_before_tax',
                    name: 'wage_due_before_tax',
                    className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                },
            ];

            // Add dynamic allowance and deduction columns
            if (data.length > 0) {
                const firstRow = data[0];

                Object.keys(firstRow.allowances_array || {}).forEach(key => {
                    columns.push({
                        name: `allowances_array.${key}`,
                        data: `allowances_array.${key}`,
                        className: 'text-start px-3 py-2 border border-2 text-gray-800 fs-6',
                        render: function(data) {
                            return decodeHTML(data); // Decode and render as raw HTML
                        }
                    });
                });
                columns.push({
                    name: 'total_allowances',
                    data: 'total_allowances',
                    className: 'text-start px-3 py-2 border border-2 text-gray-800 fs-6'
                });
                Object.keys(firstRow.deductions_array || {}).forEach(key => {
                    columns.push({
                        name: `deductions_array.${key}`,
                        data: `deductions_array.${key}`,
                        className: 'text-start px-3 py-2 border border-2 text-gray-800 fs-6',
                        render: function(data) {
                            return decodeHTML(data); // Decode and render as raw HTML
                        }
                    });
                });

                columns.push({
                    name: 'total_deductions',
                    data: 'total_deductions',
                    className: 'text-start px-3 py-2 border border-2 text-gray-800 fs-6'
                });
            }
            columns.push({
                name: 'total_wage',
                data: 'total_wage',
                className: 'text-start px-3 py-2 border border-2 text-gray-800 fs-6'
            });

            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                info: false,
                ajax: {
                    url: dataUrl,
                    type: "GET",
                    data: function(d) {
                        d.firstEnter = firstEnter;

                        let establishmentChanges = {};
                        $('input[name^="establishment_changes"]').each(function() {
                            const employeeId = $(this).attr('id').replace('establishment_change_', '');
                            establishmentChanges[employeeId] = $(this).val();
                        });

                        // Add establishment changes to the request data
                        d.establishment_changes = establishmentChanges;

                        return d;
                    }
                },
                columns: columns,
                order: [],
                scrollX: true,
                pageLength: 10,
            });
            dataTable.on('draw', function() {
                $('[name^="employee_establishment"]').select2({
                    minimumResultsForSearch: -1
                });
                $('.select2-selection.select2-selection--single').attr('style', function(i, style) {
                    return 'height: 36.05px !important;';
                });
            });
        }
    </script>
@endsection
