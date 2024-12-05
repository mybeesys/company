<div class="card my-5" data-section="bankInfo">
    <!--begin::Card head-->
    {{-- <div class="card-header card-header-stretch custom-header">
        <!--begin::Title-->
        <div class="card-title d-flex align-items-center">

            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.bank_account_information')</h3>
        </div>
        <!--end::Title-->


    </div> --}}
    <!--end::Card head-->

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container">

            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        {{-- <input class="form-control form-control-solid custom-input" name="country_bank"
                            placeholder="@lang('clientsandsuppliers::fields.country_bank')" id="country_bank" type="text"> --}}
                        <select id="bankInfo_country_bank"
                            class="form-select select-2 form-select-solid custom-input"
                            style="padding: 0px 12px;border: 1px solid var(--bs-gray-300);"
                            name="bankInfo_country_bank">
                            <option value="">@lang('clientsandsuppliers::fields.country_bank')</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name_en . ' - ' . $country->name_ar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        {{-- <input class="form-control form-control-solid custom-input" name="currency"
                            placeholder="@lang('clientsandsuppliers::fields.currency')" id="currency" type="text"> --}}
                        <select id="bankInfo_currency"  class="form-select select-2 form-select-solid custom-input"
                            style="padding: 0px 12px;border: 1px solid var(--bs-gray-300);" name="bankInfo_currency">
                            <option value="">@lang('clientsandsuppliers::fields.currency')</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->currency_symbol_en  . ' - ' . $country->currency_name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_bank_name"
                            placeholder="@lang('clientsandsuppliers::fields.bank_name')" id="bank_name" type="text">
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_bank_account_name"
                            placeholder="@lang('clientsandsuppliers::fields.bank_account_name')" id="bank_account_name" type="text">
                    </div>
                </div>
            </div>



            <div class="row">
                <div class="col-12">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_iban_number"
                            placeholder="@lang('clientsandsuppliers::fields.iban_number')" id="bankInfo_iban_number" type="text">
                    </div>

                </div>
                <div class="col-12">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_bank_account_number"
                            placeholder="@lang('clientsandsuppliers::fields.bank_account_number')" id="bankInfo_bank_account_number" type="text">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_swift_code"
                            placeholder="@lang('clientsandsuppliers::fields.swift_code')" id="bankInfo_swift_code" type="text">
                    </div>

                </div>
                <div class="col-sm">
                    <div class="fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid ">
                        <input class="form-control form-control-solid custom-input" name="bankInfo_bank_address"
                            placeholder="@lang('clientsandsuppliers::fields.bank_address')" id="bankInfo_bank_address" type="text">
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
