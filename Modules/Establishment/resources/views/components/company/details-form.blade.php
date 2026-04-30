@props(['company', 'countries', 'settings'])
<style>
    .company-details-surface {
        border: 1px solid #eef0f4;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
    }

    .company-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #111827;
    }
    .company-subtle-note {
        color: #6b7280;
        font-size: .82rem;
    }
</style>

<x-form.form-card :headerDiv="false" class="mb-5 company-details-surface">
    <div class="border-bottom px-6 pt-6 pb-4 mb-3">
        <div class="company-section-title">{{ __('establishment::general.company_details') }}</div>
        <div class="company-subtle-note">{{ __('establishment::general.company_details_subtitle') }}</div>
    </div>
    <div class="px-6 pb-6">
    <div class="row g-6 mb-1">
        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.name') }}</label>
            <input type="text" class="form-control form-control-solid" name="name"
                value="{{ $company?->name }}" readonly placeholder="{{ __('establishment::fields.name') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.email') }}</label>
            <input type="text" class="form-control form-control-solid" name="email"
                value="{{ $company?->email }}" required placeholder="{{ __('establishment::fields.email') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.ceo_name') }}</label>
            <input type="text" class="form-control form-control-solid" name="ceo_name"
                value="{{ $company?->ceo_name }}" placeholder="{{ __('establishment::fields.ceo_name') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.phone') }}</label>
            <input type="text" class="form-control form-control-solid" name="phone"
                value="{{ $company?->phone }}" placeholder="{{ __('establishment::fields.phone') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.country') }}</label>
            <select class="form-select form-select-solid" name="country_id" data-control="select2" data-hide-search="true">
                <option value="">{{ __('establishment::fields.country') }}</option>
                @foreach ($countries as $country)
                    <option value="{{ $country['id'] }}" @selected((string) $company?->country_id === (string) $country['id'])>
                        {{ $country['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.country_state') }}</label>
            <input type="text" class="form-control form-control-solid" name="state"
                value="{{ $company?->state }}" placeholder="{{ __('establishment::fields.state') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.city') }}</label>
            <input type="text" class="form-control form-control-solid" name="city"
                value="{{ $company?->city }}" placeholder="{{ __('establishment::fields.city') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.zipcode') }}</label>
            <input type="text" class="form-control form-control-solid" name="zipcode"
                value="{{ $company?->zipcode }}" placeholder="{{ __('establishment::fields.zipcode') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.national_address') }}</label>
            <input type="text" class="form-control form-control-solid" name="national_address"
                value="{{ $company?->national_address }}" placeholder="{{ __('establishment::fields.national_address') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.website') }}</label>
            <input type="text" class="form-control form-control-solid" name="website"
                value="{{ $company?->website }}" placeholder="{{ __('establishment::fields.website') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.tax_name') }}</label>
            <input type="text" class="form-control form-control-solid" name="tax_name"
                value="{{ $company?->tax_name }}" placeholder="{{ __('establishment::fields.tax_name') }}" />
        </div>

        <div class="col-md-4 fv-row">
            <label class="fs-6 fw-semibold mb-2">{{ __('establishment::fields.tax_number') }}</label>
            <input type="text" class="form-control form-control-solid" name="tax_number"
                value="{{ $company?->tax_number }}" placeholder="{{ __('establishment::fields.tax_number') }}" />
        </div>
    </div>
    </div>
</x-form.form-card>

<div class="card card-flush py-4 mt-5 company-details-surface">
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

<div class="card card-flush py-4 mt-5 company-details-surface">
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
