@props(['notifications_settings_parameters'])
<div class="tab-pane fade" id="mail_settings_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <form class="d-flex flex-column gap-5 w-100 mt-10" action="#" id="mail_settings_form">
            @csrf
            <div class="d-flex flex-wrap w-100">
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_MAILER"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_MAILER')?->value }}"
                        name="key[MAIL_MAILER]" label="MAIL_MAILER" />
                </x-form.input-div>
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_HOST"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_HOST')?->value }}"
                        name="key[MAIL_HOST]" label="MAIL_HOST" />
                </x-form.input-div>

            </div>
            <div class="d-flex flex-wrap w-100">
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_PORT"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_PORT')?->value }}"
                        name="key[MAIL_PORT]" label="MAIL_PORT" />
                </x-form.input-div>
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_ENCRYPTION"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_ENCRYPTION')?->value }}"
                        name="key[MAIL_ENCRYPTION]" label="MAIL_ENCRYPTION" />
                </x-form.input-div>
            </div>

            <div class="d-flex flex-wrap w-100">
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_USERNAME"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_USERNAME')?->value }}"
                        name="key[MAIL_USERNAME]" label="MAIL_USERNAME" />
                </x-form.input-div>

                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_PASSWORD"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_PASSWORD')?->value }}"
                        name="key[MAIL_PASSWORD]" label="MAIL_PASSWORD" />
                </x-form.input-div>
            </div>

            <div class="d-flex flex-wrap w-100">
                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_FROM_ADDRESS"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_FROM_ADDRESS')?->value }}"
                        name="key[MAIL_FROM_ADDRESS]" label="MAIL_FROM_ADDRESS" />
                </x-form.input-div>

                <x-form.input-div class="mb-10 w-100 px-2">
                    <x-form.input required :errors=$errors placeholder="MAIL_FROM_NAME"
                        value="{{ $notifications_settings_parameters->firstWhere('key', 'MAIL_FROM_NAME')?->value }}"
                        name="key[MAIL_FROM_NAME]" label="MAIL_FROM_NAME" />
                </x-form.input-div>
            </div>
            <x-form.form-buttons id="mail_settings_form" />
        </form>
    </x-cards.card>
</div>

<script>
    function mailSettingsForm() {
        $('#mail_settings_form').on('submit', function(e) {
            e.preventDefault();
            ajaxRequest('store-notification-settings-parameters', "POST", $(this).serializeArray()).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                            '</div>');
                    });
                });
        });
    }
</script>
