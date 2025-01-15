@props([
    'employees',
    'notifications_settings',
    'notification_name',
    'variables',
    'active' => false,
    'internal' => true,
    'sms' => true,
    'email' => true,
    'internal_receiver_hint' => null,
    'email_receiver_hint' => null,
    'sms_receiver_hint' => null,
])
<div @class(['tab-pane fade', 'show active' => $active]) id="{{ $notification_name }}_notification" role="tabpanel">
    @php
        $general_internal_notification_setting = $notifications_settings
            ->where('type', $notification_name)
            ->where('sendType', 'internal')
            ->first();
        $general_email_notification_setting = $notifications_settings
            ->where('type', $notification_name)
            ->where('sendType', 'email')
            ->first();
        $general_sms_notification_setting = $notifications_settings
            ->where('type', $notification_name)
            ->where('sendType', 'sms')
            ->first();
    @endphp
    <form action="#" id="{{ $notification_name }}_notifications_settings">
        @csrf
        @if ($internal)
            <x-general::notifications.notification-types-cards.internal-notification :variables="$variables" :employees="$employees"
                :notification_setting="$general_internal_notification_setting" :notification_name="$notification_name" :receiver_hint="$internal_receiver_hint" />
        @endif
        @if ($email)
            <x-general::notifications.notification-types-cards.email-notification :variables="$variables" :employees="$employees"
                :notification_setting="$general_email_notification_setting" :notification_name="$notification_name" :receiver_hint="$email_receiver_hint" />
        @endif
        @if ($sms)
            <x-general::notifications.notification-types-cards.sms-notification :variables="$variables" :employees="$employees"
                :notification_setting="$general_sms_notification_setting" :notification_name="$notification_name" :receiver_hint="$sms_receiver_hint" />
        @endif

        <x-form.form-buttons cancelUrl="{{ url('/employee') }}" id="{{ $notification_name }}_notifications_settings" />
    </form>
</div>
<script>
    function handleInternalNotification_{{ $notification_name }}() {
        if ("{{ $general_internal_notification_setting?->is_active }}" == 1) {
            $('#{{ $notification_name }}_internal_notification').collapse('toggle');
            $('#{{ $notification_name }}_internal_notification_active').prop('checked', true).val(1);
        } else {
            $('[name="employee_to_receive_{{ $notification_name }}_internal_notification"], [name="{{ $notification_name }}_internal_notification_title_ar"], [name="{{ $notification_name }}_internal_notification_title_en"], [name="{{ $notification_name }}_internal_notification_body_ar"], [name="{{ $notification_name }}_internal_notification_body_en"]')
                .prop('required', true);
        }

        $('.{{ $notification_name }}_internal_notification_active_field').on('click', function(e) {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#{{ $notification_name }}_internal_notification_active').prop('checked', true).val(1);
                $('[name="employee_to_receive_{{ $notification_name }}_internal_notification"], [name="{{ $notification_name }}_internal_notification_title_ar"], [name="{{ $notification_name }}_internal_notification_title_en"], [name="{{ $notification_name }}_internal_notification_body_ar"], [name="{{ $notification_name }}_internal_notification_body_en"]')
                    .prop('required', true);
            } else {
                $('#{{ $notification_name }}_internal_notification_active').prop('checked', false).val(0);
                $('[name="employee_to_receive_{{ $notification_name }}_internal_notification"], [name="{{ $notification_name }}_internal_notification_title_ar"], [name="{{ $notification_name }}_internal_notification_title_en"], [name="{{ $notification_name }}_internal_notification_body_ar"], [name="{{ $notification_name }}_internal_notification_body_en"]')
                    .prop('required', true);
            }
        });
    }

    function handleSMSNotification_{{ $notification_name }}() {
        if ("{{ $general_sms_notification_setting?->is_active }}" == 1) {
            $('#{{ $notification_name }}_sms_notification').collapse('toggle');
            $('#{{ $notification_name }}_sms_notification_active').prop('checked', true).val(1);
        } else {
            $('[name="employee_to_receive_{{ $notification_name }}_sms_notification"], [name="{{ $notification_name }}_sms_notification_body_ar"], [name="{{ $notification_name }}_sms_notification_body_en"]')
                .prop('required', false);
        }

        $('.{{ $notification_name }}_sms_notification_active_field').on('click', function(e) {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#{{ $notification_name }}_sms_notification_active').prop('checked', true).val(1);
                $('[name="employee_to_receive_{{ $notification_name }}_sms_notification"], [name="{{ $notification_name }}_sms_notification_body_ar"], [name="{{ $notification_name }}_sms_notification_body_en"]')
                    .prop('required', true);
            } else {
                $('#{{ $notification_name }}_sms_notification_active').prop('checked', false).val(0);
                $('[name="employee_to_receive_{{ $notification_name }}_sms_notification"], [name="{{ $notification_name }}_sms_notification_body_ar"], [name="{{ $notification_name }}_sms_notification_body_en"]')
                    .prop('required', false);
            }
        });
    }

    function handleEmailNotification_{{ $notification_name }}() {
        if ("{{ $general_email_notification_setting?->is_active }}" == 1) {
            $('#{{ $notification_name }}_email_notification').collapse('toggle');
            $('#{{ $notification_name }}_email_notification_active').prop('checked', true).val(1);
        } else {
            $('[name="employee_to_receive_{{ $notification_name }}_email_notification"], [name="{{ $notification_name }}_email_notification_title_ar"], [name="{{ $notification_name }}_email_notification_title_en"]')
                .prop('required', false);
        }

        $('.{{ $notification_name }}_email_notification_active_field').on('click', function(e) {
            if ($(this).attr("aria-expanded") == 'true') {
                $('#{{ $notification_name }}_email_notification_active').prop('checked', true).val(1);
                $('[name="employee_to_receive_{{ $notification_name }}_email_notification"], [name="{{ $notification_name }}_email_notification_title_ar"], [name="{{ $notification_name }}_email_notification_title_en"]')
                    .prop('required', true);
            } else {
                $('#{{ $notification_name }}_email_notification_active').prop('checked', false).val(0);
                $('[name="employee_to_receive_{{ $notification_name }}_email_notification"], [name="{{ $notification_name }}_email_notification_title_ar"], [name="{{ $notification_name }}_email_notification_title_en"]')
                    .prop('required', false);
            }
        });
    }

    function handleNotificationsSettingsForm_{{ $notification_name }}() {
        $('#{{ $notification_name }}_notifications_settings').on('submit', function(e) {
            e.preventDefault();
            let employee_to_receive_internal_notification = $(
                '[name="employee_to_receive_{{ $notification_name }}_internal_notification"]').val();
            let employee_to_receive_email_notification = $(
                '[name="employee_to_receive_{{ $notification_name }}_email_notification"]').val();
            let employee_to_receive_sms_notification = $(
                '[name="employee_to_receive_{{ $notification_name }}_sms_notification"]').val();

            let data = $(this).serializeArray();
            data.push({
                name: "employee_to_receive_{{ $notification_name }}_internal_notification",
                value: employee_to_receive_internal_notification
            }, {
                name: "employee_to_receive_{{ $notification_name }}_email_notification",
                value: employee_to_receive_email_notification
            }, {
                name: "employee_to_receive_{{ $notification_name }}_sms_notification",
                value: employee_to_receive_sms_notification
            });

            ajaxRequest(`{{ url("store-notifications-settings/$notification_name") }}`, "POST", data).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                            '</div>');
                    });
                });
        });
    }

    function initElements_{{ $notification_name }}() {
        selectDeselectAll(
            $('#{{ $notification_name }}_intern_emp_select_all_btn'),
            $('#{{ $notification_name }}_intern_emp_deselect_all_btn'),
            '[name="employee_to_receive_{{ $notification_name }}_internal_notification"]'
        );

        selectDeselectAll(
            $('#{{ $notification_name }}_email_emp_select_all_btn'),
            $('#{{ $notification_name }}_email_emp_deselect_all_btn'),
            '[name="employee_to_receive_{{ $notification_name }}_email_notification"]'
        );

        ClassicEditor
            .create($('#{{ $notification_name }}_email_notification_body_ar')[0], {
                toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
            })
            .catch(error => {
                console.error(error);
            });

        ClassicEditor
            .create($('#{{ $notification_name }}_email_notification_body_en')[0], {
                toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
            })
            .catch(error => {
                console.error(error);
            });
    }
</script>
