@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col text-nowrap mb-10">
                        <h1>@lang('employee::general.add_payroll') @lang('employee::general.for'): <span class="text-primary">{{ $date }}</span></h1>
                        <span>@lang('employee::fields.establishment'): {{ $establishment }} </span>
                    </div>
                    <div class="col">
                        <x-form.input-div class="w-100 mb-10 min-w-200px">
                            <x-form.input :errors=$errors placeholder="{{ __('employee::fields.payroll_group_name') }}"
                                value="" name="payroll_group_name" :label="__('employee::fields.payroll_group_name')" />
                        </x-form.input-div>
                    </div>
                    <div class="col">
                        <x-form.input-div class="w-100 mb-10 min-w-200px">
                            <x-form.select name="payroll_group_state" :label="__('employee::fields.status')" :options="[
                                ['id' => 'draft', 'name' => __('employee::fields.draft')],
                                ['id' => 'final', 'name' => __('employee::fields.final_invoice')],
                            ]" :errors="$errors"
                                data_allow_clear="false" required placeholder="{{ __('employee::fields.status') }}" />
                        </x-form.input-div>
                    </div>
                </div>
            </div>
        </x-cards.card-header>
        <x-cards.card-body>
            @foreach ($employees as $employee)
                <x-form.form-card headerClass="mb-5 bg-secondary" class="mb-5" :title="$employee->translatedName">
                    <div class="container mt-5">
                        <div class="row">
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap" name="total_worked_days"
                                    :label="__('employee::fields.total_worked_days')" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap" name="regular_worked_hours"
                                    :label="__('employee::fields.regular_worked_hours')" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap" name="overtime_worked_hours"
                                    :label="__('employee::fields.overtime_worked_hours')" />
                            </x-form.input-div>
                            <x-form.input-div class="mb-5 w-100">
                                <x-form.input :errors=$errors readonly labelClass="text-nowrap" name="total_wage_before_tax"
                                    :label="__('employee::fields.wage_due_before_tax')" />
                            </x-form.input-div>
                        </div>
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
    </x-cards.card>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            let addAllowanceTypeUrl = "";
            $('[name="payroll_group_state"]').select2({
                minimumResultsForSearch: -1,
            })
            allowanceDeductionRepeater('allowance', "{{ route('adjustment_types.store') }}", "{{ session()->get('locale') }}");
            allowanceDeductionRepeater('deduction', "{{ route('adjustment_types.store') }}", "{{ session()->get('locale') }}");
        })

        function allowanceDeductionRepeater(type, addAllowanceTypeUrl, lang) {
            const customOptions = new Map();

            function initializeSelect2(element) {
                const select2Config = {
                    tags: true,
                    createTag: function(params) {
                        const term = (params.term || '').trim();
                        if (term === '') {
                            return null;
                        }
                        return {
                            id: term,
                            text: term,
                            newTag: true
                        };
                    },
                    data: Array.from(customOptions.values())
                };
                element.select2(select2Config)
                    .on('select2:select', handleTagSelection);
            }

            function handleTagSelection(e) {
                const data = e.params.data;

                if (!data.newTag) {
                    return;
                }

                const name_lang = lang === 'ar' ? 'name' : 'name_en';
                const $select = $(e.target);

                ajaxRequest(addAllowanceTypeUrl, 'POST', {
                        name: data.text,
                        name_lang: name_lang,
                        type: type
                    })
                    .done(function(response) {
                        if (response.id) {
                            const newOption = {
                                id: response.id,
                                text: data.text
                            };
                            customOptions.set(response.id, newOption);

                            $(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`).each(function() {
                                const $select = $(this);
                                const option = new Option(newOption.text, newOption.id, false, false);
                                $select.append(option);

                                if (this === e.target) {
                                    $select.val(response.id).trigger('change');
                                }
                            });
                        }
                    })
                    .fail(function() {
                        $select.val(null).trigger('change');
                    });
            }

            $('.employee-adjustments').each(function() {
                const employeeDiv = $(this).find(`.employee_${type}`);
                if (!employeeDiv.length) {
                    console.error('Employee div not found');
                    return;
                }

                const employeeClass = employeeDiv.attr('class');
                if (!employeeClass) {
                    console.error('No class attribute found');
                    return;
                }

                const match = employeeClass.match(/employee_(\d+)/);
                if (!match) {
                    console.error('Could not extract employee ID from class:', employeeClass);
                    return;
                }

                const employeeId = match[1];

                const hasInitialValues = $(
                        `.employee_${employeeId} select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                    .val() !== undefined &&
                    $(`.employee_${employeeId} select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                    .val() !== '';
                // Initialize repeater with proper selector
                $(`.employee_${employeeId} #${type}_repeater`).repeater({
                    initEmpty: !hasInitialValues,

                    show: function() {
                        const $this = $(this);
                        $this.slideDown();

                        // Initialize select2 for amount type
                        $this.find(`select[name^="${type}_repeater"][name$="[amount_type]"]`)
                            .select2({
                                minimumResultsForSearch: -1
                            });

                        // Initialize flatpickr
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

                        // Initialize select2 for type
                        initializeSelect2($this.find(
                            `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                        ));
                    },

                    ready: function() {
                        const $repeater = $(`.employee_${employeeId} #${type}_repeater`);

                        $repeater.find(`select[name^="${type}_repeater"][name$="[amount_type]"]`)
                            .select2({
                                minimumResultsForSearch: -1
                            });

                        $repeater.find(`input[name^="${type}_repeater"][name$="[applicable_date]"]`)
                            .flatpickr({
                                plugins: [
                                    monthSelectPlugin({
                                        shorthand: true,
                                        dateFormat: "Y-m",
                                        altFormat: "F Y"
                                    })
                                ]
                            });

                        $repeater.find(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                            .each(function() {
                                initializeSelect2($(this));
                            });
                    },

                    hide: function(deleteElement) {
                        $(this).slideUp(deleteElement);
                    }
                });
            });
        }
    </script>
@endsection
