@props(['permissions', 'modules'])
<x-general.modal module="employee" id='employee_dashboard_permissions_edit' body_class="pt-0" title='edit_permissions' class='mw-1000px'>
    <div class="d-flex flex-column me-n7">
        <x-employee::dashboard-roles.permissions-input :modules=$modules />
    </div>
</x-general.modal>
