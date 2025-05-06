<div class="modal fade" id="kt_modal_edit_account" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content" @if (session()->get('locale') == 'ar') dir="rtl" @endif>
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>


            <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-10">
                <div class="text-center mb-5">
                    <h1 class="mb-3">@lang('accounting::lang.edit_account')</h1>
                </div>

                <div class="separator d-flex flex-center mb-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>


                <form action="{{ route('update-account') }}" method="POST">
                    @csrf

                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_name_ar')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" id="name_ar" required
                            name="name_ar" value="">
                    </div>

                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_name_en')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" id="name_en" required
                            name="name_en" value="">
                    </div>
                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.gl_code')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" id="gl_code" required
                            name="gl_code" value="">
                    </div>

                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_type')</span>
                        </label>
                        <select id="kt_ecommerce_select2_account_type data-kt-menu-placement" required
                            class="form-select select-2 form-select-solid select-2 kt_ecommerce_select2_account_type_" name="account_type">
                            <option value="" >@lang('messages.select') </option>
                            @foreach ($account_main_types as $key => $vale)
                                <option @if ('normal' === $key) selected @endif value="{{ $key }}">
                                    {{ $vale }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_category')</span>
                        </label>
                        <select id="kt_ecommerce_select2_account_category data-kt-menu-placement" required
                            class="form-select select-2 form-select-solid select-2 kt_ecommerce_select2_account_category_" name="account_category">
                            <option value="" >@lang('messages.select')</option>
                            @foreach ($account_category as $key => $vale)
                                <option @if (Session::get('account_category') == $key) selected @endif value="{{ $key }}">
                                    {{ $vale }}</option>
                            @endforeach
                        </select>
                    </div> --}}

                    <input type="text" id="account_id" hidden class="form-control form-control-solid" required
                        name="account_id" value="">
                    <div class="text-center">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            @lang('messages.cancel')
                        </button>

                        <button type="submit" class="btn btn-primary"
                            @if (app()->getLocale() == 'ar') style="margin-right: 8px;" @endif>
                            <span class="indicator-label">@lang('messages.update')</span>
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
