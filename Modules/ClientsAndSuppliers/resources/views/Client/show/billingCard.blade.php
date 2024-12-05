<div class="card1 mt-5" data-section="billing">
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
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.country')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    @if (app()->getLocale() == 'ar')  {{ $country_billingAddress?->name_ar ?? '--' }}
                    @else
                    {{ $country_billingAddress?->name_en ?? '--' }}
                    @endif
                </label>
            </div>


            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.street_name')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->billingAddress->street_name ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.city')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->billingAddress->city ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.state')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->billingAddress->state ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.building_number')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->billingAddress->building_number ?? '--' }}
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.postal_code')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->billingAddress->postal_code ?? '--' }}
                </label>
            </div>

            @if ($contact->billingAddress->customInformation)
            @foreach ($contact->billingAddress->customInformation as $custom)
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
