<div class="card-toolbar m-0">
    <!--begin::Tab nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
        <li class="nav-item" role="presentation" @if ($contact->business_type != 'customer') hidden @endif>
            <a id="client_contacts_tab" class="nav-link justify-content-center text-active-gray-800 active"
                data-bs-toggle="tab" role="tab" href="#client_contacts" aria-selected="true">
                @lang('clientsandsuppliers::fields.client_contacts')
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="Billing_Address_tab" class="nav-link justify-content-center text-active-gray-800 @if ($contact->business_type != 'customer') active @endif"
                data-bs-toggle="tab" role="tab" href="#Billing_Address" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.Billing Address')

            </a>
        </li>
        <li class="nav-item" role="presentation" @if ($contact->business_type != 'customer') hidden @endif>
            <a id="shipping_addresses_tab" class="nav-link justify-content-center text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#shipping_addresses" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.shipping_addresses')
            </a>
        </li>
        <li class="nav-item" role="presentation" @if ($contact->business_type != 'customer') hidden @endif>
            <a id="bank_account_information_tab"
                class="nav-link justify-content-center text-active-gray-800 text-hover-gray-800" data-bs-toggle="tab"
                role="tab" href="#bank_account_information" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.bank_account_information')
            </a>
        </li>
    </ul>
    <!--end::Tab nav-->
</div>


<div class="tab-content  " @if ($contact->business_type != 'customer') hidden @endif>
    <div id="client_contacts" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="client_contacts_tab">
        @if ($contact->clientContacts)
            @include('clientsandsuppliers::Client.show.clientContactsCard')
        @else
            @include('clientsandsuppliers::Client.empty-data')
        @endif


    </div>
</div>

<div class="tab-content">
    <div id="Billing_Address" class="card-body p-0 tab-pane fade show @if ($contact->business_type != 'customer') active @endif" role="tabpanel"
        aria-labelledby="Billing_Address_tab">
        @if ($contact->billingAddress)
            @include('clientsandsuppliers::Client.show.billingCard')
        @else
            @include('clientsandsuppliers::Client.empty-data')
        @endif
    </div>
</div>

<div class="tab-content" @if ($contact->business_type != 'customer') hidden @endif>
    <div id="shipping_addresses" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="shipping_addresses_tab">
        @if ($contact->shippingAddress)
            @include('clientsandsuppliers::Client.show.shippingCard')
        @else
            @include('clientsandsuppliers::Client.empty-data')
        @endif
    </div>
</div>

<div class="tab-content" @if ($contact->business_type != 'customer') hidden @endif>
    <div id="bank_account_information" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="bank_account_information_tab">
        @if ($contact->bankAccountInformation)
            @include('clientsandsuppliers::Client.show.bankAccountCard')
        @else
            @include('clientsandsuppliers::Client.empty-data')
        @endif

    </div>
</div>
