@props(['employees', 'notifications_settings'])
<x-cards.card class="shadow-sm pb-5 px-5">
    <x-cards.card-header class="align-items-center py-1 px-2 gap-2 gap-md-5 mb-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#created_emp_notification">@lang('general::general.created_employee_notification')</a>
            </li>
        </ul>
    </x-cards.card-header>
    <div class="tab-content" id="myTabContent">
        <x-general::notifications.general-notification-tab active :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="created_emp"
            variables="{employee_pin}, {employee_total_wage}, {created_by}, {created_date}, {created_time}, {employee_name}, {employee_username}, {employee_password}"
            :internal="false" :sms="false" :email_receiver_hint="__('general::general.created_employee_receiver_hint')" :email_fields="['subject' => true, 'body' => true, 'bcc' => true, 'cc' => true, 'receiver' => false]" />
    </div>
</x-cards.card>
