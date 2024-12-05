<div class="card-toolbar m-0" style="    justify-items: center;">
    <!--begin::Tab nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
        <li class="nav-item" role="presentation">
            <a id="client_contacts_tab" class="nav-link justify-content-center fs-3  text-active-gray-800 active"
                data-bs-toggle="tab" role="tab" href="#client_contacts" aria-selected="true">
                @lang('clientsandsuppliers::fields.1')
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="Billing_Address__tab" class="nav-link justify-content-center fs-3 text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#Billing__Address" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.2')

            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="shipping_addresses_tab" class="nav-link justify-content-center fs-3 text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#shipping_addresses" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.3')
            </a>
        </li>
        {{-- <li class="nav-item" role="presentation">
            <a id="bank_account_information_tab"
                class="nav-link justify-content-center text-active-gray-800 fs-3 text-hover-gray-800" data-bs-toggle="tab"
                role="tab" href="#bank_account_information" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.4')
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a id="bank_account_information_tab1"
                class="nav-link justify-content-center text-active-gray-800 fs-3 text-hover-gray-800" data-bs-toggle="tab"
                role="tab" href="#bank_account_information1" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.5')
            </a>
        </li> --}}
    </ul>
    <!--end::Tab nav-->
</div>

<div class="tab-content">
    <div id="client_contacts" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="client_contacts_tab">
            @include('clientsandsuppliers::Client.empty-data')


    </div>
</div>

<div class="tab-content">
    <div id="Billing_Address" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="Billing_Address_tab">
            @include('clientsandsuppliers::Client.empty-data')
         </div>
</div>

<div class="tab-content">
    <div id="shipping_addresses" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="shipping_addresses_tab">
            @include('clientsandsuppliers::Client.empty-data')
          </div>
</div>

<div class="tab-content">
    <div id="bank_account_information" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="bank_account_information_tab">
            @include('clientsandsuppliers::Client.empty-data')

    </div>
</div>

<div class="tab-content">
    <div id="bank_account_information1" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="bank_account_information_tab1">
            @include('clientsandsuppliers::Client.empty-data')

    </div>
</div>
