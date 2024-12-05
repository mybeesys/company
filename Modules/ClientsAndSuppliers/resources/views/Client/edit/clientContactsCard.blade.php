<div class="card my-5">
    <!-- Card Header -->
    <div class="card-header card-header-stretch custom-header">
        <div class="card-title d-flex align-items-center">
            <h3 class="fw-bold m-0 text-gray-800">@lang('clientsandsuppliers::fields.client_contacts')</h3>
        </div>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-xs btn-default text-primary" id="addContactCard">
                <i class="ki-outline ki-plus fs-2"></i>
                @lang('clientsandsuppliers::fields.Add another contact')
            </button>
        </div>
    </div>

    <!-- Card Body -->
    <div class="card-body">
        <div id="contactCardContainer">
            @if (count($contact->clientContacts)==0)
            <div class="contact-card">
                <!-- Contact Title -->
                <h4 class="fw-bold text-gray-700 mb-3">
                    @lang('clientsandsuppliers::fields.contact') (1)
                </h4>
                <!-- First Card Content -->
                <div class="container">
                    <div class="row g-3">
                        <div class="col-md-12 d-flex align-items-center ">
                            <input class="form-control form-control-solid custom-input" name="client_contact_name[]"
                                required placeholder="@lang('clientsandsuppliers::fields.client_contact_name')" type="text">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>
                        </div>
                        <div class="col-md-12 d-flex align-items-center ">
                            <input class="form-control form-control-solid custom-input" name="client_contact_email[]"
                                @if (session()->get('locale') == 'ar') style="text-align:end;" @endif required
                                placeholder="@lang('clientsandsuppliers::fields.client_contact_email')" type="email">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>

                        </div>
                        <div class="col-md-12 d-flex align-items-center">
                            <input class="form-control form-control-solid custom-input"
                                name="client_contact_mobile_number[]" required placeholder="@lang('clientsandsuppliers::fields.client_contact_mobile_number')"
                                type="text">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>

                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input"
                                name="alternative_mobile_number[]" placeholder="@lang('clientsandsuppliers::fields.alternative_mobile_number')" type="text">
                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input"
                                name="client_contact_department[]" placeholder="@lang('clientsandsuppliers::fields.client_contact_department')" type="text">
                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input" name="client_contact_position[]"
                                placeholder="@lang('clientsandsuppliers::fields.client_contact_position')" type="text">
                        </div>
                    </div>
                    <!-- Delete Button -->
                    <button type="button" class="btn btn-danger btn-sm mt-3 remove-contact-card">
                        @lang('clientsandsuppliers::fields.delete_contact')
                    </button>
                </div>
                <!-- Separator -->
                <div class="separator d-flex flex-center my-6">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>
            </div>
            @else
            @foreach ($contact->clientContacts as $key => $clientContact)
            <div class="contact-card">
                <!-- Contact Title -->
                <h4 class="fw-bold text-gray-700 mb-3">
                    @lang('clientsandsuppliers::fields.contact') ({{ $key + 1 }})
                </h4>
                <input type="hidden" name="client_contact_id[]" value="{{ $clientContact->id }}" />

                <!-- First Card Content -->
                <div class="container">
                    <div class="row g-3">
                        <div class="col-md-12 d-flex align-items-center ">
                            <input class="form-control form-control-solid custom-input" name="client_contact_name[]"
                                required value="{{ $clientContact->name }}" placeholder="@lang('clientsandsuppliers::fields.client_contact_name')"
                                type="text">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>
                        </div>
                        <div class="col-md-12 d-flex align-items-center ">
                            <input class="form-control form-control-solid custom-input"
                                name="client_contact_email[]" value="{{ $clientContact->email }}"
                                @if (session()->get('locale') == 'ar') style="text-align:end;" @endif required
                                placeholder="@lang('clientsandsuppliers::fields.client_contact_email')" type="email">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>

                        </div>
                        <div class="col-md-12 d-flex align-items-center">
                            <input class="form-control form-control-solid custom-input"
                                value="{{ $clientContact->mobile_number }}" name="client_contact_mobile_number[]"
                                required placeholder="@lang('clientsandsuppliers::fields.client_contact_mobile_number')" type="text">
                            <label class="fs-3 fw-semibold me-3 mb-0  required"></label>

                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input"
                                value="{{ $clientContact->alternative_mobile_number }}"
                                name="alternative_mobile_number[]" placeholder="@lang('clientsandsuppliers::fields.alternative_mobile_number')" type="text">
                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input"
                                value="{{ $clientContact->department }}" name="client_contact_department[]"
                                placeholder="@lang('clientsandsuppliers::fields.client_contact_department')" type="text">
                        </div>
                        <div class="col-md-12">
                            <input class="form-control form-control-solid custom-input"
                                value="{{ $clientContact->position }}" name="client_contact_position[]"
                                placeholder="@lang('clientsandsuppliers::fields.client_contact_position')" type="text">
                        </div>
                    </div>
                    <!-- Delete Button -->
                    <button type="button" class="btn btn-danger btn-sm mt-3 remove-contact-card">
                        @lang('clientsandsuppliers::fields.delete_contact')
                    </button>
                </div>
                <!-- Separator -->
                <div class="separator d-flex flex-center my-6">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>
            </div>
        @endforeach
            @endif

        </div>
    </div>
</div>
