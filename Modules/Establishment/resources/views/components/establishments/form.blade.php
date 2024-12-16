@props(['establishments', 'establishment' => null, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('establishment::general.establishment_details')">
        <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
            <x-form.input required :errors=$errors class="py-2" :label="__('establishment::fields.name')"
                placeholder="{{ __('establishment::fields.name') }} ({{ __('establishment::fields.required') }})"
                value="{{ $establishment?->name }}" name="name" />
        </x-form.input-div>
        <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
            <x-form.input :errors=$errors class="py-2" :label="__('establishment::fields.address')"
                placeholder="{{ __('establishment::fields.address') }} ({{ __('establishment::fields.required') }})"
                value="{{ $establishment?->address }}" name="address" />
        </x-form.input-div>
        <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
            <x-form.input :errors=$errors class="py-2" :label="__('establishment::fields.city')"
                placeholder="{{ __('establishment::fields.city') }} ({{ __('establishment::fields.required') }})"
                value="{{ $establishment?->city }}" name="city" />
        </x-form.input-div>
        <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
            <x-form.input :errors=$errors class="py-2" :label="__('establishment::fields.phone_number')"
                placeholder="{{ __('establishment::fields.phone_number') }} ({{ __('establishment::fields.required') }})"
                value="{{ $establishment?->contact_details }}" name="contact_details" />
        </x-form.input-div>

        <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold gap-2 mb-10">
            <label for="logo" class="w-100">@lang('establishment::fields.logo')</label>
            <div class="w-100 d-flex flex-column justify-content-center">
                <x-form.image-input :errors=$errors name="logo" :image="$establishment?->logo" />
                <div class="text-muted fs-7 mx-auto">@lang('employee::general.image_hint')</div>
            </div>

        </x-form.input-div>

        <x-form.switch-div class="my-auto">
            <input type="hidden" name="is_active" value="0">
            <x-form.input :errors=$errors class="form-check-input" value="1" type="checkbox"
                labelClass="form-check-label" name="is_active"
                label="{{ __('establishment::general.deactivate/activate') }}"
                checked="{{ $establishment?->is_active }}" />
        </x-form.switch-div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/schedule/establishment') }}" :id=$formId />
</div>
