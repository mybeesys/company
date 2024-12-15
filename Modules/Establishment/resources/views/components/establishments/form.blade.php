@props(['establishments', 'establishment' => null, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card :title="__('establishment::general.establishment_details')">
        <div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10">
                <div class="w-50 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-0" for="name">@lang('establishment::fields.name')</label>
                </div>
                <x-form.input required :errors=$errors class="py-2"
                    placeholder="{{ __('establishment::fields.name') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->name }}" name="name" />
            </x-form.input-div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10">
                <div class="w-50 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-0" for="address">@lang('establishment::fields.address')</label>
                </div>
                <x-form.input required :errors=$errors class="py-2"
                    placeholder="{{ __('establishment::fields.address') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->address }}" name="address" />
            </x-form.input-div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10">
                <div class="w-50 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-0" for="city">@lang('establishment::fields.city')</label>
                </div>
                <x-form.input required :errors=$errors class="py-2"
                    placeholder="{{ __('establishment::fields.city') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->city }}" name="city" />
            </x-form.input-div>
        </div>
        <div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10">
                <div class="w-50 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-0" for="phone_number">@lang('establishment::fields.phone_number')</label>
                </div>
                <x-form.input required :errors=$errors class="py-2"
                    placeholder="{{ __('establishment::fields.phone_number') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->phone_number }}" name="phone_number" />
            </x-form.input-div>

            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold ">
                <div class="w-50 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-0" for="logo">@lang('establishment::fields.logo')</label>
                </div>
                <div class="w-100">
                    <div class="dropzone py-3" id="kt_dropzonejs_example_1">
                        <div class="dz-message needsclick">
                            <i class="ki-duotone ki-file-up fs-3x text-primary"><span class="path1"></span><span
                                    class="path2"></span></i>

                            <div class="ms-4">
                                <h3 class="fs-5 fw-bold text-gray-900 mb-1">@lang('establishment::general.click_to_upload')</h3>
                                <span class="fs-7 fw-semibold text-gray-500">@lang('establishment::general.image_hint')</span>
                            </div>

                        </div>
                    </div>

                </div>
            </x-form.input-div>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/schedule/establishment') }}" :id=$formId />
</div>
