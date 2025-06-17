<div class="tab-pane fade show" id="inventory_policy_tab" role="tabpanel">
    <div class="container">
        <form id="update-inventory-policy" method="POST" action="{{ route('update-inventory-policy') }}">

            @csrf

            <div class="row my-5">
                <div class="col-4 mb-5">
                    <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <label class="fs-6 fw-semibold mb-2">
                            @lang('general::general.inventory_tracking_policy')
                        </label>
                    </div>
                    <select name="inventory_tracking_policy" id="inventory_tracking_policy" class="form-select">
                        <option value="periodic" {{ ($policy ?? 'periodic') == 'periodic' ? 'selected' : '' }}>
                            @lang('general::general.periodic')
                        </option>
                        <option value="perpetual" {{ ($policy ?? 'periodic') == 'perpetual' ? 'selected' : '' }}>
                            @lang('general::general.perpetual')
                        </option>
                    </select>
                </div>
            </div>

            <div class="row" id="periodic_settings"
                style="display: {{ ($policy ?? 'periodic') == 'periodic' ? 'flex' : 'none' }};">
                <div class="col-4 mb-5">
                    <label>@lang('general::general.inventory_frequency')</label>
                    <select name="inventory_count_frequency" class="form-select">
                        <option value="daily"
                            {{ ($inventoryCountFrequency ?? 'monthly') == 'daily' ? 'selected' : '' }}>
                            @lang('general::general.daily')
                        </option>
                        <option value="weekly"
                            {{ ($inventoryCountFrequency ?? 'monthly') == 'weekly' ? 'selected' : '' }}>
                            @lang('general::general.weekly')
                        </option>
                        <option value="monthly"
                            {{ ($inventoryCountFrequency ?? 'monthly') == 'monthly' ? 'selected' : '' }}>
                            @lang('general::general.monthly')
                        </option>
                    </select>

                </div>
            </div>

            <div class="row" id="perpetual_settings"
                style="display: {{ ($policy ?? 'periodic') == 'perpetual' ? 'flex' : 'none' }};">
                <label>
                    <input type="checkbox" name="allow_sale_without_stock" value="1"
                        {{ $allowSaleWithoutStock =='true'? 'checked' : '' }}>
                    @lang('general::general.allow_sale_without_stock')
                </label>
            </div>

            <div class="separator d-flex flex-center m-5">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>

            <button type="submit" class="btn btn-primary w-200px" style="border-radius: 6px;">
                @lang('messages.save')
            </button>
        </form>
    </div>
</div>
