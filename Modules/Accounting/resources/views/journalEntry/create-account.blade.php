<div class="modal fade" id="kt_modal_create_account" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content" @if (session()->get('locale') == 'ar') dir="rtl" @endif>
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>

            <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-10">
                <div class="text-center mb-5">
                    <h1 class="mb-3">@lang('accounting::lang.add_account')</h1>
                </div>

                <div class="separator d-flex flex-center mb-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>

                <form id="addAccountForm" method="POST">
                    @csrf
                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.parent_account')</span>
                        </label>
                        <select id="kt_ecommerce_select2_account_type" required
                            class="form-select select-2 form-select-solid" name="account_id">
                            <option value="" selected>@lang('messages.select')</option>
                            @foreach ($parents_account as $account)
                                <option value="{{ $account->id }}">
                                    ({{ $account->gl_code }}) -
                                    @if (app()->getLocale() == 'ar')
                                        {{ $account->name_ar }} - <span
                                            class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                    @else
                                        {{ $account->name_en }} - <span
                                            class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_name_ar')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" required name="name_ar" value="">
                    </div>

                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_name_en')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" required name="name_en" value="">
                    </div>

                    <div class="fv-row mb-5" hidden>
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_type')</span>
                        </label>
                        <select id="kt_ecommerce_select2_account_type" required
                            class="form-select select-2 form-select-solid" name="account_type">
                            @foreach ($account_main_types as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-5" hidden>
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('accounting::lang.account_category')</span>
                        </label>
                        <select id="kt_ecommerce_select2_account_category" required
                            class="form-select select-2 form-select-solid" name="account_category">
                            @foreach ($account_category as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-center">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">
                            @lang('messages.cancel')
                        </button>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
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
