@props(['employees', 'notifications_settings'])
<x-cards.card class="shadow-sm pb-5 px-5">
    <x-cards.card-header class="align-items-center py-1 px-2 gap-2 gap-md-5 mb-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#new_order_notification">@lang('inventory::notification.low_stock_amount_notification')</a>
            </li>
        </ul>
    </x-cards.card-header>
    <div class="tab-content" id="myTabContent">
        <x-general::notifications.general-notification-tab active :employees="$employees" :notifications_settings="$notifications_settings"
         variables="{product_name_ar}, {product_name_en}, {product_SKU}, {product_barcode}, {threshold}, {total_qty}" 
        notification_name="low_stock_alert_notification" variables=""/>
    </div>
</x-cards.card>
