@props(['employee' => null, 'roles', 'establishments'])
<label @class(['form-label'])>@lang('employee::fields.pos_roles_wages')</label>
<div id="role_wage_repeater">
    <div class="form-group">
        <div data-repeater-list="role_wage_repeater" class="d-flex flex-column gap-3">
            @foreach (old('role_wage_repeater', $employee?->roles ?? [null]) as $index => $roleWage)
                <x-employee::employees.role-wage-repeater-inputs :index=$index
                    role_select_value="{{ is_array($roleWage) ? $roleWage['role'] ?? '' : $roleWage?->id }}"
                    establishment_select_value="all" default_selection_value="all"
                    default_selection="{{ __('employee::general.all_establishments') }}"
                    wage_value="{{ is_array($roleWage) ? $roleWage['wage'] ?? '' : $roleWage?->wages?->first()?->rate }}"
                    :roles=$roles :establishments=$establishments />
            @endforeach

            @if ($employee?->establishmentsPivot()?->exists())
                @foreach (old('role_wage_repeater', $employee?->establishmentsPivot ?? [null]) as $index => $roleWage)
                    <x-employee::employees.role-wage-repeater-inputs :index=$index
                        role_select_value="{{ is_array($roleWage) ? $roleWage['role'] ?? '' : $roleWage?->role_id }}"
                        default_selection="{{ __('employee::general.all_establishments') }}"
                        default_selection_value="all"
                        establishment_select_value="{{ is_array($roleWage) ? $roleWage['establishment'] ?? '' : $roleWage?->establishment->id }}"
                        wage_value="{{ is_array($roleWage) ? $roleWage['wage'] ?? '' : $roleWage?->wage?->rate }}"
                        :roles=$roles :establishments=$establishments />
                @endforeach
            @endif
        </div>
    </div>
    <div class="form-group mt-7">
        <a href="javascript:;" data-repeater-create class="btn btn-sm btn-light-primary">
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_roles')</a>
    </div>
</div>
