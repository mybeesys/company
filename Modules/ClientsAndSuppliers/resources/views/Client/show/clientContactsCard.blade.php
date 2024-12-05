<div class="card1 mt-5">
    <!-- Card Header -->
    <div class="card-header1 card-header-stretch custom-header">
        <div class="card-title d-flex align-items-center">
            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.client_contacts')</h3>
        </div>

    </div>

    <!-- Card Body -->
    <div class="card-body">
        <div id="contactCardContainer">

            @foreach ($contact->clientContacts as $clientContact)
            <div class="contact-card">

                <div class="container">
                    <div class="row g-3">

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.client_contact_name')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                                {{ $clientContact->name ?? '--' }}
                            </label>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.client_contact_email')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 150px;">
                                {{ $clientContact->email ?? '--' }}
                            </label>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.client_contact_mobile_number')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                                {{ $clientContact->mobile_number ?? '--' }}
                            </label>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.alternative_mobile_number')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 160px;">
                                {{ $clientContact->alternative_mobile_number ?? '--' }}
                            </label>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.client_contact_department')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                                {{ $clientContact->department ?? '--' }}
                            </label>
                        </div>

                        <div class="d-flex align-items-center mb-5">
                            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 170px;">@lang('clientsandsuppliers::fields.client_contact_position')</label>
                            <label class="fs-6 fw-bold mb-2 me-3 " style="width: 200px;">
                                {{ $clientContact->position ?? '--' }}
                            </label>
                        </div>


                    </div>

                </div>
                <!-- Separator -->
                {{-- <hr class="my-4"> --}}
            </div>
            @endforeach




        </div>
    </div>
</div>
