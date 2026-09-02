@php
    $firstH = \Modules\General\Support\SettingAccess::firstHorizontal();
    $v = \Modules\General\Support\SettingAccess::verticalFlags();
    $firstV = \Modules\General\Support\SettingAccess::firstVertical();
@endphp
<div class="tab-pane fade {{ $firstH === 'general' ? 'show active' : '' }}" id="general_setting_tab" role="tabpanel">
    <div class="">


        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                @if ($v['company'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'company' ? 'active' : '' }}" data-bs-toggle="tab" href="#company_details-tab">
                        @lang('establishment::general.company_details')
                    </a>
                </li>
                @endif
                @if ($v['general'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'general' ? 'active' : '' }}" data-bs-toggle="tab" href="#general-tab">
                        @lang('general::general.general_settings')
                    </a>
                </li>
                @endif

                @if ($v['taxes'])
                <li class="nav-item w-md-200px me-0 py-1 nav-link-taxes">
                    <a class="nav-link py-3 {{ $firstV === 'taxes' ? 'active' : '' }}" data-bs-toggle="tab" href="#taxes_tab">@lang('menuItemLang.taxes')</a>
                </li>
                @endif
                @if ($v['policy'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'policy' ? 'active' : '' }}" data-bs-toggle="tab" href="#inventory_policy_tab">@lang('general::lang.inventory_tracking_policy')</a>
                </li>
                @endif

                @if ($v['sales'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'sales' ? 'active' : '' }}" data-bs-toggle="tab" href="#sales-tab">
                        @lang('menuItemLang.sales')
                    </a>
                </li>
                @endif
                @if ($v['purchases'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'purchases' ? 'active' : '' }}" data-bs-toggle="tab" href="#purchases-tab">
                        @lang('menuItemLang.purchases')
                    </a>
                </li>
                @endif
                @if ($v['modules'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'modules' ? 'active' : '' }}" data-bs-toggle="tab" href="#enabledModules-tab">
                        @lang('general::general.Module Management')
                    </a>
                </li>
                @endif
                @if ($v['unit'])
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'unit' ? 'active' : '' }}" data-bs-toggle="tab" href="#defaultUnit-tab">
                        @lang('general::general.Default Unit')
                    </a>
                </li>
                @endif

                 @if ($v['rewards'])
                 <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 {{ $firstV === 'rewards' ? 'active' : '' }}" data-bs-toggle="tab" href="#reward_points_tab">
                        @lang('general::lang.reward_points_tab')
                    </a>
                </li>
                @endif


            </ul>

            <div class="tab-content w-100" id="invoice-tab">
                @if ($v['general'])
                <div class="tab-pane fade {{ $firstV === 'general' ? 'show active' : '' }}" id="general-tab" role="tabpanel">
                    @include('general::general-setting.general_settings')

                </div>
                @endif
                @if ($v['company'])
                <div class="tab-pane fade {{ $firstV === 'company' ? 'show active' : '' }}" id="company_details-tab" role="tabpanel">
                    @include('general::general-setting.company_details')

                </div>
                @endif



                @if ($v['sales'])
                <div class="tab-pane fade {{ $firstV === 'sales' ? 'show active' : '' }}" id="sales-tab" role="tabpanel">
                    <div class="card card-flush border-0 shadow-sm">
                        <div class="card-body">
                            <div class="form-check form-switch d-flex align-items-center gap-3">
                                <input class="form-check-input" type="checkbox" id="toggleCouponGeneralSales" @disabled(! \Modules\General\Support\SettingAccess::canTab('invoice', 'update'))>
                                <label class="form-check-label fw-semibold" for="toggleCouponGeneralSales">
                                    @lang('sales::lang.toggleCoupon')
                                </label>
                            </div>
                            <div class="text-muted fs-7 mt-2 mb-6">
                                @lang('general::general.invoice_settings')
                            </div>

                            <div class="separator separator-dashed my-5"></div>

                            <div class="form-check form-switch d-flex align-items-center gap-3">
                                <input class="form-check-input" type="checkbox" id="toggleSellWithModifiersCombos" @disabled(! \Modules\General\Support\SettingAccess::canTab('invoice', 'update'))>
                                <label class="form-check-label fw-semibold" for="toggleSellWithModifiersCombos">
                                    @lang('sales::lang.toggleSellWithModifiersCombos')
                                </label>
                            </div>
                            <div class="text-muted fs-7 mt-2">
                                @lang('sales::lang.toggleSellWithModifiersCombos_hint')
                            </div>
                        </div>
                    </div>

                </div>
                @endif
                @if ($v['purchases'])
                <div class="tab-pane fade {{ $firstV === 'purchases' ? 'show active' : '' }}" id="purchases-tab" role="tabpanel">
                    @lang('menuItemLang.purchases')
                </div>
                @endif
                @if ($v['modules'])
                <div class="tab-pane fade {{ $firstV === 'modules' ? 'show active' : '' }}" id="enabledModules-tab" role="tabpanel">
                    @include('general::general-setting.enabledModules')
                </div>
                @endif

                @if ($v['policy'])
                <div class="tab-pane fade {{ $firstV === 'policy' ? 'show active' : '' }}" id="inventory_policy_tab" role="tabpanel">
                    @include('general::general-setting.inventory_policy_tab')
                </div>
                @endif

                @if ($v['unit'])
                <div class="tab-pane fade {{ $firstV === 'unit' ? 'show active' : '' }}" id="defaultUnit-tab" role="tabpanel">
                    @include('general::general-setting.default_unit-tab')
                </div>
                @endif
                @if ($v['rewards'])
                <div class="tab-pane fade {{ $firstV === 'rewards' ? 'show active' : '' }}" id="reward_points_tab" role="tabpanel">
                    @include('general::general-setting.reward_points')
                </div>
                @endif

                @if ($v['taxes'])
                <x-general::taxes.tax-index :taxesColumns=$taxesColumns />
                @endif
                {{-- Temporarily hidden: payment methods content + modals (page UI only) --}}
                {{-- <x-general::paymentMethods.payment-method-index :methodColumns=$methodColumns /> --}}


            </div>
        </div>


    </div>
    @if ($v['taxes'])
    @include('general::tax.create')
    @include('general::tax.edit')
    @endif
    {{-- @include('general::payment-methods.create') --}}
    {{-- @include('general::payment-methods.edit') --}}
</div>

<script>
    $(document).ready(function() {
        const $toggleCoupon = $('#toggleCouponGeneralSales');
        const $toggleModsCombos = $('#toggleSellWithModifiersCombos');
        if (!$toggleCoupon.length && !$toggleModsCombos.length) {
            return;
        }

        $.ajax({
            url: "{{ route('invoice-settings-get') }}",
            type: "GET",
            success: function(response) {
                if (response.success) {
                    if ($toggleCoupon.length) {
                        $toggleCoupon.prop('checked', !!response.data.coupon);
                    }
                    if ($toggleModsCombos.length) {
                        $toggleModsCombos.prop('checked', !!response.data.sell_with_modifiers_combos);
                    }
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

        $toggleModsCombos.on('change', function() {
            $.ajax({
                url: "{{ route('invoice-settings-update') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    key: "toggleSellWithModifiersCombos",
                    value: $(this).is(':checked') ? 1 : 0
                }
            });
        });
    });
</script>
