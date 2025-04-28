<div class="tab-pane fade show active" id="general_setting_tab" role="tabpanel">
    <div class="">


        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 active" data-bs-toggle="tab" href="#company_details-tab">
                        @lang('establishment::general.company_details')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 " data-bs-toggle="tab" href="#general-tab">
                        @lang('general::general.general_settings')
                    </a>
                </li>

                <li class="nav-item w-md-200px me-0 py-1 nav-link-taxes">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#taxes_tab">@lang('menuItemLang.taxes')</a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1 nav-link-methods">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#payemnt_methods_tab">@lang('general::lang.payment_methods')</a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 " data-bs-toggle="tab" href="#sales-tab">
                        @lang('menuItemLang.sales')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#purchases-tab">
                        @lang('menuItemLang.purchases')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#enabledModules-tab">
                        @lang('general::general.Module Management')
                    </a>
                </li>
            </ul>

            <div class="tab-content w-100" id="invoice-tab">
                <div class="tab-pane fade show " id="general-tab" role="tabpanel">
                    @include('general::general-setting.general_settings')

                </div>
                <div class="tab-pane fade show active" id="company_details-tab" role="tabpanel">
                    @include('general::general-setting.company_details')

                </div>



                <div class="tab-pane fade show" id="sales-tab" role="tabpanel">
                    @lang('menuItemLang.sales')

                </div>
                <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                    @lang('menuItemLang.purchases')
                </div>
                <div class="tab-pane fade" id="enabledModules-tab" role="tabpanel">
                    @include('general::general-setting.enabledModules')
                </div>



                <x-general::taxes.tax-index :taxesColumns=$taxesColumns />
                <x-general::paymentMethods.payment-method-index :methodColumns=$methodColumns />


            </div>
        </div>


    </div>
    @include('general::tax.create')
    @include('general::tax.edit')
    @include('general::payment-methods.create')
</div>
