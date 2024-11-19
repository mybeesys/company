@props(['dashboardRole' => null, 'modules', 'rolePermissions' => null, 'disabled' => false, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('employee::general.role_details')">
        <div class="d-flex flex-wrap gap-5">
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors :disabled=$disabled
                    placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                    value="{{ $dashboardRole?->name }}" name="name" :label="__('employee::fields.name')" />
            </x-form.input-div>
            <x-form.input-div class="mb-10 w-100 px-2">
                <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.rank') }} (1-999)"
                    :disabled=$disabled value="{{ $dashboardRole?->rank }}" name="rank" :label="__('employee::fields.rank')" />
            </x-form.input-div>
            <x-form.switch-div class="my-auto">
                <input type="hidden" name="is_active" value="0">
                <x-form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                    labelClass="form-check-label" name="is_active" :disabled=$disabled
                    label="{{ __('employee::general.deactivate/activate') }}"
                    checked="{{ $dashboardRole?->is_active }}" />
            </x-form.switch-div>
        </div>
    </x-form.form-card>

    <x-employee::dashboard-roles.permissions-input :modules=$modules :rolePermissions=$rolePermissions />

    <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" :disabled=$disabled :id=$formId />
</div>
