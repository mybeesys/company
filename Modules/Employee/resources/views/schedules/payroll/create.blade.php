@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')

    {{-- <x-cards.card>
    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col text-nowrap mb-10">
                    <h1>@lang('employee::general.add_payroll') @lang('employee::general.for'): <span class="text-primary">{{ $date }}</span></h1>
                    <span>@lang('employee::fields.establishment'): {{ $establishment_name }} </span>
                </div>
                <div class="col">
                    <x-form.input-div class="w-100 mb-10 min-w-200px">
                        <x-form.input :errors=$errors placeholder="{{ __('employee::fields.payroll_group_name') }}"
                            value="" name="payroll_group_name" :label="__('employee::fields.payroll_group_name')" />
                    </x-form.input-div>
                </div>
                <div class="col">

                </div>
            </div>
        </div>
    </x-cards.card-header>
    <x-cards.card-body>
        @foreach ($employees as $employee)
            <x-form.form-card headerClass="mb-5 bg-secondary" class="mb-5" :title="$employee->translatedName">
                <div class="container mt-5">
                    @foreach ($employee->allRoles->isEmpty() ? [null] : $employee->allRoles as $role)
                        <div class="row mb-3">
                            <div class="col">
                                <h2 class="text-primary">{{ $role?->name }}:</h2>
                            </div>
                        </div>
                        <div class="row mb-5">
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap" name="total_worked_days"
                                    :label="__('employee::fields.total_worked_days')" :value="$role?->days_count" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap"
                                    name="regular_worked_hours" :label="__('employee::fields.regular_worked_hours')" :value="$role?->worked_hours - $role?->overtime_hours" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap"
                                    name="overtime_worked_hours" :label="__('employee::fields.overtime_worked_hours')" :value="$role?->overtime_hours" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap"
                                    name="total_worked_hours" :label="__('employee::fields.total_hours')" :value="$role?->worked_hours" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap"
                                    name="total_wage_before_tax" :label="__('employee::fields.wage_due_before_tax')" />
                            </x-form.input-div>
                        </div>
                        <div class="separator border-primary my-5"></div>
                    @endforeach
                    <div class="row employee-adjustments">
                        <div class="col">
                            <x-form.form-card :title="__('employee::fields.allowances')"
                                class="px-0 employee_{{ $employee->id }} employee_allowance">
                                <x-employee::payroll.allowance-deduction-repeater type="allowance" :adjustments="$employee->allowances"
                                    :adjustment_types="$allowances_types" />
                            </x-form.form-card>
                        </div>
                        <div class="col">
                            <x-form.form-card :title="__('employee::fields.deductions')"
                                class="px-0 employee_{{ $employee->id }} employee_deduction">
                                <x-employee::payroll.allowance-deduction-repeater type="deduction" :adjustment_types="$deductions_types" />
                            </x-form.form-card>
                        </div>
                    </div>
                    <div class="row px-5 mt-10">
                        <div class="col">
                            <h2>@lang('employee::fields.total_wage_before_tax'):</h2>
                        </div>
                        <div class="col">
                            <h2>@lang('employee::fields.total_wage'):</h2>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
            </x-form.form-card>
        @endforeach
    </x-cards.card-body>
</x-cards.card> --}}
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 mb-10">
            <x-tables.table-header model="createPayroll" :search="false" :addButton="false" module="employee">
                <x-slot:elements>
                    <div class="col text-nowrap">
                        <h1>@lang('employee::general.add_payroll') @lang('employee::general.for'): <span class="text-primary">{{ $date }}</span></h1>
                        {{-- <span>@lang('employee::fields.establishment'): {{ $establishment_name }} </span> --}}
                    </div>
                    <x-form.input-div class="w-100 min-w-200px">
                        <x-form.input :errors=$errors placeholder="{{ __('employee::fields.payroll_group_name') }}"
                            value="" name="payroll_group_name" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100 min-w-200px">
                        <x-form.select name="payroll_group_state" :options="[
                            ['id' => 'draft', 'name' => __('employee::fields.draft')],
                            ['id' => 'final', 'name' => __('employee::fields.final_invoice')],
                        ]" :errors="$errors"
                            data_allow_clear="false" required placeholder="{{ __('employee::fields.status') }}" />
                    </x-form.input-div>
                </x-slot:elements>
                <x-slot:export>
                    <x-tables.export-menu id="shift" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :actionColumn="false" :columns=$columns model="createPayroll" :idColumn=false module="employee" />
        </x-cards.card-body>

        <x-form.form-buttons cancelUrl="{{ url('/schedule/payroll') }}" class="px-10 py-5" />
    </x-cards.card>
    <x-employee::payroll.adjustment-modal :allowances_types="$allowances_types" :deductions_types="$deductions_types" />

@endsection

@section('script')
    @parent
    <script>
        var urlParams = new URLSearchParams(window.location.search);
        let dataTable;
        let employeeId;
        let date;
        let deduction_apply = true;
        const table = $('#kt_createPayroll_table');
        const baseUrl = '{{ route('schedules.payrolls.create') }}';
        $(document).ready(function() {
            let employee_ids = urlParams.get('employee_ids');
            let establishment_ids = urlParams.get('establishment_ids');
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
            addAllowancesForm("{{ route('schedules.payrolls.store-payroll-allowance') }}");
            addDeductionForm("{{ route('schedules.payrolls.store-payroll-deduction') }}")

            let addAllowanceTypeUrl = "";
            $('[name="payroll_group_state"]').select2({
                minimumResultsForSearch: -1,
            })
            allowanceDeductionRepeater('allowance', "{{ route('adjustment_types.store') }}",
                "{{ session()->get('locale') }}");
            allowanceDeductionRepeater('deduction', "{{ route('adjustment_types.store') }}",
                "{{ session()->get('locale') }}");

        });

        function addAllowancesForm(storePayrollAllowanceUrl) {
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
                        deduction_apply = false;
                        dataTable.ajax.reload(null, false);
                        $('#payroll_allowance_modal').modal('toggle');
                    });
            });
        }

        function addDeductionForm(storePayrollDeductionUrl) {
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
                        deduction_apply = false;
                        dataTable.ajax.reload(null, false);
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
                    newItem.find('input[name*="[allowance_id]"]').val(allowanceId);
                });

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
                    newItem.find('input[name*="[deduction_id]"]').val(deductionId);
                });

                setTimeout(() => {
                    $('#payroll_deduction_modal').modal('toggle');
                }, 300);
            });
        }


        function allowanceDeductionRepeater(type, addAllowanceTypeUrl, lang) {
            const customOptions = new Map();

            function initializeSelect2(element) {
                const addNewOption = {
                    id: 'add_new',
                    text: lang === 'ar' ? 'إضافة خيار جديد' : 'Add New Option',
                    addNew: true
                };
                const allOptions = [addNewOption, ...Array.from(customOptions.values())];
                const select2Config = {
                    tags: false,
                    data: allOptions,
                    templateResult: function(option) {
                        if (option.addNew) {
                            return $(`
                    <div class="add-new-option">
                        <i class="fas fa-plus me-2"></i>
                        <span>${option.text}</span>
                    </div>
                `);
                        }
                        return option.text;
                    },
                    templateSelection: function(option) {
                        if (option.addNew) {
                            return $(
                                `<input type="text" class="select2-add-new-input" placeholder="${lang === 'ar' ? 'اكتب الخيار الجديد' : 'Type new option...'}"/>`
                            );
                        }
                        return option.text;
                    }
                };

                element.select2(select2Config)
                    .on('select2:select', function(e) {
                        const data = e.params.data;
                        if (data.addNew) {
                            setTimeout(() => {
                                const input = $('.select2-add-new-input');
                                input.focus();
                                let isNewOptionHandled = false;

                                input.on('keydown', function(e) {
                                    if (e.which === 13) {
                                        const newValue = $(this).val();
                                        if (newValue.trim()) {
                                            handleNewOption(element, newValue);
                                            isNewOptionHandled = true;
                                        }
                                    }
                                    if (e.which === 32) {
                                        e.stopPropagation();
                                    }
                                });
                                input.on('blur', function() {
                                    if (!isNewOptionHandled) {
                                        const newValue = $(this).val().trim();
                                        if (newValue) {
                                            handleNewOption(element, newValue);
                                        } else {
                                            element.val(null).trigger('change');
                                        }
                                    }
                                    isNewOptionHandled = false;
                                });
                            }, 0);
                        }
                    });
                reorderOptions();
            }

            function reorderOptions() {
                const allRepeaters = $(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`);
                allRepeaters.each(function() {
                    const currentElement = $(this);
                    const addNewOption = currentElement.find('option[value="add_new"]');
                    if (addNewOption.length) {
                        addNewOption.detach();
                        currentElement.append(addNewOption);
                    }
                });
            }

            function handleNewOption(element, newValue) {
                const name_lang = lang === 'ar' ? 'name' : 'name_en';

                ajaxRequest(addAllowanceTypeUrl, 'POST', {
                        name: newValue,
                        name_lang: name_lang,
                        type: type
                    })
                    .done(function(response) {
                        if (response.id) {
                            const newOption = {
                                id: response.id,
                                text: newValue
                            };
                            customOptions.set(response.id, newOption);

                            const newSelectOption = new Option(newOption.text, newOption.id, true, true);
                            element.append(newSelectOption);

                            reorderOptions();

                            element.trigger('change');
                            element.select2('open');
                        }
                    })
                    .fail(function() {
                        element.val(null).trigger('change');
                    });
            }

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

                    initializeSelect2($this.find(
                        `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                    ));
                },

                hide: function(deleteElement) {
                    $(this).slideUp(deleteElement);
                }
            });
        }

        function initDatatable(dataUrl) {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,
                    type: "GET",
                    data: function(d) {
                        d.deduction_apply = deduction_apply;
                    }
                },
                info: false,
                columns: [{
                        data: 'employee',
                        name: 'employee',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'establishments',
                        name: 'establishments',
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
                    {
                        data: 'allowances',
                        name: 'allowances',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'deductions',
                        name: 'deductions',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'total_wage_before_tax',
                        name: 'total_wage_before_tax',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'total_wage',
                        name: 'total_wage',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
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
