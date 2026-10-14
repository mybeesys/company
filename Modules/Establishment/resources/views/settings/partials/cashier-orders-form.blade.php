@php
    $locale = app()->getLocale();
    $letters = range('A', 'Z');
@endphp

<div class="d-flex flex-column gap-5">
    <x-form.form-card :title="$t('presets')" bodyClass="d-flex flex-column gap-4">
        <div class="cashier-order-options cashier-order-options-3" data-preset-cards>
            @foreach (['retail', 'qsr', 'multi_pos'] as $preset)
                <button type="button" class="cashier-order-option {{ ($s['preset'] ?? '') === $preset ? 'is-active' : '' }}" data-apply-preset="{{ $preset }}">
                    <span class="cashier-order-option-title">{{ $t('preset_'.$preset) }}</span>
                    <span class="cashier-order-option-hint">{{ $t('preset_'.$preset.'_hint') }}</span>
                </button>
            @endforeach
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('identity')" bodyClass="d-flex flex-column gap-6">
        <p class="text-muted fs-7 mb-0">{{ $t('identity_hint') }}</p>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" @checked($s['enabled'])>
            <label class="form-check-label fw-semibold">{{ $t('enabled') }}</label>
        </div>
        <div class="text-muted fs-8">{{ $t('enabled_hint') }}</div>

        <div>
            <div class="fw-bold mb-3">{{ $t('scope') }}</div>
            <div class="cashier-order-options cashier-order-options-2">
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'scope', 'value' => 'company', 'current' => $s['scope'], 'title' => $t('scope_company'), 'hint' => $t('scope_company_hint')])
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'scope', 'value' => 'branch', 'current' => $s['scope'], 'title' => $t('scope_branch'), 'hint' => $t('scope_branch_hint')])
            </div>
        </div>

        <div>
            <div class="fw-bold mb-3">{{ $t('system_id_mode') }}</div>
            <div class="cashier-order-options cashier-order-options-2">
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'system_id_mode', 'value' => 'dated_serial', 'current' => $s['system_id_mode'], 'title' => $t('system_id_dated'), 'hint' => $t('system_id_dated_hint')])
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'system_id_mode', 'value' => 'internal', 'current' => $s['system_id_mode'], 'title' => $t('system_id_internal'), 'hint' => $t('system_id_internal_hint')])
            </div>
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('reset')" bodyClass="d-flex flex-column gap-6">
        <p class="text-muted fs-7 mb-0">{{ $t('reset_hint') }}</p>
        <div class="cashier-order-options cashier-order-options-3">
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'reset_policy', 'value' => 'daily', 'current' => $s['reset_policy'], 'title' => $t('reset_daily'), 'hint' => $t('reset_daily_hint')])
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'reset_policy', 'value' => 'shift', 'current' => $s['reset_policy'], 'title' => $t('reset_shift'), 'hint' => $t('reset_shift_hint')])
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'reset_policy', 'value' => 'never', 'current' => $s['reset_policy'], 'title' => $t('reset_never'), 'hint' => $t('reset_never_hint')])
        </div>
        <div class="row g-4" data-depends="reset_policy" data-show-when="shift">
            <div class="col-md-8">
                <label class="form-label fw-semibold">{{ $t('shift_source') }}</label>
                <select name="shift_source" class="form-select form-select-solid">
                    <option value="register_shift" @selected($s['shift_source'] === 'register_shift')>{{ $t('shift_register') }}</option>
                    <option value="employee_schedule" @selected($s['shift_source'] === 'employee_schedule')>{{ $t('shift_schedule') }}</option>
                </select>
            </div>
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('sequence')" bodyClass="d-flex flex-column gap-6">
        <div class="row g-4">
            <div class="col-sm-4">
                <label class="form-label fw-semibold">{{ $t('start_number') }}</label>
                <input type="number" min="0" max="999999" name="start_number" class="form-control form-control-solid" value="{{ $s['start_number'] }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label fw-semibold">{{ $t('padding') }}</label>
                <input type="number" min="1" max="8" name="padding" class="form-control form-control-solid" value="{{ $s['padding'] }}">
                <div class="form-text">{{ $t('padding_hint') }}</div>
            </div>
            <div class="col-sm-4">
                <label class="form-label fw-semibold">{{ $t('separator') }}</label>
                <select name="separator" class="form-select form-select-solid">
                    <option value="-" @selected($s['separator'] === '-')>-</option>
                    <option value="/" @selected($s['separator'] === '/')>/</option>
                    <option value="." @selected($s['separator'] === '.')>.</option>
                    <option value=" " @selected($s['separator'] === ' ')>&nbsp;</option>
                    <option value="" @selected($s['separator'] === '')>—</option>
                </select>
            </div>
        </div>

        <div class="separator"></div>
        <div class="fw-bold">{{ $t('cycle') }}</div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="cycle_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="cycle_enabled" value="1" @checked($s['cycle_enabled']) data-toggle-cycle>
            <label class="form-check-label fw-semibold">{{ $t('cycle_enabled') }}</label>
        </div>
        <div class="text-muted fs-8">{{ $t('cycle_enabled_hint') }}</div>
        <div class="row g-4" data-cycle-fields>
            <div class="col-sm-4">
                <label class="form-label fw-semibold">{{ $t('cycle_max') }}</label>
                <input type="number" min="1" max="999999" name="cycle_max" class="form-control form-control-solid" value="{{ $s['cycle_max'] }}">
            </div>
            <div class="col-sm-8 d-flex align-items-end">
                <div class="form-check form-check-custom form-check-solid">
                    <input type="hidden" name="cycle_warn_on_reuse" value="0">
                    <input class="form-check-input" type="checkbox" name="cycle_warn_on_reuse" value="1" @checked($s['cycle_warn_on_reuse'])>
                    <label class="form-check-label">{{ $t('cycle_warn') }}</label>
                </div>
            </div>
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="void_recycles_number" value="0">
            <input class="form-check-input" type="checkbox" name="void_recycles_number" value="1" @checked($s['void_recycles_number'])>
            <label class="form-check-label fw-semibold">{{ $t('void_recycle') }}</label>
        </div>
        <div class="text-muted fs-8">{{ $t('void_recycle_hint') }}</div>
    </x-form.form-card>

    <x-form.form-card :title="$t('allocation')" bodyClass="d-flex flex-column gap-6">
        <p class="text-muted fs-7 mb-0">{{ $t('allocation_hint') }}</p>
        <div class="cashier-order-options cashier-order-options-3">
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'allocation', 'value' => 'central', 'current' => $s['allocation'], 'title' => $t('allocation_central'), 'hint' => $t('allocation_central_hint')])
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'allocation', 'value' => 'prefix', 'current' => $s['allocation'], 'title' => $t('allocation_prefix'), 'hint' => $t('allocation_prefix_hint')])
            @include('establishment::settings.partials.cashier-orders-option', ['name' => 'allocation', 'value' => 'range', 'current' => $s['allocation'], 'title' => $t('allocation_range'), 'hint' => $t('allocation_range_hint')])
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ $t('prefix_style') }}</label>
                <select name="prefix_style" class="form-select form-select-solid">
                    <option value="letter" @selected($s['prefix_style'] === 'letter')>{{ $t('prefix_letter') }}</option>
                    <option value="numeric" @selected($s['prefix_style'] === 'numeric')>{{ $t('prefix_numeric') }}</option>
                    <option value="ref" @selected($s['prefix_style'] === 'ref')>{{ $t('prefix_ref') }}</option>
                    <option value="custom" @selected($s['prefix_style'] === 'custom')>{{ $t('prefix_custom') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ $t('global_prefix') }}</label>
                <input type="text" name="global_prefix" maxlength="8" class="form-control form-control-solid" value="{{ $s['global_prefix'] }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input type="hidden" name="show_terminal_in_display" value="0">
                    <input class="form-check-input" type="checkbox" name="show_terminal_in_display" value="1" @checked($s['show_terminal_in_display'])>
                    <label class="form-check-label fw-semibold">{{ $t('show_terminal') }}</label>
                </div>
            </div>
        </div>
        <div>
            <label class="form-label fw-semibold">{{ $t('format_pattern') }}</label>
            <div class="d-flex flex-wrap gap-2 mb-3" data-token-bar>
                @foreach (['{prefix}', '{dining}', '{padded}', '{seq}', '{date}', '{shift}', '{separator}'] as $token)
                    <button type="button" class="btn btn-sm btn-light-primary" data-insert-token="{{ $token }}">{{ $t('insert_token') }} {{ $token }}</button>
                @endforeach
            </div>
            <input type="text" name="format_pattern" maxlength="80" class="form-control form-control-solid" value="{{ $s['format_pattern'] }}" dir="ltr">
            <div class="form-text">{{ $t('format_pattern_hint') }}</div>
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('surfaces')" bodyClass="d-flex flex-column gap-5">
        <div class="row g-4">
            @foreach (['show_on_cashier' => 'show_cashier', 'show_on_receipt' => 'show_receipt', 'show_on_kds' => 'show_kds', 'show_on_queue_screen' => 'show_queue'] as $field => $label)
                <div class="col-md-6">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" @checked($s[$field])>
                        <label class="form-check-label fw-semibold">{{ $t($label) }}</label>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="kitchen_same_as_display" value="0">
            <input class="form-check-input" type="checkbox" name="kitchen_same_as_display" value="1" @checked($s['kitchen_same_as_display'])>
            <label class="form-check-label fw-semibold">{{ $t('kitchen_same') }}</label>
        </div>
        <div class="text-muted fs-8">{{ $t('kitchen_same_hint') }}</div>
        <div class="w-md-300px">
            <label class="form-label fw-semibold">{{ $t('display_size') }}</label>
            <select name="display_size" class="form-select form-select-solid">
                <option value="normal" @selected($s['display_size'] === 'normal')>{{ $t('size_normal') }}</option>
                <option value="large" @selected($s['display_size'] === 'large')>{{ $t('size_large') }}</option>
                <option value="xlarge" @selected($s['display_size'] === 'xlarge')>{{ $t('size_xlarge') }}</option>
            </select>
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('search')" bodyClass="d-flex flex-column gap-5">
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="search_by_display" value="0">
            <input class="form-check-input" type="checkbox" name="search_by_display" value="1" @checked($s['search_by_display'])>
            <label class="form-check-label fw-semibold">{{ $t('search_by_display') }}</label>
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="search_requires_date_when_cycled" value="0">
            <input class="form-check-input" type="checkbox" name="search_requires_date_when_cycled" value="1" @checked($s['search_requires_date_when_cycled'])>
            <label class="form-check-label fw-semibold">{{ $t('search_date_when_cycled') }}</label>
        </div>
        <div>
            <div class="fw-bold mb-3">{{ $t('offline') }}</div>
            <div class="cashier-order-options cashier-order-options-3">
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'offline_policy', 'value' => 'terminal_prefix', 'current' => $s['offline_policy'], 'title' => $t('offline_prefix'), 'hint' => ''])
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'offline_policy', 'value' => 'local_temp', 'current' => $s['offline_policy'], 'title' => $t('offline_temp'), 'hint' => ''])
                @include('establishment::settings.partials.cashier-orders-option', ['name' => 'offline_policy', 'value' => 'refuse', 'current' => $s['offline_policy'], 'title' => $t('offline_refuse'), 'hint' => ''])
            </div>
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('dining')" bodyClass="d-flex flex-column gap-5">
        <p class="text-muted fs-7 mb-0">{{ $t('dining_hint') }}</p>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input type="hidden" name="apply_dining_prefixes" value="0">
            <input class="form-check-input" type="checkbox" name="apply_dining_prefixes" value="1" @checked($s['apply_dining_prefixes']) data-toggle-dining>
            <label class="form-check-label fw-semibold">{{ $t('apply_dining') }}</label>
        </div>
        <div data-dining-fields>
            @if ($diningTypes->isEmpty())
                <div class="text-muted fs-7">{{ $t('dining_none') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gs-0 gy-3 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>{{ $t('dining') }}</th>
                                <th class="w-150px">{{ $t('dining_prefix') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($diningTypes as $type)
                                <tr>
                                    <td>{{ $locale === 'ar' ? $type->name_ar : ($type->name_en ?: $type->name_ar) }}</td>
                                    <td>
                                        <input type="text" maxlength="8" class="form-control form-control-solid form-control-sm" dir="ltr"
                                            name="dining_type_prefixes[{{ $type->id }}]"
                                            value="{{ $s['dining_type_prefixes'][(string) $type->id] ?? $s['dining_type_prefixes'][$type->id] ?? '' }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-form.form-card>

    <x-form.form-card :title="$t('terminals')" bodyClass="d-flex flex-column gap-5">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <p class="text-muted fs-7 mb-0">{{ $t('terminals_hint') }}</p>
            <a href="{{ url('/devices') }}" class="btn btn-sm btn-light-primary">{{ $t('open_devices') }}</a>
        </div>
        @if ($cashierDevices->isEmpty())
            <div class="text-muted fs-7">{{ $t('terminals_empty') }}</div>
        @else
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gs-0 gy-3 mb-0" data-terminal-table>
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>{{ $t('device_enabled') }}</th>
                            <th>{{ $t('device_name') }}</th>
                            <th>{{ $t('device_branch') }}</th>
                            <th>{{ $t('device_ref') }}</th>
                            <th class="w-120px">{{ $t('device_prefix') }}</th>
                            <th class="w-100px" data-range-cols>{{ $t('range_from') }}</th>
                            <th class="w-100px" data-range-cols>{{ $t('range_to') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cashierDevices as $index => $device)
                            @php
                                $tid = (string) $device->id;
                                $row = $s['terminals'][$tid] ?? ['enabled' => true, 'prefix' => $letters[$index] ?? (string) ($index + 1), 'range_from' => ($index * 500) + 1, 'range_to' => ($index + 1) * 500];
                                $branch = $device->establishment;
                                $branchName = $branch ? ($locale === 'ar' ? $branch->name : ($branch->name_en ?: $branch->name)) : '—';
                            @endphp
                            <tr>
                                <td>
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="hidden" name="terminals[{{ $device->id }}][enabled]" value="0">
                                        <input class="form-check-input" type="checkbox" name="terminals[{{ $device->id }}][enabled]" value="1" @checked($row['enabled'] ?? true)>
                                    </div>
                                </td>
                                <td class="fw-semibold">{{ $device->name }}</td>
                                <td>{{ $branchName }}</td>
                                <td dir="ltr">{{ $device->ref }}</td>
                                <td>
                                    <input type="text" maxlength="8" class="form-control form-control-solid form-control-sm" dir="ltr"
                                        name="terminals[{{ $device->id }}][prefix]" value="{{ $row['prefix'] ?? '' }}">
                                </td>
                                <td data-range-cols>
                                    <input type="number" class="form-control form-control-solid form-control-sm" name="terminals[{{ $device->id }}][range_from]" value="{{ $row['range_from'] ?? 1 }}">
                                </td>
                                <td data-range-cols>
                                    <input type="number" class="form-control form-control-solid form-control-sm" name="terminals[{{ $device->id }}][range_to]" value="{{ $row['range_to'] ?? 500 }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-form.form-card>
</div>
