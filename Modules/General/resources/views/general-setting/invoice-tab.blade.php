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
                <li class="nav-item w-md-200px me-0 py-1 nav-link-methods">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#inventory_policy_tab">@lang('general::lang.inventory_tracking_policy')</a>
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
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#defaultUnit-tab">
                        @lang('general::general.Default Unit')
                    </a>
                </li>

                 <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#reward_points_tab">
                        @lang('general::lang.reward_points_tab')
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
                    <div class="card card-flush border-0 shadow-sm">
                        <div class="card-body">
                            <div class="form-check form-switch d-flex align-items-center gap-3">
                                <input class="form-check-input" type="checkbox" id="toggleCouponGeneralSales">
                                <label class="form-check-label fw-semibold" for="toggleCouponGeneralSales">
                                    @lang('sales::lang.toggleCoupon')
                                </label>
                            </div>
                            <div class="text-muted fs-7 mt-2">
                                @lang('general::general.invoice_settings')
                            </div>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                    @lang('menuItemLang.purchases')
                </div>
                <div class="tab-pane fade" id="enabledModules-tab" role="tabpanel">
                    @include('general::general-setting.enabledModules')
                </div>

                <div class="tab-pane fade" id="inventory_policy_tab" role="tabpanel">
                    @include('general::general-setting.inventory_policy_tab')
                </div>

                <div class="tab-pane fade" id="defaultUnit-tab" role="tabpanel">
                    @include('general::general-setting.default_unit-tab')
                </div>
                <div class="tab-pane fade" id="reward_points_tab" role="tabpanel">
                    @include('general::general-setting.reward_points')
                </div>

                <x-general::taxes.tax-index :taxesColumns=$taxesColumns />
                <x-general::paymentMethods.payment-method-index :methodColumns=$methodColumns />


            </div>
        </div>


    </div>
    @include('general::tax.create')
    @include('general::tax.edit')
    @include('general::payment-methods.create')
    @include('general::payment-methods.edit')
</div>

<script>
    $(document).ready(function() {
        const $toggleCoupon = $('#toggleCouponGeneralSales');
        if (!$toggleCoupon.length) {
            return;
        }

        $.ajax({
            url: "{{ route('invoice-settings-get') }}",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    $toggleCoupon.prop('checked', !!response.data.coupon);
                }
            }
        });

        $toggleCoupon.on('change', function() {
            $.ajax({
                url: "{{ route('invoice-settings-update') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    key: "toggleCoupon",
                    value: $(this).is(':checked') ? 1 : 0
                }
            });
        });
    });
</script>
