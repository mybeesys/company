<div class="tab-pane fade show" id="reward_points_tab" role="tabpanel">
    <div class="container">
        <form id="update-reward-points" method="POST" action="{{ route('update-reward-points') }}">
            @csrf

            <div class="row my-5">
                <div class="col-4 mb-5 d-flex align-items-center">
                    <label>
                        <input type="checkbox" name="enable_rp" value="1"
                            {{ ($reward_points_settings['enable_rp'] ?? false) == true ? 'checked' : '' }}>
                        @lang('general::lang.enable_rp')
                    </label>
                </div>
            </div>

            <div class="row" id="rp_settings"
                style="display: {{ ($reward_points_settings['enable_rp'] ?? false) == true ? 'flex' : 'none' }};">
                <div class="col-4 mb-5">
                    <label>@lang('general::lang.rp_name')</label>
                    <input type="text" name="rp_name" class="form-control" placeholder="@lang('general::lang.rp_name')"
                        value="{{ $reward_points_settings['rp_name'] ?? '' }}">
                </div>

                <div class="col-12">
                    <h4>@lang('general::lang.earning_points_setting')</h4>
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.amount_for_unit_rp')</label>
                    <input type="number" step="0.01" name="amount_for_unit_rp" class="form-control"
                        value="{{ $reward_points_settings['amount_for_unit_rp'] ?? '' }}">
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.min_order_total_for_rp')</label>
                    <input type="number" step="0.01" name="min_order_total_for_rp" class="form-control"
                        value="{{ $reward_points_settings['min_order_total_for_rp'] ?? '' }}">
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.max_rp_per_order')</label>
                    <input type="number" name="max_rp_per_order" class="form-control"
                        value="{{ $reward_points_settings['max_rp_per_order'] ?? '' }}">
                </div>

                <div class="col-12">
                    <h4>@lang('general::lang.redeem_points_setting')</h4>
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.redeem_amount_per_unit_rp')</label>
                    <input type="number" step="0.01" name="redeem_amount_per_unit_rp" class="form-control"
                        value="{{ $reward_points_settings['redeem_amount_per_unit_rp'] ?? '' }}">
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.min_order_total_for_redeem')</label>
                    <input type="number" step="0.01" name="min_order_total_for_redeem" class="form-control"
                        value="{{ $reward_points_settings['min_order_total_for_redeem'] ?? '' }}">
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.min_redeem_point')</label>
                    <input type="number" name="min_redeem_point" class="form-control"
                        value="{{ $reward_points_settings['min_redeem_point'] ?? '' }}">
                </div>

                <div class="col-4 mb-5">
                    <label>@lang('general::lang.max_redeem_point')</label>
                    <input type="number" name="max_redeem_point" class="form-control"
                        value="{{ $reward_points_settings['max_redeem_point'] ?? '' }}">
                </div>

                <div class="col-6 mb-5">
                    <label>@lang('general::lang.rp_expiry_period')</label>
                    <div class="input-group">
                        <input type="number" name="rp_expiry_period" class="form-control"
                            value="{{ $reward_points_settings['rp_expiry_period'] ?? '' }}">
                        <span class="input-group-text">-</span>
                        <select name="rp_expiry_type" class="form-select">
                            <option value="month"
                                {{ ($reward_points_settings['rp_expiry_type'] ?? 'month') == 'month' ? 'selected' : '' }}>
                                @lang('general::lang.month')
                            </option>
                            <option value="year"
                                {{ ($reward_points_settings['rp_expiry_type'] ?? 'month') == 'year' ? 'selected' : '' }}>
                                @lang('general::lang.year')
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="separator d-flex flex-center m-5"></div>

            @dashboardcan(\Modules\General\Support\SettingPermissions::for('reward points', 'update'))
            <button type="submit" class="btn btn-primary w-200px">@lang('messages.save')</button>
            @enddashboardcan
        </form>
    </div>
</div>
