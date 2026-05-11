@props([
    'establishments',
    'establishment' => null,
    'formId' => null,
    'showPerpetualInventoryAccount' => false,
    'perpetualInventoryAccounts' => null,
])
<div class="establishment-form d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <x-form.form-card bodyClass="d-flex gap-5" :title="__('establishment::general.establishment_details')">
        <div class="w-100">
            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input required :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.name')"
                            placeholder="{{ __('establishment::fields.name') }} ({{ __('establishment::fields.required') }})"
                            value="{{ $establishment?->name }}" name="name" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input required :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.name_en')"
                            placeholder="{{ __('establishment::fields.name_en') }} ({{ __('establishment::fields.required') }})"
                            value="{{ $establishment?->name_en }}" name="name_en" />
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.address')"
                            placeholder="{{ __('establishment::fields.address') }}" value="{{ $establishment?->address }}"
                            name="address" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.code')"
                            placeholder="{{ __('establishment::fields.code') }}" value="{{ $establishment?->code}}"
                            name="code" />
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.city')"
                            placeholder="{{ __('establishment::fields.city') }}" value="{{ $establishment?->city }}"
                            name="city" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.region')"
                            placeholder="{{ __('establishment::fields.region') }}" value="{{ $establishment?->region }}"
                            name="region" />
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <x-form.input :errors=$errors class="py-2" labelClass="form-label fw-semibold mb-2" :label="__('establishment::fields.phone_number')"
                            placeholder="{{ __('establishment::fields.phone_number') }}"
                            value="{{ $establishment?->contact_details }}" name="contact_details" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <label class="form-label fw-semibold mb-2" for="parent_id">@lang('establishment::fields.main_establishment')</label>
                        <x-form.select name="parent_id" class="w-100" :options="$establishments" :optionName="get_name_by_lang()" :errors="$errors"
                            data_allow_clear="false" :placeholder="__('establishment::fields.establishment')" :value="$establishment?->parent_id" />
                    </div>
                </div>
            </div>

            @php
                if ($establishment?->hasAnyRelation()) {
                    $disabled = true;
                } else {
                    $disabled = false;
                }
            @endphp
            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                <div class="col-12">
                    <div class="fv-row w-100 d-flex flex-column flex-sm-row align-items-sm-center gap-3 form-check form-check-custom form-check-solid">
                        @if ($disabled)
                            <input type="hidden" name="is_main" value="1">
                            <x-form.field-hint :hint="__('establishment::general.est_has_children_note')" />
                        @else
                            <input type="hidden" name="is_main" value="0">
                        @endif
                        <x-form.input type="checkbox" :errors=$errors class="form-check-input" labelClass="mb-0"
                            :label="__('establishment::fields.is_main_establishment')" :disabled="$disabled" checked="{{ $establishment?->is_main }}" :form_control="false"
                            name="is_main" labelWidth />
                        <x-form.field-hint :hint="__('establishment::general.main_est_note')" />
                    </div>
                </div>
            </div>

            <div class="row g-4 g-lg-5 mb-4 mb-lg-5">
                @if ($showPerpetualInventoryAccount)
                    <div class="col-md-6 col-lg-5">
                        <div class="fv-row w-100">
                            <label class="form-label fw-semibold mb-2" for="perpetual_inventory_account_id">@lang('establishment::fields.perpetual_inventory_account')</label>
                            <select name="perpetual_inventory_account_id" id="perpetual_inventory_account_id"
                                class="form-select form-select-solid select-2 w-100" data-placeholder="@lang('messages.select')">
                                <option value=""></option>
                                @foreach ($perpetualInventoryAccounts ?? [] as $acc)
                                    <option value="{{ $acc->id }}" @selected((string) old('perpetual_inventory_account_id', $establishment?->perpetual_inventory_account_id) === (string) $acc->id)>
                                        {{ app()->getLocale() === 'ar'
                                            ? trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_ar.($acc->account_category ? ' ('.$acc->account_category.')' : ''))
                                            : trim(($acc->gl_code ? $acc->gl_code.' — ' : '').$acc->name_en.($acc->account_category ? ' ('.$acc->account_category.')' : '')) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-form.field-hint :hint="__('establishment::general.perpetual_inventory_account_hint')" />
                        </div>
                    </div>
                @else
                    <div class="col-12 col-md-6">
                        <span class="text-muted fs-7">@lang('establishment::general.perpetual_inventory_account_policy_note')</span>
                    </div>
                @endif
            </div>

            <div class="row g-4 g-lg-5 mb-0 align-items-center">
                <div class="col-md-6">
                    <div class="fv-row w-100">
                        <label for="logo" class="form-label fw-semibold mb-2">@lang('establishment::fields.logo')</label>
                        <div class="d-flex flex-column justify-content-center">
                            <x-form.image-input :errors=$errors name="logo" :image="$establishment?->logo" />
                            <div class="text-muted fs-7 mt-2">@lang('employee::general.image_hint')</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-form.switch-div class="mt-3 mt-md-0">
                        <input type="hidden" name="is_active" value="0">
                        <x-form.input :solid="false" :errors=$errors class="form-check-input" :hint="__('establishment::general.disable_enable_main_est')" labelWidth value="1"
                            type="checkbox" labelClass="form-check-label" name="is_active"
                            label="{{ __('establishment::general.deactivate/activate') }}"
                            checked="{{ $establishment?->is_active }}" />
                    </x-form.switch-div>
                </div>
            </div>
        </div>
    </x-form.form-card>
    <x-form.form-buttons cancelUrl="{{ url('/schedule/establishment') }}" :id=$formId />
</div>
