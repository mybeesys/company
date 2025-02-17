<div class="card1 mt-5" data-section="bankInfo">
    <!--begin::Card head-->
    <div class="card-header card-header-stretch custom-header">
        <!--begin::Title-->
        <div class="card-title d-flex align-items-center">

            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.financial_information')</h3>
        </div>
        <!--end::Title-->


    </div>
    <!--end::Card head-->

    <!--begin::Card body-->
    <div class="card-body">


        <div class="container">
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('sales::fields.payment_terms')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">

                    @lang('sales::lang.terms.' . $contact->payment_terms)


                </label>
            </div>
            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('accounting::fields.account')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    @if (app()->getLocale() == 'ar')
                        {{ $contact->account?->gl_code . '-' . $contact->account?->name_ar }}
                    @else
                        {{ $contact->account?->gl_code . '-' . $contact->account?->name_ar }}
                    @endif
                </label>
            </div>

            <div class="d-flex align-items-center mb-5">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.credit_limit')</label>
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    @format_currency($contact->credit_limit) 
                </label>
            </div>





        </div>

    </div>

</div>
