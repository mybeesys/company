@props([
    'role_select_value',
    'establishment_select_value',
    'wage_value',
    'default_selection' => null,
    'default_selection_value' => null,
    'index',
    'roles',
    'establishments',
])
<div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
    <x-employee::form.input-div class="w-100">
        <x-employee::form.select name="role_wage_repeater[{{ $index }}][role]" :options="$roles" :errors="$errors"
            value="{{ $role_select_value }}" />
    </x-employee::form.input-div>
    <x-employee::form.input-div class="w-100">
        <x-employee::form.select name="role_wage_repeater[{{ $index }}][establishment]" data_allow_clear="false"
            :options="$establishments" :errors="$errors" required default_selection="{{ $default_selection }}"
            default_selection_value="{{ $default_selection_value }}" value="{{ $establishment_select_value }}" />
    </x-employee::form.input-div>
    <x-employee::form.input-div class="w-100">
        <x-employee::form.input :errors="$errors" type="number" placeholder="{{ __('employee::fields.wage') }}"
            name="role_wage_repeater[{{ $index }}][wage]" value="{{ $wage_value }}" />
    </x-employee::form.input-div>
    <a href="javascript:;" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger">
        <i class="ki-outline ki-cross fs-1"></i>
    </a>
</div>
