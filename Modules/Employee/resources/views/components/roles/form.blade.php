@props(['role' => null, 'departments' => null])
<div class="d-flex flex-wrap">
    <x-employee::form.input-div class="mb-10 w-100 px-2">
        <x-employee::form.input required :errors=$errors
            placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
            name="name" :label="__('employee::fields.name')" />
    </x-employee::form.input-div>
    <x-employee::form.input-div class="mb-10 w-100 px-2">
        <x-employee::form.input :errors=$errors placeholder="{{ __('employee::fields.department') }}"
            name="department" :label="__('employee::fields.department')">
            <x-slot:datalist>
                <datalist id="departmentlist">
                    @isset($departments)
                        @foreach ($departments as $department)
                            <option value="{{ $department }}">
                        @endforeach
                    @endisset
                </datalist>
            </x-slot:datalist>
        </x-employee::form.input>
    </x-employee::form.input-div>
</div>
<div class="d-flex flex-wrap">
    <x-employee::form.input-div class="mb-10 w-100 px-2">
        <x-employee::form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
         name="rank" :label="__('employee::fields.rank')" />
    </x-employee::form.input-div>
</div>
<input type="hidden" id="role_id" name="role_id" value="">
