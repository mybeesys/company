<div class="modal fade" id="kt_modal_edit_tax" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content" @if (session()->get('locale') == 'ar') dir="rtl" @endif>
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>


            <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-10">
                <div class="text-center mb-5">
                    <h1 class="mb-3">@lang('general::general.edit_tax')</h1>
                </div>

                <div class="separator d-flex flex-center mb-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>


                <form action="{{ route('update-tax') }}" method="POST">
                    @csrf

                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('general::fields.tax_name')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" required name="tax_name"
                            value="">
                        <input type="hidden" name="id">
                    </div>


                    <div class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('general::fields.tax_name_en')</span>
                        </label>
                        <input type="text" class="form-control form-control-solid" required name="tax_name_en"
                            value="">
                    </div>

                    <input  type="hidden" name="group_tax_checkbox">

                    <div id="tax-amount-container-edit" class="fv-row mb-5 fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('general::fields.tax_amount')</span>
                        </label>
                        <input type="number" class="form-control form-control-solid" name="tax_amount" value="">
                    </div>

                    <div id="group-tax-container-edit" class="fv-row mb-5 fv-plugins-icon-container" style="display: none;">
                        <label class="fs-6 fw-semibold form-label mt-3">
                            <span class="required">@lang('menuItemLang.taxes')</span>
                        </label>
                        <select class="form-select d-flex form-select-solid" id="tax-list-container-edit" data-placeholder="@lang('messages.select')"
                            name="group_tax[]" multiple>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}">
                                    @if (session()->get('locale') == 'ar')
                                        {{ $tax->name }}
                                    @else
                                        {{ $tax->name_en }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

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

