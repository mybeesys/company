<div class="modal fade" id="kt_modal_deactive" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content" @if (session()->get('locale') == 'ar') dir="rtl" @endif>
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>


            <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-10">


                <form action="{{ route('change-status-cost-center') }}" method="POST">
                    @csrf

                    <input type="text" hidden id="cost_center_id_" class="form-control form-control" name="cost_center_id"
                        value="{{ session()->get('cost_center_id') }}">
                    <ul class="swal2-progress-steps" style="display: none;"></ul>
                    <div class="swal2-icon swal2-warning swal2-icon-show" style="display: flex;">
                        <div class="swal2-icon-content">!</div>
                    </div><img class="swal2-image" style="display: none;">


                    <div class="text-center border-0 pt-5">

                        <span class="text-muted  fw-semibold my-10 fs-4">@lang('accounting::lang.note_deactive_cc')</span>
                    </div>
                    <div class="text-center py-10">

                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            @lang('messages.No,return')
                        </button>

                        <button type="submit" class="btn btn-primary"
                            @if (app()->getLocale() == 'ar') style="margin-right: 8px;" @endif>
                            <span class="indicator-label">@lang('messages.Yes, deactive it!')</span>
                            <span class="indicator-progress">
                                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
