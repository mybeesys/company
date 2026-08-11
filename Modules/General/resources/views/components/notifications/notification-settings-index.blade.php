@props(['employees', 'notifications_settings'])
@php
    $showGeneral = tenant_setting_entitled('notifications_general');
    $showClients = tenant_setting_entitled('notifications_clients');
    $showSuppliers = tenant_setting_entitled('notifications_suppliers');
    $showInventory = tenant_setting_entitled('notifications_inventory');
    $firstPane = match (true) {
        $showGeneral => 'general_notifications',
        $showClients => 'clients_notification',
        $showSuppliers => 'suppliers_notification',
        $showInventory => 'inventory_notification',
        default => null,
    };
@endphp
<div class="tab-pane fade" id="notifications_tab" role="tabpanel">
    @if ($firstPane)
        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-750px">
                @if ($showGeneral)
                    <li class="nav-item w-md-200px me-0">
                        <a class="nav-link py-3 {{ $firstPane === 'general_notifications' ? 'active' : '' }}" data-bs-toggle="tab" href="#general_notifications">
                            @lang('general::general.general_notifications')
                        </a>
                    </li>
                @endif
                @if ($showClients)
                    <li class="nav-item w-md-200px me-0">
                        <a class="nav-link py-3 {{ $firstPane === 'clients_notification' ? 'active' : '' }}" data-bs-toggle="tab" href="#clients_notification">
                            @lang('general::general.clients_notifications')
                        </a>
                    </li>
                @endif
                @if ($showSuppliers)
                    <li class="nav-item w-md-200px me-0">
                        <a class="nav-link py-3 {{ $firstPane === 'suppliers_notification' ? 'active' : '' }}" data-bs-toggle="tab" href="#suppliers_notification">
                            @lang('general::general.suppliers_notifications')
                        </a>
                    </li>
                @endif
                @if ($showInventory)
                    <li class="nav-item w-md-200px me-0">
                        <a class="nav-link py-3 {{ $firstPane === 'inventory_notification' ? 'active' : '' }}" data-bs-toggle="tab" href="#inventory_notification">
                            @lang('inventory::notification.inventory_notifications')
                        </a>
                    </li>
                @endif
            </ul>
            <div class="tab-content w-100" id="mySubTabContent">
                @if ($showGeneral)
                    <div class="tab-pane fade {{ $firstPane === 'general_notifications' ? 'show active' : '' }}" id="general_notifications" role="tabpanel">
                        <x-general::notifications.general.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
                    </div>
                @endif
                @if ($showClients)
                    <div class="tab-pane fade {{ $firstPane === 'clients_notification' ? 'show active' : '' }}" id="clients_notification" role="tabpanel">
                        <x-general::notifications.clients.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
                    </div>
                @endif
                @if ($showSuppliers)
                    <div class="tab-pane fade {{ $firstPane === 'suppliers_notification' ? 'show active' : '' }}" id="suppliers_notification" role="tabpanel">
                        <x-general::notifications.suppliers.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
                    </div>
                @endif
                @if ($showInventory)
                    <div class="tab-pane fade {{ $firstPane === 'inventory_notification' ? 'show active' : '' }}" id="inventory_notification" role="tabpanel">
                        <x-general::notifications.inventory.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
