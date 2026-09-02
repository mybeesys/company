@php $firstH = \Modules\General\Support\SettingAccess::firstHorizontal(); @endphp
<div class="tab-pane fade {{ $firstH === 'invoice' ? 'show active' : '' }}" id="invoice_settings_tab" role="tabpanel">
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
                    <div class="card card-flush border-0 shadow-sm">
                        <div class="card-body">
                            <div class="form-check form-switch d-flex align-items-center gap-3">
                                <input class="form-check-input" type="checkbox" id="toggleCouponInvoiceSales" @disabled(! \Modules\General\Support\SettingAccess::canTab('invoice', 'update'))>
                                <label class="form-check-label fw-semibold" for="toggleCouponInvoiceSales">
                                    @lang('sales::lang.toggleCoupon')
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                    @lang('menuItemLang.purchases')
                </div>
            </div>
        </div>


    </div>

</div>

<script>
    $(document).ready(function() {
        const $toggleCoupon = $('#toggleCouponInvoiceSales');
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
