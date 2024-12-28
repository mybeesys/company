<div class="modal fade" id="kt_modal_edit_cost_center" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content" @if (session()->get('locale') == 'ar') dir="rtl" @endif>
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>


            <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-10">
                <div class="text-center mb-5">
                    <h1 class="mb-3">@lang('accounting::lang.edit_cost_center')</h1>
                </div>

                <div class="separator d-flex flex-center mb-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>


                <form action="{{ route('cost-center-update') }}" method="POST">
                    @csrf

                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.costCenter_name_ar')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" id="name_ar" required
                            name="name_ar" value="">
                    </div>

                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.costCenter_name_en')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" id="name_en" required
                            name="name_en" value="">
                    </div>


                    <div class="d-flex  align-items-center ">
                        <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('accounting::lang.is_main')</label>
                        <div class="form-check">
                        <input type="checkbox" style="border: 1px solid #9f9f9f;" id="costCenter_is_main" name="is_main"
                            class="form-check-input  my-2">
                        </div>
                    </div>

                    <input type="text" id="costCenter_id" hidden class="form-control form-control-solid"
                        name="costCenter_id" value="null">
                    <div class="text-center">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            @lang('messages.cancel')
                        </button>

                        <button type="submit" class="btn btn-primary"
                            @if (app()->getLocale() == 'ar') style="margin-right: 8px;" @endif>
                            <span class="indicator-label">@lang('messages.submit')</span>
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
