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
                                @lang('general::general.perpetual_policy_current_note')
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                @lang('general::general.periodic_policy_current_note')
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row mt-4" id="perpetual_permission_note" style="display: {{ ($policy ?? 'perpetual') == 'perpetual' ? 'block' : 'none' }};">
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">
                            <div class="fw-semibold mb-1">@lang('general::general.perpetual_stock_bypass_heading')</div>
                            <p class="mb-0 small text-muted">@lang('general::general.perpetual_stock_bypass_note')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @dashboardcan(\Modules\General\Support\SettingPermissions::for('inventory policy', 'update'))
        <button type="submit" class="btn btn-primary w-200px" style="border-radius: 6px;">
            @lang('messages.save')
        </button>
        @enddashboardcan
    </form>
</div>
