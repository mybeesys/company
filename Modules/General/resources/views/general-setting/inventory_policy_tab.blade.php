<div class="container-fluid px-0">
    <form id="update-inventory-policy" method="POST" action="{{ route('update-inventory-policy') }}">
        @csrf

        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body">
                <div class="fw-bold fs-5 mb-2">@lang('general::general.inventory_tracking_policy')</div>
                <div class="text-muted mb-5">@lang('general::general.inventory_policy_help_text')</div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">@lang('general::general.inventory_tracking_policy')</label>
                        <select name="inventory_tracking_policy" id="inventory_tracking_policy" class="form-select">
                            <option value="perpetual" {{ ($policy ?? 'perpetual') == 'perpetual' ? 'selected' : '' }}>
                                @lang('general::general.perpetual')
                            </option>
                            <option value="periodic" {{ ($policy ?? 'perpetual') == 'periodic' ? 'selected' : '' }}>
                                @lang('general::general.periodic')
                            </option>
                        </select>
                        <div class="form-text">@lang('general::general.inventory_policy_default_note')</div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="p-4 rounded border bg-light-primary">
                            <div class="fw-bold mb-2">@lang('general::general.perpetual')</div>
                            <ul class="mb-0 ps-4">
                                <li>@lang('general::general.perpetual_policy_point_1')</li>
                                <li>@lang('general::general.perpetual_policy_point_2')</li>
                                <li>@lang('general::general.perpetual_policy_point_3')</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-4 rounded border bg-light-warning">
                            <div class="fw-bold mb-2">@lang('general::general.periodic')</div>
                            <ul class="mb-0 ps-4">
                                <li>@lang('general::general.periodic_policy_point_1')</li>
                                <li>@lang('general::general.periodic_policy_point_2')</li>
                                <li>@lang('general::general.periodic_policy_point_3')</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        @if (($policy ?? 'perpetual') === 'perpetual')
                            <div class="alert alert-info mb-0">
                                {{ app()->getLocale() === 'ar'
                                    ? 'السياسة الحالية: الجرد المستمر. يتم التحقق من الرصيد لحظياً (حسب إعداد السماح بالبيع بدون مخزون) مع ترحيل الأثر المخزني والمالي مباشرة.'
                                    : 'Current policy: Perpetual inventory. Stock is validated in real-time (based on allow sale without stock setting), with immediate inventory and financial posting.' }}
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                {{ app()->getLocale() === 'ar'
                                    ? 'السياسة الحالية: الجرد الدوري. يعتمد تقييم المخزون وتكلفة المبيعات على جرد الفترة والتسويات عند الإقفال.'
                                    : 'Current policy: Periodic inventory. Inventory valuation and COGS rely on period count and closing adjustments.' }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row mt-5" id="perpetual_settings" style="display: {{ ($policy ?? 'perpetual') == 'perpetual' ? 'flex' : 'none' }};">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="allow_sale_without_stock_switch" name="allow_sale_without_stock" value="1" {{ $allowSaleWithoutStock == 'true' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="allow_sale_without_stock_switch">
                                @lang('general::general.allow_sale_without_stock')
                            </label>
                        </div>
                        <div class="form-text">@lang('general::general.allow_sale_without_stock_note')</div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-200px" style="border-radius: 6px;">
            @lang('messages.save')
        </button>
    </form>
</div>
