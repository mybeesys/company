@props(['employees', 'notifications_settings'])
<x-cards.card class="shadow-sm pb-5 px-5">
    <x-cards.card-header class="align-items-center py-1 px-2 gap-2 gap-md-5 mb-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#new_order_notification">@lang('general::general.new_order_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#payment_paid_notification">@lang('general::general.payment_paid_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#items_received_notification">@lang('general::general.items_received_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#items_pending_notification">@lang('general::general.items_pending_notification')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#purchase_order_notification">@lang('general::general.purchase_order_notification')</a>
            </li>
        </ul>
    </x-cards.card-header>
    <div class="tab-content" id="myTabContent">
        <x-general::notifications.general-notification-tab active :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="new_order" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="payment_paid" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="items_received" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="items_pending" variables=""/>

        <x-general::notifications.general-notification-tab :employees="$employees" :notifications_settings="$notifications_settings"
            notification_name="purchase_order" variables=""/>
    </div>
</x-cards.card>
