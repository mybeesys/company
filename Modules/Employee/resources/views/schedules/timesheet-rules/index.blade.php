@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
    <form id="timesheet_rules_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        formId="timesheet_rules_form" action="{{ route('schedules.timesheet-rules.store') }}">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <x-form.form-card :title="__('menuItemLang.timesheet_rule')">
                @foreach ($settings as $setting)
                    @php
                        $inputDivClass = $setting['collapse']
                            ? "{$setting['name']}_collapse collapsible form-check form-check-custom form-check-solid w-lg-50 d-md-flex align-items-center fw-bold"
                            : ($setting['type'] == 'checkbox'
                                ? 'form-check form-check-custom form-check-solid w-lg-50 d-md-flex align-items-center fw-bold'
                                : 'w-lg-50 d-md-flex align-items-center fw-bold');
                        $inputType = $setting['type'] ?? 'text';
                        $hintText = $setting['hint'] ? __('employee::general.' . $setting['name'] . '_hint') : null;
                        $isCheckbox = $setting['type'] == 'checkbox';
                    @endphp

                    <x-form.input-div :class="$inputDivClass" :row="false"
                        attribute="data-bs-toggle=collapse data-bs-target=#{{ $setting['name'] }}_toggle">
                        <div class="{{ $isCheckbox ? 'w-50 d-flex' : 'w-100 d-flex mb-5 mb-md-0' }}">
                            <label class="form-label mb-0" for="{{ $setting['name'] }}">@lang('employee::fields.' . $setting['name'])</label>
                            @if ($hintText)
                                <x-form.field-hint :hint="$hintText" />
                            @endif
                        </div>
                        @if ($setting['type'] == 'select')
                            <x-form.select :name="$setting['name']" data_allow_clear="false" :errors="$errors" :options="$setting['options']"
                                value="{{ array_key_exists($setting['name'], $stored_settings) ? $stored_settings[$setting['name']] : '' }}" />
                        @else
                            @if ($setting['type'] == 'checkbox')
                                <input type="hidden" name="{{ $setting['name'] }}" value="0">
                            @endif
                            <x-form.input :errors="$errors" :type="$inputType" :placeholder="__('employee::fields.' . ($setting['placeholder'] ?? ''))" :name="$setting['name']"
                                :class="$isCheckbox ? 'form-check-input mx-5 my-2' : 'form-control-solid py-2'" :form_control="!$isCheckbox"
                                checked="{{ array_key_exists($setting['name'], $stored_settings) ? $stored_settings[$setting['name']] : false }}"
                                value="{{ $isCheckbox ? '1' : (array_key_exists($setting['name'], $stored_settings) ? $stored_settings[$setting['name']] : '') }}" />
                        @endif
                    </x-form.input-div>

                    @if ($setting['collapse'])
                        <div class="collapse" id="{{ $setting['name'] }}_toggle">
                            <x-form.input-div class="w-md-50 d-md-flex align-items-center fw-bold pt-10"
                                :row="false">
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
                $('#allow_clockin_before_shift_toggle').collapse('show');
            }
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

        $('.flatpickr-time').addClass('d-flex align-items-center gap-10 py-5 w-75 mx-auto border-0');
        $('.flatpickr-time.time24hr').addClass('gap-15');
        $('.flatpickr-calendar').addClass('h-50px d-flex align-items-center');

        $('[name="weak_starts_on"]').select2({
            minimumResultsForSearch: -1
        });
        $('.select2-selection.select2-selection--single').attr('style', function(i, style) {
            return 'height: 36.05px !important;';
        });

        $('.allow_clockin_before_shift_collapse').on('click', function() {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#allow_clockin_before_shift').prop('checked', true);
                $('#allow_clockin_before_shift').val(1);
            } else {
                $('#allow_clockin_before_shift').prop('checked', false);
                $('#allow_clockin_before_shift').val(0);
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
