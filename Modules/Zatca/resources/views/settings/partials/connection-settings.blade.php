<div class="z-banner">
    {{ __('zatca::lang.env_help') }}
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
                           value="{{ old('seller_name', $setting->seller_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="organization_name">{{ __('zatca::lang.organization_name') }}</label>
                    <input type="text" name="organization_name" id="organization_name" class="form-control form-control-solid"
                           value="{{ old('organization_name', $setting->organization_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="vat_number">{{ __('zatca::lang.vat_number') }}</label>
                    <input type="text" name="vat_number" id="vat_number" class="form-control form-control-solid"
                           value="{{ old('vat_number', $setting->vat_number) }}" maxlength="15" inputmode="numeric" required>
                    <div class="z-help">{{ __('zatca::lang.vat_number_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="commercial_registration_number">{{ __('zatca::lang.commercial_registration_number') }}</label>
                    <input type="text" name="commercial_registration_number" id="commercial_registration_number" class="form-control form-control-solid"
                           value="{{ old('commercial_registration_number', $setting->commercial_registration_number) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="organization_unit">{{ __('zatca::lang.organization_unit') }}</label>
                    <input type="text" name="organization_unit" id="organization_unit" class="form-control form-control-solid"
                           value="{{ old('organization_unit', $setting->organization_unit) }}"
                           maxlength="10" inputmode="numeric" pattern="\d{10}" required>
                    <div class="z-help">{{ __('zatca::lang.organization_unit_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="business_category">{{ __('zatca::lang.business_category') }}</label>
                    <input type="text" name="business_category" id="business_category" class="form-control form-control-solid"
                           value="{{ old('business_category', $setting->business_category) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="country_code">{{ __('zatca::lang.country_code') }}</label>
                    <input type="text" name="country_code" id="country_code" class="form-control form-control-solid"
                           value="{{ old('country_code', $setting->country_code ?: 'SA') }}" maxlength="2" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label required" for="invoice_type">{{ __('zatca::lang.invoice_type') }}</label>
                    <select name="invoice_type" id="invoice_type" class="form-select form-select-solid select-2" required>
                        @foreach ($invoiceTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('invoice_type', $setting->invoice_type ?: '1100') === $value)>{{ $label }}</option>
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
                           value="{{ old('city', $setting->city) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="district">{{ __('zatca::lang.district') }}</label>
                    <input type="text" name="district" id="district" class="form-control form-control-solid"
                           value="{{ old('district', $setting->district) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="building_number">{{ __('zatca::lang.building_number') }}</label>
                    <input type="text" name="building_number" id="building_number" class="form-control form-control-solid"
                           value="{{ old('building_number', $setting->building_number) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label required" for="postal_code">{{ __('zatca::lang.postal_code') }}</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control form-control-solid"
                           value="{{ old('postal_code', $setting->postal_code) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="plot_identification">{{ __('zatca::lang.plot_identification') }}</label>
                    <input type="text" name="plot_identification" id="plot_identification" class="form-control form-control-solid"
                           value="{{ old('plot_identification', $setting->plot_identification) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="street_name">{{ __('zatca::lang.street_name') }}</label>
                    <input type="text" name="street_name" id="street_name" class="form-control form-control-solid"
                           value="{{ old('street_name', $setting->street_name) }}">
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
                           value="{{ old('email_address', $setting->email_address) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="otp">{{ __('zatca::lang.otp') }}</label>
                    <input type="text" name="otp" id="otp" class="form-control form-control-solid"
                           value="{{ old('otp', $setting->otp) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="common_name">{{ __('zatca::lang.common_name') }}</label>
                    <input type="text" name="common_name" id="common_name" class="form-control form-control-solid"
                           value="{{ old('common_name', $setting->common_name) }}">
                    <div class="z-help">{{ __('zatca::lang.common_name_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="egs_serial_number">{{ __('zatca::lang.egs_serial_number') }}</label>
                    <input type="text" name="egs_serial_number" id="egs_serial_number" class="form-control form-control-solid"
                           value="{{ old('egs_serial_number', $setting->egs_serial_number) }}">
                    <div class="z-help">{{ __('zatca::lang.egs_serial_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="z-card">
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
        <button type="submit" name="generate_certificates" value="0" class="btn btn-light-primary">
            {{ __('zatca::lang.save_only') }}
        </button>
        <button type="submit" name="generate_certificates" value="1" class="btn btn-primary">
            {{ __('zatca::lang.save_generate') }}
        </button>
        <button type="button" class="btn btn-success" id="btn-zatca-regenerate">
            {{ __('zatca::lang.regenerate') }}
        </button>
    </div>
</form>
