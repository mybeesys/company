@props(['employees', 'notifications_settings'])
<div class="tab-pane fade" id="notifications_tab" role="tabpanel">
    <div class="d-flex flex-row-fluid gap-5">
        <ul
            class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-750px">
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3 active" data-bs-toggle="tab" href="#general_notifications">
                    @lang('general::general.general_notifications')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#clients_notification">
                    @lang('general::general.clients_notifications')
                </a>
            </li>
        </ul>
        <div class="tab-content w-100" id="mySubTabContent">
            <div class="tab-pane fade show active" id="general_notifications" role="tabpanel">
                <x-general::notifications.general.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
            <div class="tab-pane fade" id="clients_notification" role="tabpanel">
                <x-general::notifications.clients.main-tab :employees="$employees" :notifications_settings="$notifications_settings" />
            </div>
        </div>
    </div>
</div>
