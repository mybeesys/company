@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
    <form id="timesheet_rules_form" class="form d-flex flex-column flex-lg-row" formId="timesheet_rules_form" action="#">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <x-form.form-card :title="__('menuItemLang.timesheet_rule')">
                @foreach ($settings as $setting)
                    @php
                        $inputDivClass = 'w-lg-50 d-md-flex align-items-center fw-bold ';
                        $inputDivClass .= $setting['collapse']
                            ? "{$setting['name']}_collapse collapsible form-check form-check-custom form-check-solid"
                            : ($setting['type'] == 'checkbox'
                                ? 'form-check form-check-custom form-check-solid'
                                : ($setting['type'] == 'select'
                                    ? ''
                                    : 'gap-5'));
                        $inputType = $setting['type'] ?? 'text';
                        $hintText = $setting['hint'] ? __('employee::general.' . $setting['name'] . '_hint') : null;
                        $isCheckbox = $setting['type'] == 'checkbox';
                    @endphp
                    <x-form.input-div :class="$inputDivClass" :row="false"
                        attribute="{{ $setting['collapse'] ? 'data-bs-toggle=collapse data-bs-target=#' . $setting['name'] . '_toggle' : '' }}">
                        <div class="{{ $isCheckbox ? 'w-50 d-flex' : 'w-100 d-flex mb-5 mb-md-0' }}">
                            <label class="form-label mb-0" for="{{ $setting['name'] }}">@lang('employee::fields.' . $setting['name'])</label>
                            @if ($hintText)
                                <x-form.field-hint :hint="$hintText" />
                            @endif
                        </div>
                        @if ($setting['type'] == 'select')
                            <x-form.select :name="$setting['name']" required data_allow_clear="false" :errors="$errors"
                                :options="$setting['options']" no_default :value="array_key_exists($setting['name'], $stored_settings)
                                    ? $stored_settings[$setting['name']]
                                    : ''" />
                        @else
                            @if ($setting['type'] == 'checkbox')
                                <input type="hidden" name="{{ $setting['name'] }}" value="0">
                            @endif
                            <x-form.input :errors="$errors" required="{{ $setting['type'] !== 'checkbox' }}"
                                :type="$inputType" :placeholder="__('employee::fields.' . ($setting['placeholder'] ?? ''))" :name="$setting['name']" :class="$isCheckbox ? 'form-check-input mx-5 my-2' : 'py-2'"
                                :form_control="!$isCheckbox" :solid="$isCheckbox"
                                checked="{{ array_key_exists($setting['name'], $stored_settings) ? $stored_settings[$setting['name']] : false }}"
                                value="{{ $isCheckbox ? '1' : (array_key_exists($setting['name'], $stored_settings) ? $stored_settings[$setting['name']] : '') }}" />
                        @endif
                    </x-form.input-div>

                    @if ($setting['collapse'])
                        <div class="collapse" id="{{ $setting['name'] }}_toggle">
                            <x-form.input-div class="w-md-50 d-md-flex align-items-center fw-bold pt-10" :row="false">
                                @php
                                    $collapsedInput = $setting['collapsed_input'];
                                    $collapsedHint = $collapsedInput['hint']
                                        ? __('employee::general.' . $collapsedInput['name'] . '_hint')
                                        : null;
                                @endphp

                                <div class="w-100 d-flex mb-5 mb-md-0">
                                    <label class="form-label mb-0" for="{{ $collapsedInput['name'] }}">
                                        @lang('employee::fields.' . $collapsedInput['name'])</label>
                                    @if ($collapsedHint)
                                        <x-form.field-hint :hint="$collapsedHint" />
                                    @endif
                                </div>
                                <x-form.input :errors="$errors" class="form-control-solid py-2" :placeholder="__('employee::fields.' . ($collapsedInput['placeholder'] ?? ''))"
                                    name="{{ $collapsedInput['name'] }}"
                                    value="{{ array_key_exists($collapsedInput['name'], $stored_settings) ? $stored_settings[$collapsedInput['name']] : '' }}" />
                            </x-form.input-div>
                        </div>
                    @endif
                    <div class="separator border-secondary my-5 w-lg-50"></div>
                @endforeach
            </x-form.form-card>

            <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" id="timesheet_rules_form" />
        </div>
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            if ($('#enable_auto_clockout').is(':checked')) {
                $('#enable_auto_clockout_toggle').collapse('show');
            }
            if ($('#allow_clockin_before_shift').is(':checked')) {
                $('#clock_in_before_shift_time_limit').prop('required', true);
                $('#allow_clockin_before_shift_toggle').collapse('show');
            }

            $('select[name="off_days[]"]').val(<?php echo json_encode($stored_settings['off_days'] ?? []); ?>).trigger('change');
        });
        $('form').on('submit', function(e) {
            e.preventDefault();
            if ($('#enable_auto_clockout').is(':checked')) {
                const timeValue = $('#auto_clock_out_time').val();
                if (!timeValue) {
                    $('#auto_clock_out_time').focus();
                    return;
                }
            }

            ajaxRequest("{{ route('schedules.timesheet-rules.store') }}", "POST", $(this).serializeArray()).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value + '</div>');
                    });
                });
        });

        $(`#timesheet_rules_form input`).on('change', function() {
            let input = $(this);
            input.removeClass('is-invalid');
        });

        $('#day_start_on_time').flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
        });
        $('#auto_clock_out_time').flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
        })

        $('#auto_clock_out_time').attr('style', function(i, style) {
            return 'max-width: 325.98px !important;';
        });

        $('#clock_in_before_shift_time_limit').attr('style', function(i, style) {
            return 'max-width: 325.98px !important;';
        });

        Inputmask("datetime", {
            mask: "9.9",
            placeholder: "_._",
        }).mask("#overtime_rate_multiplier");

        Inputmask("datetime", {
            mask: "9.9",
            placeholder: "_._",
        }).mask("#doubletime_rate_multiplier");

        Inputmask({
            regex: "([0-9][0-9]):([0-5][0-9])",
            placeholder: "__:__",
        }).mask("#maximum_regular_hours_per_week");

        hoursMinsInuptMask('clock_in_before_shift_time_limit')
        hoursMinsInuptMask('maximum_regular_hours_per_day')
        hoursMinsInuptMask('maximum_overtime_hours_per_day')
        hoursMinsInuptMask('duration_of_paid_break')
        hoursMinsInuptMask('work_time_to_qualify_for_paid_break')


        $('[name="week_starts_on"]').select2({
            minimumResultsForSearch: -1
        });

        $('[name="off_days[]"]').select2({
            minimumResultsForSearch: -1,
            multiple: true
        });

        $('.select2-selection.select2-selection--single').attr('style', function(i, style) {
            return 'height: 36.05px !important;';
        });

        $('.select2-selection.select2-selection--multiple').attr('style', function(i, style) {
            return 'height: 36.05px !important; max-width: 326.88px; min-height: 36.05px !important;';
        });

        $('.allow_clockin_before_shift_collapse').on('click', function() {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#allow_clockin_before_shift').prop('checked', true);
                $('#allow_clockin_before_shift').val(1);
                $('#clock_in_before_shift_time_limit').prop('required', true);
            } else {
                $('#allow_clockin_before_shift').prop('checked', false);
                $('#allow_clockin_before_shift').val(0);
                $('#clock_in_before_shift_time_limit').prop('required', false);
            }
        });

        $('.enable_auto_clockout_collapse').on('click', function() {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#enable_auto_clockout').prop('checked', true);
                $('#enable_auto_clockout').val(1);
            } else {
                $('#enable_auto_clockout').prop('checked', false);
                $('#enable_auto_clockout').val(0);
            }
        });

        function hoursMinsInuptMask(id) {
            Inputmask("datetime", {
                inputFormat: "HH:MM",
                placeholder: "__:__",
                hourFormat: "24",
            }).mask(`#${id}`);
        }
    </script>
@endsection
