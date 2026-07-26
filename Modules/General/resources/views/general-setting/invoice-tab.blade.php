<div class="tab-pane fade show active" id="general_setting_tab" role="tabpanel">
    <div class="">


        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                @if (tenant_setting_entitled('company_details'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3 active" data-bs-toggle="tab" href="#company_details-tab">
                            @lang('establishment::general.company_details')
                        </a>
                    </li>
                @endif
                @if (tenant_setting_entitled('general'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#general-tab">
                            @lang('general::general.general_settings')
                        </a>
                    </li>
                @endif

                @if (tenant_setting_entitled('taxes'))
                    <li class="nav-item w-md-200px me-0 py-1 nav-link-taxes">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#taxes_tab">@lang('menuItemLang.taxes')</a>
                    </li>
                @endif
                @if (tenant_setting_entitled('payment_methods'))
                    <li class="nav-item w-md-200px me-0 py-1 nav-link-methods">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#payemnt_methods_tab">@lang('general::lang.payment_methods')</a>
                    </li>
                @endif
                @if (tenant_setting_entitled('inventory_policy'))
                    <li class="nav-item w-md-200px me-0 py-1 nav-link-methods">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#inventory_policy_tab">@lang('general::lang.inventory_tracking_policy')</a>
                    </li>
                @endif

                @if (tenant_setting_entitled('sales'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#sales-tab">
                            @lang('menuItemLang.sales')
                        </a>
                    </li>
                @endif
                @if (tenant_setting_entitled('purchases'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#purchases-tab">
                            @lang('menuItemLang.purchases')
                        </a>
                    </li>
                @endif
                @if (tenant_setting_entitled('subscription_modules'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#enabledModules-tab">
                            @lang('general::general.subscription_modules')
                        </a>
                    </li>
                @endif
                @if (tenant_setting_entitled('default_unit'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#defaultUnit-tab">
                            @lang('general::general.Default Unit')
                        </a>
                    </li>
                @endif

                @if (tenant_setting_entitled('reward_points'))
                    <li class="nav-item w-md-200px me-0 py-1">
                        <a class="nav-link py-3" data-bs-toggle="tab" href="#reward_points_tab">
                            @lang('general::lang.reward_points_tab')
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content w-100" id="invoice-tab">
                @if (tenant_setting_entitled('general'))
                    <div class="tab-pane fade show" id="general-tab" role="tabpanel">
                        @include('general::general-setting.general_settings')
                    </div>
                @endif
                @if (tenant_setting_entitled('company_details'))
                    <div class="tab-pane fade show active" id="company_details-tab" role="tabpanel">
                        @include('general::general-setting.company_details')
                    </div>
                @endif

                @if (tenant_setting_entitled('sales'))
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
                @endif
                @if (tenant_setting_entitled('purchases'))
                    <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                        @lang('menuItemLang.purchases')
                    </div>
                @endif
                @if (tenant_setting_entitled('subscription_modules'))
                    <div class="tab-pane fade" id="enabledModules-tab" role="tabpanel">
                        @include('general::general-setting.enabledModules')
                    </div>
                @endif

                @if (tenant_setting_entitled('inventory_policy'))
                    <div class="tab-pane fade" id="inventory_policy_tab" role="tabpanel">
                        @include('general::general-setting.inventory_policy_tab')
                    </div>
                @endif

                @if (tenant_setting_entitled('default_unit'))
                    <div class="tab-pane fade" id="defaultUnit-tab" role="tabpanel">
                        @include('general::general-setting.default_unit-tab')
                    </div>
                @endif
                @if (tenant_setting_entitled('reward_points'))
                    <div class="tab-pane fade" id="reward_points_tab" role="tabpanel">
                        @include('general::general-setting.reward_points')
                    </div>
                @endif

                @if (tenant_setting_entitled('taxes'))
                    <x-general::taxes.tax-index :taxesColumns=$taxesColumns />
                @endif
                @if (tenant_setting_entitled('payment_methods'))
                    <x-general::paymentMethods.payment-method-index :methodColumns=$methodColumns />
                @endif
            </div>
        </div>


    </div>
    @if (tenant_setting_entitled('taxes'))
        @include('general::tax.create')
        @include('general::tax.edit')
    @endif
    @if (tenant_setting_entitled('payment_methods'))
        @include('general::payment-methods.create')
        @include('general::payment-methods.edit')
    @endif
</div>

@if (tenant_setting_entitled('sales'))
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
@endif
