@props(['employee' => null, 'posRoles', 'establishments', 'disabled' => false])
<label @class(['form-label'])>@lang('employee::fields.pos_roles')</label>
@php
    if (old('pos_role_repeater')) {
        $groupedRoles = collect(old('pos_role_repeater'))
            ->groupBy('posRole')
            ->map(function ($group) {
                return [
                    'posRole' => $group->first()['posRole'],
                    'establishment' => $group->pluck('establishment')->flatten()->unique()->values()->toArray(),
                ];
            })
            ->values();
    } else {
        $groupedRoles = $employee?->posRoles->isEmpty()
            ? collect([null])
            : $employee?->posRoles
                    ->groupBy('id')
                    ->map(function ($group) {
                        return [
                            'posRole' => $group->first()->id,
                            'establishment' => $group->pluck('pivot.establishment_id')->unique()->values()->toArray(),
                        ];
                    })
                    ->values() ?? collect([null]);
    }
@endphp
<div id="pos_role_repeater">
    <div class="form-group">
        <div data-repeater-list="pos_role_repeater" class="d-flex flex-column gap-3">
            @foreach ($groupedRoles as $index => $role)
                <div data-repeater-item @class(['d-flex flex-wrap align-items-center gap-3'])>
                    <x-form.input-div class="w-100">
                        <x-form.select name="pos_role_repeater[{{ $index }}][posRole]" :options="$posRoles"
                            :errors="$errors" :disabled=$disabled required
                            placeholder="{{ __('employee::fields.role') }}"
                            value="{{ is_array($role) ? $role['posRole'] ?? '' : $role?->id }}"
                            data_allow_clear="false" />
                    </x-form.input-div>
                    <x-form.input-div class="w-100">
                        <x-form.select name="pos_role_repeater[{{ $index }}][establishment]"
                            data_allow_clear="false" :disabled=$disabled :options="$establishments" :errors="$errors"
                            placeholder="{{ __('employee::fields.establishment') }}" required no_default
                            attribute="multiple" :value="is_array($role) ? $role['establishment'] : $role?->pivot->establishment_id" />
                    </x-form.input-div>
                    <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger"
                        @disabled($disabled)>
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group mt-7">
        <button type="button" data-repeater-create class="btn btn-sm btn-light-primary" @disabled($disabled)>
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_roles')</button>
    </div>
</div>
