@props(['company', 'countries', 'settings'])
<x-form.form-card :headerDiv="false" class="mb-5">
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2 mt-10">
        <x-form.input readonly :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.name')"
            placeholder="{{ __('establishment::fields.name') }}" value="{{ $company?->name }}" name="name" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2 mt-10">
        <x-form.input required :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.email')"
            placeholder="{{ __('establishment::fields.email') }}" value="{{ $company->email }}" name="email" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.ceo_name')"
            placeholder="{{ __('establishment::fields.ceo_name') }}" value="{{ $company?->ceo_name }}"
            name="ceo_name" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.phone')"
            placeholder="{{ __('establishment::fields.phone') }}" value="{{ $company?->phone }}" name="phone" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <div class="w-100 d-flex mb-5 mb-md-0">
            <label class="form-label mb-lg-0" for="country">@lang('establishment::fields.country')</label>
        </div>
        <x-form.select name="country_id" :options="$countries" :errors="$errors" data_allow_clear="false" :placeholder="__('establishment::fields.country')"
            value="{{ $company?->country_id }}" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.country_state')"
            placeholder="{{ __('establishment::fields.state') }} " value="{{ $company?->state }}" name="state" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.city')"
            placeholder="{{ __('establishment::fields.city') }} " value="{{ $company?->city }}" name="city" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.zipcode')"
            placeholder="{{ __('establishment::fields.phone') }}" value="{{ $company?->zipcode }}" name="zipcode" />
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
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.tax_name')"
            placeholder="{{ __('establishment::fields.tax_name') }}" value="{{ $company?->tax_name }}"
            name="tax_name" />
    </x-form.input-div>
    <x-form.input-div class="w-lg-50 d-md-flex align-items-center fw-bold mb-5 gap-2">
        <x-form.input :errors=$errors class="py-2" labelClass="mb-lg-0" :label="__('establishment::fields.tax_number')"
            placeholder="{{ __('establishment::fields.tax_number') }}" value="{{ $company?->tax_number }}"
            name="tax_number" />
    </x-form.input-div>
</x-form.form-card>

<div class="card card-flush py-4 mt-5">
    <div class="card-header">
        <div class="card-title">
            <h2>{{ __('general::lang.social_media_links') }}</h2>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row g-9 mb-7">
            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2">{{ __('general::lang.whatsapp') }}</label>
                <input type="text" class="form-control form-control-solid" name="social_whatsapp"
                    value="{{ $settings->where('key', 'social_whatsapp')->first()->value ?? '' }}"
                    placeholder="https://wa.me/..." />
            </div>

            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2">{{ __('general::lang.facebook') }}</label>
                <input type="text" class="form-control form-control-solid" name="social_facebook"
                    value="{{ $settings->where('key', 'social_facebook')->first()->value ?? '' }}" />
            </div>

            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2">{{ __('general::lang.instagram') }}</label>
                <input type="text" class="form-control form-control-solid" name="social_instagram"
                    value="{{ $settings->where('key', 'social_instagram')->first()->value ?? '' }}" />
            </div>

            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2">{{ __('general::lang.snapchat') }}</label>
                <input type="text" class="form-control form-control-solid" name="social_snapchat"
                    value="{{ $settings->where('key', 'social_snapchat')->first()->value ?? '' }}" />
            </div>

            <div class="col-md-4 fv-row">
                <label class="fs-6 fw-semibold mb-2">{{ __('general::lang.x_twitter') }}</label>
                <input type="text" class="form-control form-control-solid" name="social_x"
                    value="{{ $settings->where('key', 'social_x')->first()->value ?? '' }}" />
            </div>
        </div>
    </div>
</div>

<div class="card card-flush py-4 mt-5">
    <div class="card-header">
        <div class="card-title">
            <h2>{{ __('general::lang.menu_cover_image') }}</h2>
        </div>
    </div>
    <div class="card-body pt-0 text-center">
        <style>
            .image-input-placeholder {
                background-image: url('{{ asset('assets/media/svg/avatars/blank.svg') }}');
            }

            [data-bs-theme="dark"] .image-input-placeholder {
                background-image: url('{{ asset('assets/media/svg/avatars/blank-dark.svg') }}');
            }
        </style>

        <div class="image-input image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
    @php
        $coverSetting = $settings->where('key', 'menu_cover_image')->first();
        $coverPath = $coverSetting?->value;

        if ($coverPath && Storage::disk('public')->exists($coverPath)) {
            $version = $coverSetting?->updated_at?->timestamp ?? now()->timestamp;
            $tenantPrefix = tenancy()->tenant ? ('tenant' . tenancy()->tenant->id . '/') : '';
            $coverUrl = asset('storage/' . $tenantPrefix . ltrim($coverPath, '/')) . '?v=' . $version;
        } else {
            $coverUrl = asset('assets/media/svg/avatars/blank.svg');
        }
    @endphp

    <div class="image-input-wrapper w-150px h-150px" style="background-image: url('{{ $coverUrl }}')">
    </div>

    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
        data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{ __('Change cover') }}">
        <i class="ki-outline ki-pencil fs-7"></i>

        <input type="file" name="menu_cover_image" accept=".png, .jpg, .jpeg" />
        <input type="hidden" name="avatar_remove" />
    </label>

    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
        data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{ __('Cancel cover') }}">
        <i class="ki-outline ki-cross fs-2"></i>
    </span>

    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
        data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{ __('Remove cover') }}">
        <i class="ki-outline ki-cross fs-2"></i>
    </span>
</div>
        <div class="text-muted fs-7">{{ __('Allowed file types: png, jpg, jpeg.') }}</div>
    </div>
</div>
<x-form.form-buttons id="company_settings_form" />
