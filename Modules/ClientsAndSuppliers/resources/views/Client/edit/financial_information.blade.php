<div class="card my-5" data-section="financial">

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container" id="financial">

            <div class="row">

                <div class="d-flex align-items-center mb-5">
                    <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::fields.payment_terms')</label>

                    <div class="col-md-9 d-flex align-items-center ">
                        <input class="form-control form-control-solid custom-input" name="payment_terms"
                            placeholder="@lang('sales::fields.payment_terms')" value="{{ $contact->payment_terms }}" type="number" min="0">
                        <label class="fs-3 fw-semibold me-3 mb-0   "></label>

                    </div>
                </div>
            </div>
            <div class="row mb-5">
                <div class="d-flex align-items-center">
                    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;" for="account_id">
                        @lang('clientsandsuppliers::fields.accounting_account')
                    </label>
                    <div class="col-md-9">
                        <select id="account_id"
                            class="form-select select-2 form-select-solid kt_ecommerce_select2_account"
                            name="account_id" required data-placeholder="@lang('clientsandsuppliers::fields.select_account')">
                            <option value="">@lang('clientsandsuppliers::fields.select_account')</option>

                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    @selected(old('account_id', $contact->account_id) == $account->id)>
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
                        <div class="form-text text-muted">@lang('clientsandsuppliers::fields.accounting_account_required_hint')</div>
                    </div>
                </div>
            </div>



            <div class="col-sm">
                <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                    <input class="form-control form-control-solid custom-input" dir="ltr" style="text-align: end;"
                        name="credit_limit" placeholder="@lang('clientsandsuppliers::fields.credit_limit')" id="credit_limit"
                        value="{{ $contact->credit_limit }}" type="number">
                </div>
            </div>


        </div>
    </div>

</div>
