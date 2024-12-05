<div class="card1 mt-5" data-section="bankInfo">
    <!--begin::Card head-->
    <div class="card-header card-header-stretch custom-header">
        <!--begin::Title-->
        <div class="card-title d-flex align-items-center">

            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.bank_account_information')</h3>
        </div>
        <!--end::Title-->


    </div>
    <!--end::Card head-->

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container">
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.country_bank')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    @if (app()->getLocale() == 'ar')  {{ $country_bank->name_ar ?? '--' }}
                    @else
                    {{ $country_bank->name_en ?? '--' }}
                    @endif

                </label>
            </div>
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.currency')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $country_bank->currency_name_en ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.bank_name')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->bank_name ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.bank_account_name')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->bank_account_name ?? '--' }}
                </label>
            </div>
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.iban_number')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->iban_number ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.bank_account_number')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->bank_account_number ?? '--' }}
                </label>
            </div>
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.swift_code')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->swift_code ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.bank_address')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->bankAccountInformation->bank_address ?? '--' }}
                </label>
            </div>


            @if ($contact->bankAccountInformation->customInformation)
            @foreach ($contact->bankAccountInformation->customInformation as $custom)
                <div class="d-flex  align-items-center ">
                    <label class="fs-6 fw-semibold mb-2 me-3 "
                        style="width: 150px;">{{ $custom->lable ?? '--' }}</label>
                    <div class="form-check">
                        <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                            {{ $custom->value ?? '--' }}
                        </label>
                    </div>
                </div>
            @endforeach
        @endif




        </div>

    </div>

</div>
