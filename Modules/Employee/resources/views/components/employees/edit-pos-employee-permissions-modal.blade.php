@props(['permissions'])
<x-general.modal module="employee" id='employee_pos_permissions_edit' body_class="pt-0" title='edit_permissions' class='mw-800px'>
    <div class="d-flex flex-column me-n7" data-kt-scroll="true"
        data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="500px"
        data-kt-scroll-dependencies="#kt_modal_update_role_header" data-kt-scroll-wrappers="#kt_modal_update_role_scroll"
        data-kt-scroll-offset="300px">
        <x-employee::pos-roles.permissions-input :permissions=$permissions :header=false />
    </div>
</x-general.modal>
