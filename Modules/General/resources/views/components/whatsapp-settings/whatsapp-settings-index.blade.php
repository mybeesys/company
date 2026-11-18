@props(['notifications_settings_parameters'])
@php
    $firstH = \Modules\General\Support\SettingAccess::firstHorizontal();
    $templateKey = \Modules\General\Support\WhatsAppInvoiceSharePresenter::TEMPLATE_KEY;
    $stored = $notifications_settings_parameters->firstWhere('key', $templateKey)?->value;
    $templateValue = filled($stored)
        ? $stored
        : \Modules\General\Support\WhatsAppInvoiceSharePresenter::defaultTemplate();
@endphp
<div class="tab-pane fade {{ $firstH === 'whatsapp' ? 'show active' : '' }}" id="whatsapp_settings_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <form class="d-flex flex-column gap-5 w-100 mt-10" action="#" id="whatsapp_settings_form">
            @csrf
            <div class="mb-2">
                <h3 class="fs-4 fw-bold mb-2">@lang('general::general.whatsapp_invoice_template')</h3>
                <p class="text-muted fs-7 mb-0">@lang('general::general.whatsapp_invoice_template_hint')</p>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach (['customer_name', 'invoice_no', 'amount', 'date', 'company_name', 'download_link'] as $token)
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
