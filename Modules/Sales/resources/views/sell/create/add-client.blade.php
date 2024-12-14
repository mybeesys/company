<div class="modal fade modal-xl" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClientModalLabel">@lang('clientsandsuppliers::general.add_clients')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClientForm">
                    @csrf
                    <div class="container">
                        <div class="row">
                            <div class="col-sm">
                                <div class="card" data-section="contact" style="border: 0;box-shadow: none">
                                    <div class="container">
                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 required"
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.client_name')
                                            </label>
                                            <input class="form-control form-control-solid custom-height"
                                                name="client_name" required
                                                placeholder="@lang('clientsandsuppliers::fields.client_name') / @lang('clientsandsuppliers::fields.organization_name')"
                                                type="text">
                                        </div>
                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.mobile_number')</label>
                                            <input class="form-control form-control-solid custom-height"
                                                name="mobile_number" placeholder="@lang('clientsandsuppliers::fields.mobile_number')"
                                                type="text">
                                        </div>


                                        <input  type="hidden" name="business_type" value="customer"/>

                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.phone_number')</label>
                                            <input class="form-control form-control-solid custom-height"
                                                name="phone_number" placeholder="@lang('clientsandsuppliers::fields.phone_number')"
                                                type="text">
                                        </div>
                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.email')</label>
                                            <input class="form-control form-control-solid custom-height" name="email"
                                                placeholder="@lang('clientsandsuppliers::fields.email')" type="text">
                                        </div>

                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.website')</label>
                                            <input class="form-control form-control-solid custom-height" name="website"
                                                placeholder="@lang('clientsandsuppliers::fields.website')"  type="text">
                                        </div>
                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.tax_number')</label>
                                            <input class="form-control form-control-solid custom-height"
                                                name="tax_number" placeholder="@lang('clientsandsuppliers::fields.tax_number')"
                                                type="text">
                                        </div>

                                        <div class="d-flex align-items-center mb-5">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.commercial_register')</label>
                                            <input class="form-control form-control-solid custom-height"
                                                name="commercial_register" placeholder="@lang('clientsandsuppliers::fields.commercial_register')"
                                                id="commercial_register" type="text">
                                        </div>


                                        <div class="d-flex  align-items-center ">
                                            <label class="fs-6 fw-semibold mb-2 me-3 "
                                                style="width: 150px;">@lang('clientsandsuppliers::fields.Point of sale client')</label>
                                            <div class="form-check">
                                                <input type="checkbox" style="border: 1px solid #9f9f9f;"
                                                    id="point_of_sale_client" name="point_of_sale_client"
                                                    class="form-check-input  my-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm">
                                <div class="card mb-5" data-section="billing">
                                    <!--begin::Card head-->
                                    <div class="card-header card-header-stretch custom-header">
                                        <!--begin::Title-->
                                        <div class="card-title d-flex align-items-center">

                                            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.Billing Address')</h3>
                                        </div>
                                        <!--end::Title-->


                                    </div>
                                    <!--end::Card head-->

                                    <!--begin::Card body-->
                                    <div class="card-body card-body-billing">


                                        <div class="container">
                                            <div class="row">

                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <select id="billing_country"
                                                            class="form-select select-2 form-select-solid custom-input"
                                                            style="padding: 0px 12px;border: 1px solid var(--bs-gray-300);"
                                                            name="billing_country">
                                                            <option value="">@lang('clientsandsuppliers::fields.select_country')</option>
                                                            @foreach ($countries as $country)
                                                                <option value="{{ $country->id }}">
                                                                    {{ $country->name_en . ' - ' . $country->name_ar }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                </div>
                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <input class="form-control form-control-solid custom-input"
                                                            name="billing_street_name"
                                                            placeholder="@lang('clientsandsuppliers::fields.street_name')" id="billing_street_name"
                                                            type="text">
                                                    </div>

                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <input class="form-control form-control-solid custom-input"
                                                            name="billing_city" placeholder="@lang('clientsandsuppliers::fields.city')"
                                                            id="billing_city" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <input class="form-control form-control-solid custom-input"
                                                            name="billing_state" placeholder="@lang('clientsandsuppliers::fields.state')"
                                                            id="billing_state" type="text">
                                                    </div>

                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <input class="form-control form-control-solid custom-input"
                                                            name="building_number" placeholder="@lang('clientsandsuppliers::fields.building_number')"
                                                            id="building_number" type="text">
                                                    </div>
                                                </div>
                                                <div class="col-sm">
                                                    <div
                                                        class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                                                        <input class="form-control form-control-solid custom-input"
                                                            name="billing_postal_code"
                                                            placeholder="@lang('clientsandsuppliers::fields.postal_code')" id="billing_postal_code"
                                                            type="text">
                                                    </div>
                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="separator d-flex flex-center my-6">
                        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                    </div>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </form>
            </div>
        </div>
    </div>
</div>
