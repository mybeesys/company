<div class="modal fade" id="kt_modal_add_company" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <form id="kt_modal_add_company_form">
                @csrf
                <input type="hidden" name="company_id" id="company_id">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal_title">{{ __('franchise::lang.save') }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.franchisee_name_ar') }}</label>
                            <input type="text" class="form-control form-control-solid" name="name_ar" id="name_ar" required />
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.franchisee_name_en') }}</label>
                            <input type="text" class="form-control form-control-solid" name="name_en" id="name_en" required />
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.city') }}</label>
                            <select name="city" id="city" class="form-select form-select-solid" data-control="select2"
                                data-dropdown-parent="#kt_modal_add_company" data-placeholder="{{ __('franchise::lang.city') }}" data-allow-clear="true">
                                <option></option>
                                @foreach (['riyadh', 'jeddah', 'dammam', 'khobar', 'dhahran', 'medina', 'mecca', 'taif', 'tabuk', 'hail', 'qassim', 'buraidah', 'unaizah', 'abha', 'khamis_mushait', 'jizan', 'najran', 'al_jouf', 'arar', 'jubail', 'yanbu'] as $city_key)
                                    <option value="{{ $city_key }}">{{ __('franchise::lang.cities.' . $city_key) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.street') }}</label>
                            <input type="text" class="form-control form-control-solid" name="street" id="street" />
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.national_address') }}</label>
                            <input type="text" class="form-control form-control-solid" name="national_address" id="national_address" />
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.vat_no') }}</label>
                            <input type="text" class="form-control form-control-solid" name="vat_no" id="vat_no" required />
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.mobile') }}</label>
                            <input type="text" class="form-control form-control-solid" name="mobile" id="mobile" required />
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.tel') }}</label>
                            <input type="text" class="form-control form-control-solid" name="tel" id="tel" />
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.email') }}</label>
                            <input type="email" class="form-control form-control-solid" name="email" id="email" required />
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.accounting_account') }}</label>
                            <select name="account" id="account" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#kt_modal_add_company">
                                @foreach (\Modules\Accounting\Models\AccountingAccount::where('status', 'active')->get() as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-12 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.product_permission') }}</label>
                            <select name="product_permission" id="product_permission" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#kt_modal_add_company">
                                <option value="absolute">{{ __('franchise::lang.permission_absolute') }}</option>
                                <option value="request">{{ __('franchise::lang.permission_request') }}</option>
                                <option value="denied">{{ __('franchise::lang.permission_denied') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                    <button type="submit" id="kt_modal_add_company_submit" class="btn btn-primary">
                        <span class="indicator-label">{{ __('franchise::lang.save') }}</span>
                        <span class="indicator-progress">{{ __('franchise::lang.please_wait') }}...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
