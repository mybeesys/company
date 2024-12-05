<div class="card my-5" data-section="shipping">
    <!--begin::Card head-->
    <div class="card-header card-header-stretch custom-header">
        <!--begin::Title-->
        {{-- <div class="card-title d-flex align-items-center">

            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.shipping_addresses')</h3>
        </div> --}}
        <!--end::Title-->

        <div class="d-flex  align-items-center form-check " style="padding-right: 0px">
            <input type="checkbox" style="border: 1px solid #9f9f9f;" id="copyBillingAddress"
                class="form-check-input mx-3 my-2">

            <label class="fs-6 fw-semibold mb-2 me-3 ">@lang('clientsandsuppliers::fields.Copy billing address')</label>
        </div>

    </div>
    <!--end::Card head-->

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container" id="shipping">

            <div class="row">

                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        {{-- <input class="form-control form-control-solid custom-input" name="country"
                            placeholder="@lang('clientsandsuppliers::fields.country')" id="country" type="text"> --}}
                        <select id="shipping_country" class="form-select select-2 form-select-solid custom-input"
                            style="padding: 0px 12px;border: 1px solid var(--bs-gray-300);" name="shipping_country">
                            <option value="">@lang('clientsandsuppliers::fields.select_country')</option>
                            @foreach ($countries as $country)
                                <option @if ($contact->shippingAddress?->country == $country->id) selected @endif value="{{ $country->id }}">
                                    {{ $country->name_en . ' - ' . $country->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="shipping_street_name"
                            placeholder="@lang('clientsandsuppliers::fields.street_name')" value="{{ $contact->shippingAddress?->street_name }}"
                            id="shipping_street_name" type="text">
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="shipping_city"
                            placeholder="@lang('clientsandsuppliers::fields.city')" value="{{ $contact->shippingAddress?->city }}"
                            id="shipping_city" type="text">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="shipping_state"
                            placeholder="@lang('clientsandsuppliers::fields.state')" value="{{ $contact->shippingAddress?->state }}"
                            id="shipping_state" type="text">
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="shipping_postal_code"
                            placeholder="@lang('clientsandsuppliers::fields.postal_code')" value="{{ $contact->shippingAddress?->postal_code }}"
                            id="shipping_postal_code" type="text">
                    </div>

                </div>
            </div>

            @if ($contact->business_type == 'customer')
                        @if ($contact->shippingAddress->customInformation)
                @foreach ($contact->shippingAddress->customInformation as $key => $custom)
                    <div class="row">
                        <div class="col-sm">
                            <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid"><input
                                    class="form-control form-control-solid custom-input" name="shipping_customLable[]"
                                    placeholder="@lang('clientsandsuppliers::fields.customLable')" value="{{ $custom->lable }}" type="text"></div>
                        </div>
                        <div class="col-sm">
                            <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid"><input
                                    class="form-control form-control-solid custom-input" name="shipping_customValue[]"
                                    placeholder="@lang('clientsandsuppliers::fields.customValue')" value="{{ $custom->value }}" type="text"></div>
                        </div>
                    </div>
                @endforeach
            @endif
            @endif





        </div>

        <div class="d-flex " style="align-items: center">
            <button type="button" class="btn btn-xs btn-default text-primary add-custom-fields-btn px-1"> <i
                    class="ki-outline ki-plus fs-2"></i>
                @lang('clientsandsuppliers::fields.Add custom fields')
            </button>

            <span class=" mt-2" data-bs-toggle="tooltip" title="@lang('clientsandsuppliers::fields.tooltip_custom_fields')">
                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
            </span>

        </div>
    </div>

</div>
