@props(['employees', 'notifications_settings'])
<x-cards.card class="shadow-sm pb-5 px-5">
    <x-cards.card-header class="align-items-center py-1 px-2 gap-2 gap-md-5 mb-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#new_sell_notification">@lang('general::general.new_sell_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#payment_received_notification">@lang('general::general.payment_received_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#payments_notification">@lang('general::general.payments_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#new_booking_notification">@lang('general::general.new_booking_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#new_quotation_notification">@lang('general::general.new_quotation_notification')</a>
            </li>
        </ul>
    </x-cards.card-header>
    <div class="tab-content" id="myTabContent">
        <x-general::notifications.general-notification-tab active :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="new_sell" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="payment_received" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="payments" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="new_booking" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="new_quotation" variables=""/>
    </div>
</x-cards.card>
