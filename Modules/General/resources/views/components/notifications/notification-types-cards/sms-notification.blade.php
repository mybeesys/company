@props(['notification_setting', 'employees', 'notification_name', 'variables', 'receiver_hint' => null, 'fields'])
<x-form.form-card class="mb-15 shadow" :title="__('general::general.sms_notification')" :collapsible="true" id="{{ $notification_name }}_sms_notification"
    headerClass="{{ $notification_name }}_sms_notification_active_field">
    <x-slot:header>
        <div class="card-toolbar justify-content-end">
            <x-form.switch-div class="form-check-custom">
                <input type="hidden" name="{{ $notification_name }}_sms_notification_active" value="0">
                <x-form.input :errors=$errors class="form-check-input h-20px w-30px" :value="1" type="checkbox"
                    name="{{ $notification_name }}_sms_notification_active" />
            </x-form.switch-div>
        </div>
    </x-slot:header>
    <div class="mb-5">
        <span class="fs-5">@lang('general::general.variables_can_be_used'): {{ $variables }}</span>
        <x-form.field-hint :hint="__('general::general.notification_variables_hint')" />
    </div>
    @if ($fields['body'])
        <x-form.input-div class="mb-10 w-100 px-2">
            <label for="{{ $notification_name }}_sms_notification_body" class="form-label">@lang('general::general.body_ar')
            </label>
            <textarea name="{{ $notification_name }}_sms_notification_body_ar" class="form-control form-control form-control-solid"
                data-kt-autosize="true">{{ $notification_setting->template[$notification_name . '_sms_notification_body_ar'] ?? null }}</textarea>
        </x-form.input-div>

        <x-form.input-div class="mb-10 w-100 px-2">
            <label for="{{ $notification_name }}_sms_notification_body_en" class="form-label">@lang('general::general.body_en')
            </label>
            <textarea name="{{ $notification_name }}_sms_notification_body_en" class="form-control form-control form-control-solid"
                data-kt-autosize="true">{{ $notification_setting->template[$notification_name . '_sms_notification_body_en'] ?? null }}</textarea>
        </x-form.input-div>
    @endif
    @if ($fields['receiver'])
        <div class="d-flex flex-wrap">
            <x-form.input-div class="w-100 w-md-50 px-2" :row="false">
                <x-form.select name="employee_to_receive_{{ $notification_name }}_sms_notification"
                    optionName="translatedName" :label="__('general::general.employees_to_receive_notification')" :options=$employees :errors="$errors"
                    data_allow_clear="false" :value="$notification_setting?->notifiable->pluck('id')->toArray()" placeholder="{{ __('employee::fields.employee') }}"
                    required no_default attribute="multiple">
                    <button type="button" id="{{ $notification_name }}_intern_emp_select_all_btn"
                        class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                    <button type="button" id="{{ $notification_name }}_intern_emp_deselect_all_btn"
                        class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
                    @if ($receiver_hint)
                        <x-form.field-hint :hint="$receiver_hint" />
                    @endif
                </x-form.select>
            </x-form.input-div>
        </div>
    @endif
</x-form.form-card>
