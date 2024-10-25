@props(['permissions'])
<div class="modal fade" id="employee_permissions_edit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">@lang('employee::general.edit_permissions')</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5 pt-0">
                <form id="employee_permissions_edit_form" class="form" action="#">
                    <div class="d-flex flex-column me-n7" id="kt_modal_update_role_scroll" data-kt-scroll="true"
                        data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="500px"
                        data-kt-scroll-dependencies="#kt_modal_update_role_header"
                        data-kt-scroll-wrappers="#kt_modal_update_role_scroll" data-kt-scroll-offset="300px">
                        <x-employee::pos-roles.permissions-input :permissions=$permissions :header=false />
                    </div>
                    <input type="hidden" id="employee_id" name="employee_id" value="">
                    <div class="text-center pt-5">
                        <button type="reset" class="btn btn-light me-3"
                            data-bs-dismiss="modal">@lang('general.cancel')</button>
                        <button type="submit" class="btn btn-primary" data-kt-roles-modal-action="submit">
                            <span class="indicator-label">@lang('general.save')</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>