<div class="modal fade" id="kt_modal_{{ $action }}_role" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2>@lang('employee::general.add_role')</h2>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <form id="{{ $action }}_role_form" class="form">
                    <x-employee::roles.form :departments=$departments />
                    <x-employee::modals.modals-buttons modal="kt_modal_{{ $action }}_role" />
                </form>
            </div>
        </div>
    </div>
</div>
