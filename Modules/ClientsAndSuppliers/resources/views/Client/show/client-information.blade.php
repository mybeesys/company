<div class="card" data-section="contact" style="border: 0;box-shadow: none">
    <div class="container">
        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">
                @if ($contact->business_type != 'customer') 
                @lang('clientsandsuppliers::fields.supplier_name')

                @else
                @lang('clientsandsuppliers::fields.client_name')

                @endif
            </label>
            <label class="fs-4 fw-bold mb-2 me-3 " style="width: 150px;">
                {{ $contact->name ?? '--' }}
            </label>
        </div>
        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.mobile_number')</label>
            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 150px;">
                {{ $contact->mobile_number ?? '--' }}
            </label>
        </div>


        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.phone_number')</label>
            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 150px;">
                {{ $contact->phone_number ?? '--' }}
            </label>
        </div>
        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.email')</label>
            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 150px;">
                {{ $contact->email ?? '--' }}
            </label>
        </div>

        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.website')</label>
            <a href="{{ $contact->website ?? '#' }}" class="fs-6 fw-semibold mb-2 me-3 " style="width: 200px;">
                {{ $contact->website ?? '--' }}
            </a>
        </div>
        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.tax_number')</label>
            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                {{ $contact->tax_number ?? '--' }}
            </label>
        </div>

        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.commercial_register')</label>
            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                {{ $contact->commercial_register ?? '--' }}
            </label>
        </div>


        <div class="d-flex  align-items-center ">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.Point of sale client')</label>
            <div class="form-check">
                <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                    {{ $contact->point_of_sale_client ?? '--' }}
                </label>
            </div>
        </div>

        @foreach ($contact->customInformation as $custom)
            <div class="d-flex  align-items-center ">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">{{ $custom->lable ?? '--' }}</label>
                <div class="form-check">
                    <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                        {{ $custom->value ?? '--' }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>


</div>
