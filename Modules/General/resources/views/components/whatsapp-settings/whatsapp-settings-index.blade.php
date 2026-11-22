@props(['notifications_settings_parameters'])
@php
    $firstH = \Modules\General\Support\SettingAccess::firstHorizontal();
    $templateKey = \Modules\General\Support\WhatsAppInvoiceSharePresenter::TEMPLATE_KEY;
    $ttlKey = \Modules\General\Support\WhatsAppInvoiceSharePresenter::TTL_HOURS_KEY;
    $tokenKey = \Modules\General\Support\WhatsAppCloudApiClient::TOKEN_KEY;
    $phoneIdKey = \Modules\General\Support\WhatsAppCloudApiClient::PHONE_NUMBER_ID_KEY;
    $apiVersionKey = \Modules\General\Support\WhatsAppCloudApiClient::API_VERSION_KEY;
    $stored = $notifications_settings_parameters->firstWhere('key', $templateKey)?->value;
    $templateValue = filled($stored)
        ? $stored
        : \Modules\General\Support\WhatsAppInvoiceSharePresenter::defaultApiTemplate();
    $ttlStored = $notifications_settings_parameters->firstWhere('key', $ttlKey)?->value;
    $ttlHours = is_numeric($ttlStored)
        ? (int) $ttlStored
        : \Modules\General\Support\WhatsAppInvoiceSharePresenter::DEFAULT_TTL_HOURS;
    $apiVersion = $notifications_settings_parameters->firstWhere('key', $apiVersionKey)?->value
        ?: \Modules\General\Support\WhatsAppCloudApiClient::DEFAULT_API_VERSION;
@endphp
<div class="tab-pane fade {{ $firstH === 'whatsapp' ? 'show active' : '' }}" id="whatsapp_settings_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <form class="d-flex flex-column gap-5 w-100 mt-10" action="#" id="whatsapp_settings_form">
            @csrf

            <div class="mb-2">
                <h3 class="fs-4 fw-bold mb-2">@lang('general::general.whatsapp_cloud_title')</h3>
                <p class="text-muted fs-7 mb-0">@lang('general::general.whatsapp_cloud_hint')</p>
            </div>

            <div class="d-flex flex-wrap w-100">
                <x-form.input-div class="mb-5 w-100 w-md-50 px-2">
                    <x-form.input
                        :errors="$errors"
                        type="password"
                        autocomplete="new-password"
                        placeholder="EAAG..."
                        value="{{ $notifications_settings_parameters->firstWhere('key', $tokenKey)?->value }}"
                        name="key[{{ $tokenKey }}]"
                        :label="__('general::general.whatsapp_cloud_token')"
                    />
                </x-form.input-div>
                <x-form.input-div class="mb-5 w-100 w-md-50 px-2">
                    <x-form.input
                        :errors="$errors"
                        placeholder="123456789012345"
                        value="{{ $notifications_settings_parameters->firstWhere('key', $phoneIdKey)?->value }}"
                        name="key[{{ $phoneIdKey }}]"
                        :label="__('general::general.whatsapp_cloud_phone_number_id')"
                    />
                </x-form.input-div>
                <x-form.input-div class="mb-5 w-100 w-md-50 px-2">
                    <x-form.input
                        :errors="$errors"
                        placeholder="v21.0"
                        value="{{ $apiVersion }}"
                        name="key[{{ $apiVersionKey }}]"
                        :label="__('general::general.whatsapp_cloud_api_version')"
                    />
                </x-form.input-div>
            </div>

            <div class="fv-row">
                <label class="form-label required" for="whatsapp_invoice_link_ttl">@lang('general::general.whatsapp_link_ttl')</label>
                <select
                    id="whatsapp_invoice_link_ttl"
                    name="key[{{ $ttlKey }}]"
                    class="form-select form-select-solid"
                    required
                >
                    @foreach (\Modules\General\Support\WhatsAppInvoiceSharePresenter::TTL_HOUR_OPTIONS as $hours)
                        <option value="{{ $hours }}" @selected($ttlHours === $hours)>
                            {{ \Modules\General\Support\WhatsAppInvoiceSharePresenter::linkValidityLabel($hours) }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">@lang('general::general.whatsapp_link_ttl_hint')</div>
            </div>

            <div class="mb-2 mt-4">
                <h3 class="fs-4 fw-bold mb-2">@lang('general::general.whatsapp_invoice_template')</h3>
                <p class="text-muted fs-7 mb-0">@lang('general::general.whatsapp_invoice_template_hint')</p>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach (['customer_name', 'invoice_no', 'amount', 'date', 'company_name', 'link_validity'] as $token)
                    <code class="badge badge-light-primary fs-8">{{ '{'.$token.'}' }}</code>
                @endforeach
            </div>

            <div class="fv-row">
                <label class="form-label required" for="whatsapp_invoice_template">@lang('general::general.whatsapp_message_body')</label>
                <textarea
                    id="whatsapp_invoice_template"
                    name="key[{{ $templateKey }}]"
                    class="form-control form-control-solid"
                    rows="14"
                    required
                    dir="auto"
                >{{ $templateValue }}</textarea>
            </div>

            <x-form.form-buttons id="whatsapp_settings_form" :showSubmit="\Modules\General\Support\SettingAccess::canTab('mail', 'update')" />
        </form>
    </x-cards.card>
</div>

<script>
    function whatsappSettingsForm() {
        $('#whatsapp_settings_form').on('submit', function(e) {
            e.preventDefault();
            ajaxRequest('store-notification-settings-parameters', "POST", $(this).serializeArray()).fail(
                function(data) {
                    $.each(data.responseJSON.errors || {}, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value + '</div>');
                    });
                });
        });
    }
</script>
