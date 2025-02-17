<div class="card my-5" data-section="financial">

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container" id="financial">

            <div class="row">

                <div class="d-flex align-items-center mb-5">
                    {{-- <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::fields.payment_terms')</label> --}}
                    <select name="payment_terms" id="payment_terms" class="form-select select-2 form-select-solid ">
                        <option value="">@lang('sales::fields.payment_terms')</option>

                        @foreach ($payment_terms as $key => $value)
                            <option @if ($contact->payment_terms == $key) selected @endif value="{{ $key }}">
                                {{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">

                <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account mb-5"
                    name="account_id" required>
                    <option value="">@lang('clientsandsuppliers::fields.select_account')</option>

                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @if ($contact->account_id == $account->id) selected @endif>
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



            <div class="col-sm">
                <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                    <input class="form-control form-control-solid custom-input" dir="ltr" style="text-align: end;"
                        name="credit_limit" placeholder="@lang('clientsandsuppliers::fields.credit_limit')" id="credit_limit"
                        value="{{ $contact->credit_limit }}" type="number">
                </div>
            </div>


        </div>

        {{-- <div class="d-flex " style="align-items: center">
            <button type="button" class="btn btn-xs btn-default text-primary add-custom-fields-btn px-1"> <i
                    class="ki-outline ki-plus fs-2"></i>
                @lang('clientsandsuppliers::fields.Add custom fields')
            </button>

            <span class=" mt-2" data-bs-toggle="tooltip" title="@lang('clientsandsuppliers::fields.tooltip_custom_fields')">
                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
            </span>

        </div> --}}
    </div>

</div>
