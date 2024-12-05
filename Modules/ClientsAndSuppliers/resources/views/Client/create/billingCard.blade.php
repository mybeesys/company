<div class="card my-5 " data-section="billing">
    <!--begin::Card head-->
    {{-- <div class="card-header card-header-stretch custom-header">
        <!--begin::Title-->
        <div class="card-title d-flex align-items-center">

            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.Billing Address')</h3>
        </div>
        <!--end::Title-->


    </div> --}}
    <!--end::Card head-->

    <!--begin::Card body-->
    <div class="card-body card-body-billing">


        <div class="container">
            <div class="row">

                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <select id="billing_country"
                            class="form-select select-2 form-select-solid custom-input"
                            style="padding: 0px 12px;border: 1px solid var(--bs-gray-300);" name="billing_country">
                            <option value="">@lang('clientsandsuppliers::fields.select_country')</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name_en . ' - ' . $country->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="billing_street_name"
                            placeholder="@lang('clientsandsuppliers::fields.street_name')" id="billing_street_name" type="text">
                    </div>

                </div>

            </div>

            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="billing_city"
                            placeholder="@lang('clientsandsuppliers::fields.city')" id="billing_city" type="text">
                    </div>
                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="billing_state"
                            placeholder="@lang('clientsandsuppliers::fields.state')" id="billing_state" type="text">
                    </div>

                </div>

            </div>

            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="building_number"
                            placeholder="@lang('clientsandsuppliers::fields.building_number')" id="building_number" type="text">
                    </div>
                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="billing_postal_code"
                            placeholder="@lang('clientsandsuppliers::fields.postal_code')" id="billing_postal_code" type="text">
                    </div>
                </div>

            </div>





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
