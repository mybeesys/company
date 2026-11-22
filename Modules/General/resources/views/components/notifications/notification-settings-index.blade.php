@props(['employees', 'notifications_settings', 'system_alert_preferences' => [], 'system_alert_groups' => []])
@php $firstH = \Modules\General\Support\SettingAccess::firstHorizontal(); @endphp
<div class="tab-pane fade {{ $firstH === 'notifications' ? 'show active' : '' }}" id="notifications_tab" role="tabpanel">
    <div class="d-flex flex-row-fluid gap-5">
        <ul
            class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-750px">
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3 active" data-bs-toggle="tab" href="#system_alerts_notifications">
                    @lang('general::notifications.system_alerts_nav')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#general_notifications">
                    @lang('general::general.general_notifications')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#clients_notification">
                    @lang('general::general.clients_notifications')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#suppliers_notification">
                    @lang('general::general.suppliers_notifications')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#inventory_notification">
                    @lang('inventory::notification.inventory_notifications')
                </a>
            </li>
        </ul>
        <div class="tab-content w-100" id="mySubTabContent">
            <div class="tab-pane fade show active" id="system_alerts_notifications" role="tabpanel">
                <x-general::notifications.system-alerts.main-tab
                    :employees="$employees"
                    :system_alert_preferences="$system_alert_preferences"
                    :system_alert_groups="$system_alert_groups"
                />
            </div>
            <div class="tab-pane fade" id="general_notifications" role="tabpanel">
                <x-general::notifications.general.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
            <div class="tab-pane fade" id="clients_notification" role="tabpanel">
                <x-general::notifications.clients.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
            <div class="tab-pane fade" id="suppliers_notification" role="tabpanel">
                <x-general::notifications.suppliers.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
            <div class="tab-pane fade" id="inventory_notification" role="tabpanel">
                <x-general::notifications.inventory.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
        </div>
    </div>
</div>
