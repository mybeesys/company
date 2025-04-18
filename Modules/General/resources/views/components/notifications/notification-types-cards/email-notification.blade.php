@props(['email_notification_setting', 'employees', 'notification_name', 'variables', 'receiver_hint' => null, 'fields'])
<x-form.form-card class="mb-15 shadow" :title="__('general::general.email_notification')" :collapsible="true"
    id="{{ $notification_name }}_email_notification"
    headerClass="{{ $notification_name }}_email_notification_active_field">
    <x-slot:header>
        <div class="card-toolbar justify-content-end">
            <x-form.switch-div class="form-check-custom">
                <input type="hidden" name="{{ $notification_name }}_email_notification_active" value="0">
                <x-form.input :errors=$errors class="form-check-input h-20px w-30px" value="1" type="checkbox"
                    name="{{ $notification_name }}_email_notification_active" />
            </x-form.switch-div>
        </div>
    </x-slot:header>
    <div class="mb-5">
        <span class="fs-5">@lang('general::general.variables_can_be_used'): {{ $variables }}</span>
        <x-form.field-hint :hint="__('general::general.notification_variables_hint')" />
    </div>

    @if ($fields['subject'])
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors :placeholder="__('general::general.subject_ar')" :value="$email_notification_setting->template[$notification_name . '_email_notification_subject_ar'] ?? null"
                name="{{ $notification_name }}_email_notification_subject_ar" :label="__('general::general.subject_ar')" />
        </x-form.input-div>
    @endif

    <div class="d-flex flex-wrap">
        @if ($fields['bcc'])
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors=$errors placeholder="BCC" :value="$email_notification_setting->template[$notification_name . '_email_notification_BCC'] ?? null"
                    name="{{ $notification_name }}_email_notification_BCC" label="BCC" />
            </x-form.input-div>
        @endif
        @if ($fields['cc'])
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input :errors=$errors placeholder="CC" :value="$email_notification_setting->template[$notification_name . '_email_notification_CC'] ?? null"
                    name="{{ $notification_name }}_email_notification_CC" label="CC" />
            </x-form.input-div>
        @endif
    </div>
    @if ($fields['body'])
        <x-form.input-div class="mb-10 w-100 px-2">
            <label for="{{ $notification_name }}_email_notification_body_ar" class="form-label">@lang('general::general.body_ar')
            </label>
            <textarea name="{{ $notification_name }}_email_notification_body_ar"
                id="{{ $notification_name }}_email_notification_body_ar">{{ $email_notification_setting->template[$notification_name . '_email_notification_body_ar'] ?? null }}</textarea>
        </x-form.input-div>
    @endif

    @if ($fields['subject'])
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors :placeholder="__('general::general.subject_en')" :value="$email_notification_setting->template[$notification_name . '_email_notification_subject_en'] ?? null"
                name="{{ $notification_name }}_email_notification_subject_en" :label="__('general::general.subject_en')" />
        </x-form.input-div>
    @endif
    @if ($fields['subject'])
        <x-form.input-div class="mb-10 w-100 px-2">
            <label for="{{ $notification_name }}_email_notification_body_en" class="form-label">@lang('general::general.body_en')
            </label>
            <textarea name="{{ $notification_name }}_email_notification_body_en"
                id="{{ $notification_name }}_email_notification_body_en">{{ $email_notification_setting->template[$notification_name . '_email_notification_body_en'] ?? null }}</textarea>
        </x-form.input-div>
    @endif


    @if ($fields['receiver'])
        <div class="d-flex flex-wrap">
            <x-form.input-div class="w-100 w-md-50 px-2" :row="false">
                <x-form.select name="employee_to_receive_{{ $notification_name }}_email_notification"
                    optionName="translatedName" :label="__('general::general.employees_to_receive_notification')" :options=$employees :errors="$errors"
                    data_allow_clear="false" :value="$email_notification_setting?->notifiable->pluck('id')->toArray()" placeholder="{{ __('employee::fields.employee') }}"
                    required no_default attribute="multiple">
                    <button type="button" id="{{ $notification_name }}_email_emp_select_all_btn"
                        class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">@lang('employee::general.select_all')</button>
                    <button type="button" id="{{ $notification_name }}_email_emp_deselect_all_btn"
                        class="btn btn-secondary px-4 py-1 fs-7 mb-1">@lang('employee::general.deselect_all')</button>
                    @if ($receiver_hint)
                        <x-form.field-hint :hint="$receiver_hint" />
                    @endif
                </x-form.select>
            </x-form.input-div>
        </div>
    @endif
</x-form.form-card>
