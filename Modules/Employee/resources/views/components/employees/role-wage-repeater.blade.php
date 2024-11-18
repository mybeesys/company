@props(['employee' => null, 'roles', 'establishments', 'disabled' => false])
<label @class(['form-label'])>@lang('employee::fields.pos_roles_wages')</label>
<div id="role_wage_repeater">
    <div class="form-group">
        <div data-repeater-list="role_wage_repeater" class="d-flex flex-column gap-3">
            @php
                $wageTypes = [
                    ['id' => 'hourly', 'name' => __('employee::general.hourly')],
                    ['id' => 'monthly', 'name' => __('employee::general.monthly')],
                    ['id' => 'fixed', 'name' => __('employee::general.fixed')]
                ];
            @endphp
            {{-- Handling global roles and wages --}}
            @if (($employee?->roles->isNotEmpty() || !$employee) || !$employee?->establishmentsPivot()?->exists())
                @foreach (old('role_wage_repeater', $employee?->roles->isEmpty() ? [null] : $employee?->roles ?? [null]) as $index => $globalRole)
                    <x-employee::employees.role-wage-repeater-inputs :index=$index :disabled=$disabled
                        role_select_value="{{ is_array($globalRole) ? $globalRole['role'] ?? '' : $globalRole?->id }}"
                        establishment_select_value="all" default_selection_value="all"
                        default_selection="{{ __('employee::general.all_establishments') }}"
                        wage_value="{{ is_array($globalRole) ? $globalRole['wage'] ?? '' : $globalRole?->wage?->rate }}"
                        wageType_value="{{ is_array($globalRole) ? $globalRole['wageType'] ?? '' : $globalRole?->wage?->wageType }}"
                        :wageTypes=$wageTypes :roles=$roles :establishments=$establishments />
                @endforeach
            @endif

            {{-- Establishments roles and wages --}}
            @if ($employee?->establishmentsPivot()?->exists())
                @foreach (old('role_wage_repeater', $employee?->establishmentsPivot) as $index => $establishmentRole)
                    <x-employee::employees.role-wage-repeater-inputs :index=$index :disabled=$disabled
                        role_select_value="{{ is_array($establishmentRole) ? $establishmentRole['role'] ?? '' : $establishmentRole?->role_id }}"
                        default_selection="{{ __('employee::general.all_establishments') }}"
                        default_selection_value="all"
                        establishment_select_value="{{ is_array($establishmentRole) ? $establishmentRole['establishment'] ?? '' : $establishmentRole?->establishment_id }}"
                        wage_value="{{ is_array($establishmentRole) ? $establishmentRole['wage'] ?? '' : $establishmentRole?->wage?->rate }}"
                        wageType_value="{{ is_array($establishmentRole) ? $establishmentRole['wageType'] ?? '' : $establishmentRole?->wage?->wageType }}"
                        :wageTypes=$wageTypes :roles=$roles :establishments=$establishments />
                @endforeach
            @endif
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_roles')</button>
    </div>
</div>
