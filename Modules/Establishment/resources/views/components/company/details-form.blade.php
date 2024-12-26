@props(['company', 'countries'])
<x-form.form-card :headerDiv="false" class="mb-5">
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2 mt-10">
        <x-form.input readonly :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.name')"
            placeholder="{{ __('establishment::fields.name') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->name }}" name="name" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.ceo_name')"
            placeholder="{{ __('establishment::fields.ceo_name') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->ceo_name }}" name="ceo_name" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.phone')"
            placeholder="{{ __('establishment::fields.phone') }}" value="{{ $company?->phone }}" name="phone" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <div class="w-100 d-flex mb-5 mb-md-0">
            <label class="form-label mb-lg-0" for="country">@lang('establishment::fields.country')</label>
        </div>
        <x-form.select required name="country_id" :options="$countries" :errors="$errors" data_allow_clear="false"
            :placeholder="__('establishment::fields.country')" value="{{ $company?->country_id }}" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.country_state')"
            placeholder="{{ __('establishment::fields.state') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->state }}" name="state" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.city')"
            placeholder="{{ __('establishment::fields.city') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->city }}" name="city" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.zipcode')"
            placeholder="{{ __('establishment::fields.phone') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->zipcode }}" name="zipcode" />
    </x-form.input-div>

    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.national_address')"
            placeholder="{{ __('establishment::fields.national_address') }}" value="{{ $company?->national_address }}"
            name="national_address" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.website')"
            placeholder="{{ __('establishment::fields.website') }}" value="{{ $company?->website }}" name="website" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.tax_name')"
            placeholder="{{ __('establishment::fields.tax_name') }} ({{ __('establishment::fields.required') }})"
            value="{{ $company?->tax_name }}" name="tax_name" />
    </x-form.input-div>
</x-form.form-card>
<x-form.form-buttons id="company_settings_form" />
