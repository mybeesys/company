@php
    $formValues = $formValues ?? [];
    $appliedFromCompany = $appliedFromCompany ?? [];
    $companyDefaults = $companyDefaults ?? ['values' => [], 'available' => false, 'company_name' => null];
    $fv = static function (string $key, mixed $fallback = '') use ($formValues) {
        if (array_key_exists($key, $formValues)) {
            return old($key, $formValues[$key]);
        }

        return old($key, $fallback);
    };
    $fromCompanyHint = static function (string $key) use ($appliedFromCompany) {
        if (empty($appliedFromCompany[$key])) {
            return '';
        }

        return '<div class="z-help text-primary mt-1"><i class="fa fa-building me-1"></i>'
            .e(__('zatca::lang.from_company_details'))
            .'</div>';
    };
@endphp

@include('zatca::settings.partials.setup-readiness', ['readiness' => $readiness])

<div class="z-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <div class="fw-semibold mb-1">{{ __('zatca::lang.env_help') }}</div>
        @if (! empty($companyDefaults['available']))
            <div class="small opacity-75">
                {{ __('zatca::lang.company_defaults_hint', [
                    'company' => $companyDefaults['company_name'] ?: __('zatca::lang.menu_card'),
                    'count' => count($companyDefaults['values']),
                ]) }}
            </div>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if (! empty($companyDefaults['available']))
            <button type="button" class="btn btn-sm btn-light-primary" id="btn-zatca-fill-company">
                <i class="fa fa-building me-1"></i>
                {{ __('zatca::lang.fill_from_company') }}
            </button>
        @endif
        <button type="button" class="btn btn-sm btn-light-dark" id="btn-zatca-fill-sandbox">
            <i class="fa fa-flask me-1"></i>
            {{ __('zatca::lang.fill_sandbox_sample') }}
        </button>
    </div>
</div>

<form method="POST" action="{{ route('zatca.settings.update') }}" id="zatca-settings-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="active_tab" value="connection">

    <div class="z-card">
        <div class="z-card-header">
            <h2 class="z-card-title">{{ __('zatca::lang.section_environment') }}</h2>
        </div>
        <div class="z-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label required" for="zatca_environment">{{ __('zatca::lang.section_environment') }}</label>
                    <select name="zatca_environment" id="zatca_environment" class="form-select form-select-solid select-2" required>
                        @foreach ($environments as $value => $label)
                            <option value="{{ $value }}" @selected(old('zatca_environment', $setting->zatca_environment) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6" id="zatca_app_key_wrap">
                    <label class="form-label" for="zatca_app_key">{{ __('zatca::lang.app_key') }}</label>
                    <input type="password" name="zatca_app_key" id="zatca_app_key" class="form-control form-control-solid"
                           value="{{ old('zatca_app_key') }}"
                           autocomplete="new-password"
                           placeholder="••••••••">
                    <div class="z-help">{{ __('zatca::lang.app_key_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="z-card">
        <div class="z-card-header">
            <h2 class="z-card-title">{{ __('zatca::lang.section_seller') }}</h2>
        </div>
        <div class="z-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label required" for="seller_name">{{ __('zatca::lang.seller_name') }}</label>
                    <input type="text" name="seller_name" id="seller_name" class="form-control form-control-solid"
                           value="{{ $fv('seller_name') }}" required>
                    {!! $fromCompanyHint('seller_name') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="organization_name">{{ __('zatca::lang.organization_name') }}</label>
                    <input type="text" name="organization_name" id="organization_name" class="form-control form-control-solid"
                           value="{{ $fv('organization_name') }}" required>
                    {!! $fromCompanyHint('organization_name') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="vat_number">{{ __('zatca::lang.vat_number') }}</label>
                    <input type="text" name="vat_number" id="vat_number" class="form-control form-control-solid"
                           value="{{ $fv('vat_number') }}" maxlength="15" inputmode="numeric" required>
                    <div class="z-help">{{ __('zatca::lang.vat_number_help') }}</div>
                    {!! $fromCompanyHint('vat_number') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="commercial_registration_number">{{ __('zatca::lang.commercial_registration_number') }}</label>
                    <input type="text" name="commercial_registration_number" id="commercial_registration_number" class="form-control form-control-solid"
                           value="{{ $fv('commercial_registration_number') }}" required>
                    <div class="z-help">{{ __('zatca::lang.company_field_manual_crn') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="organization_unit">{{ __('zatca::lang.organization_unit') }}</label>
                    <input type="text" name="organization_unit" id="organization_unit" class="form-control form-control-solid"
                           value="{{ $fv('organization_unit') }}"
                           maxlength="10" inputmode="numeric" pattern="\d{10}" required>
                    <div class="z-help">{{ __('zatca::lang.organization_unit_help') }}</div>
                    {!! $fromCompanyHint('organization_unit') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="business_category">{{ __('zatca::lang.business_category') }}</label>
                    <input type="text" name="business_category" id="business_category" class="form-control form-control-solid"
                           value="{{ $fv('business_category') }}" required>
                    {!! $fromCompanyHint('business_category') !!}
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="country_code">{{ __('zatca::lang.country_code') }}</label>
                    <input type="text" name="country_code" id="country_code" class="form-control form-control-solid"
                           value="{{ $fv('country_code', 'SA') }}" maxlength="2" required>
                    {!! $fromCompanyHint('country_code') !!}
                </div>
                <div class="col-md-8">
                    <label class="form-label required" for="invoice_type">{{ __('zatca::lang.invoice_type') }}</label>
                    <select name="invoice_type" id="invoice_type" class="form-select form-select-solid select-2" required>
                        @foreach ($invoiceTypes as $value => $label)
                            <option value="{{ $value }}" @selected($fv('invoice_type', '1100') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="z-card">
        <div class="z-card-header">
            <h2 class="z-card-title">{{ __('zatca::lang.section_address') }}</h2>
        </div>
        <div class="z-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label required" for="city">{{ __('zatca::lang.city') }}</label>
                    <input type="text" name="city" id="city" class="form-control form-control-solid"
                           value="{{ $fv('city') }}" required>
                    {!! $fromCompanyHint('city') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="district">{{ __('zatca::lang.district') }}</label>
                    <input type="text" name="district" id="district" class="form-control form-control-solid"
                           value="{{ $fv('district') }}" required>
                    {!! $fromCompanyHint('district') !!}
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="building_number">{{ __('zatca::lang.building_number') }}</label>
                    <input type="text" name="building_number" id="building_number" class="form-control form-control-solid"
                           value="{{ $fv('building_number') }}"
                           maxlength="4" inputmode="numeric" pattern="\d{4}" required>
                    <div class="z-help">{{ __('zatca::lang.zatca_err_building_number') }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="postal_code">{{ __('zatca::lang.postal_code') }}</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control form-control-solid"
                           value="{{ $fv('postal_code') }}"
                           maxlength="5" inputmode="numeric" pattern="\d{5}" required>
                    <div class="z-help">{{ __('zatca::lang.zatca_err_postal_code') }}</div>
                    {!! $fromCompanyHint('postal_code') !!}
                </div>
                {{-- Temporarily hidden from UI; keep value so save does not clear it --}}
                <input type="hidden" name="plot_identification" id="plot_identification"
                       value="{{ $fv('plot_identification') }}">
                <div class="col-md-12">
                    <label class="form-label" for="street_name">{{ __('zatca::lang.street_name') }}</label>
                    <input type="text" name="street_name" id="street_name" class="form-control form-control-solid"
                           value="{{ $fv('street_name') }}">
                    {!! $fromCompanyHint('street_name') !!}
                </div>
            </div>
        </div>
    </div>

    <div class="z-card">
        <div class="z-card-header">
            <h2 class="z-card-title">{{ __('zatca::lang.section_csr') }}</h2>
            <p class="z-card-subtitle">{{ __('zatca::lang.otp_help') }}</p>
        </div>
        <div class="z-card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label required" for="email_address">{{ __('zatca::lang.email_address') }}</label>
                    <input type="email" name="email_address" id="email_address" class="form-control form-control-solid"
                           value="{{ $fv('email_address') }}" required>
                    {!! $fromCompanyHint('email_address') !!}
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="otp">{{ __('zatca::lang.otp') }}</label>
                    <input type="text" name="otp" id="otp" class="form-control form-control-solid"
                           value="{{ old('otp', $setting->otp) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="common_name">{{ __('zatca::lang.common_name') }}</label>
                    <input type="text" name="common_name" id="common_name" class="form-control form-control-solid"
                           value="{{ $fv('common_name') }}">
                    <div class="z-help">{{ __('zatca::lang.common_name_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="egs_serial_number">{{ __('zatca::lang.egs_serial_number') }}</label>
                    <input type="text" name="egs_serial_number" id="egs_serial_number" class="form-control form-control-solid"
                           value="{{ $fv('egs_serial_number') }}">
                    <div class="z-help">{{ __('zatca::lang.egs_serial_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="z-card" id="zatca-credentials-card">
        <div class="z-card-header">
            <h2 class="z-card-title">{{ __('zatca::lang.section_credentials') }}</h2>
        </div>
        <div class="z-card-body">
            @if ($setting->isConfigured())
                <div class="alert alert-success mb-0">{{ __('zatca::lang.credentials_present') }}</div>
            @else
                <div class="alert alert-secondary mb-0">{{ __('zatca::lang.credentials_absent') }}</div>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-3 justify-content-end mb-5">
        @if ($canSettingsUpdate ?? false)
            <button type="submit" name="generate_certificates" value="0" class="btn btn-light-primary">
                {{ __('zatca::lang.save_only') }}
            </button>
            <button type="submit" name="generate_certificates" value="1" class="btn btn-primary">
                {{ __('zatca::lang.save_generate') }}
            </button>
        @endif
        @if ($canRegenerate ?? false)
            <button type="button" class="btn btn-success" id="btn-zatca-regenerate">
                {{ __('zatca::lang.regenerate') }}
            </button>
        @endif
    </div>
</form>
