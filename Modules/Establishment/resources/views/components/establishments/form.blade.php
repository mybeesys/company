@props(['establishments', 'establishment' => null, 'formId' => null])
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card bodyClass="d-flex gap-5" :title="__('establishment::general.establishment_details')">
        <div class="w-100">
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <x-form.input required :errors=$errors class="py-2" labelClass="mb-0" :label="__('establishment::fields.name')"
                    placeholder="{{ __('establishment::fields.name') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->name }}" name="name" />
            </x-form.input-div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <x-form.input required :errors=$errors class="py-2" labelClass="mb-0" :label="__('establishment::fields.name_en')"
                    placeholder="{{ __('establishment::fields.name_en') }} ({{ __('establishment::fields.required') }})"
                    value="{{ $establishment?->name_en }}" name="name_en" />
            </x-form.input-div>
            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <x-form.input :errors=$errors class="py-2" labelClass="mb-0" :label="__('establishment::fields.address')"
                    placeholder="{{ __('establishment::fields.address') }}" value="{{ $establishment?->address }}"
                    name="address" />
            </x-form.input-div>

            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <x-form.input :errors=$errors class="py-2" labelClass="mb-0" :label="__('establishment::fields.city')"
                    placeholder="{{ __('establishment::fields.city') }}" value="{{ $establishment?->city }}"
                    name="city" />
            </x-form.input-div>

            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <x-form.input :errors=$errors class="py-2" labelClass="mb-0" :label="__('establishment::fields.phone_number')"
                    placeholder="{{ __('establishment::fields.phone_number') }}"
                    value="{{ $establishment?->contact_details }}" name="contact_details" />
            </x-form.input-div>
            @php
                if ($establishment?->children()->exists()) {
                    $disabled = true;
                } else {
                    $disabled = false;
                }
            @endphp
            <x-form.input-div
                class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2 form-check form-check-custom form-check-solid">
                @if ($disabled)
                    <input type="hidden" name="is_main" value="1">
                    <x-form.field-hint :hint="__('establishment::general.est_has_children_note')" />
                @else
                    <input type="hidden" name="is_main" value="0">
                @endif
                <x-form.input type="checkbox" :errors=$errors class="form-check-input" labelClass="mb-0"
                    :label="__('establishment::fields.is_main_establishment')" :disabled="$disabled" checked="{{ $establishment?->is_main }}" :form_control="false"
                    name="is_main" labelClass="w-50" labelWidth />
                <x-form.field-hint :hint="__('establishment::general.main_est_note')" />
            </x-form.input-div>

            <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-10 gap-2">
                <div class="w-100 d-flex mb-5 mb-md-0">
                    <label class="form-label mb-lg-0" for="parent_id">@lang('establishment::fields.main_establishment')</label>
                </div>
                <x-form.select name="parent_id" :options="$establishments" :optionName="get_name_by_lang()" :errors="$errors"
                    data_allow_clear="false" :placeholder="__('establishment::fields.establishment')" :value="$establishment?->parent_id" />
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
                <x-form.input :solid="false" :errors=$errors class="form-check-input" :hint="__('establishment::general.disable_enable_main_est')" labelWidth value="1"
                    type="checkbox" labelClass="form-check-label" name="is_active"
                    label="{{ __('establishment::general.deactivate/activate') }}"
                    checked="{{ $establishment?->is_active }}" />
            </x-form.switch-div>

        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/schedule/establishment') }}" :id=$formId />
</div>
