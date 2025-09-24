@props(['permissions', 'modules','dashboardRoles'])
<x-general.modal module="employee" id='employee_dashboard_permissions_edit' body_class="pt-0" title='edit_permissions' class='mw-1000px'>
    <div class="d-flex flex-column me-n7">
        <div class="mb-4">
            <label for="dashboard_role_ids" class="form-label">@lang('employee::fields.dashboard_roles')</label>
            <select class="form-select" id="dashboard_role_ids" name="dashboard_role_ids[]" multiple data-control="select2" data-allow-clear="true">
                {{-- Iterate over the roles and create an option for each one --}}
                @foreach($dashboardRoles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <x-employee::dashboard-roles.permissions-input :modules=$modules />
    </div>
</x-general.modal>