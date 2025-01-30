<div class="tab-pane fade show" id="invoice_settings_tab" role="tabpanel">
    <div class="container">


        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 active" data-bs-toggle="tab" href="#general-tab">
                        @lang('general::general.general_settings')
                    </a>
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
            </ul>

            <div class="tab-content w-100" id="invoice-tab">
                <div class="tab-pane fade show active" id="general-tab" role="tabpanel">
                    @include('general::invoice-setting.general-invoice-setting.general_settings')

                </div>
                <div class="tab-pane fade show" id="sales-tab" role="tabpanel">
                    @lang('menuItemLang.sales')

                </div>
                <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                    @lang('menuItemLang.purchases')
                </div>
            </div>
        </div>


    </div>

</div>
