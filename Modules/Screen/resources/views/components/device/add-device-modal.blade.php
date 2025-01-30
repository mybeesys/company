<x-general.modal module="screen" id='add_device_modal' title='add_device' class='mw-600px'>
    <div class="d-flex flex-wrap gap-4">
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors placeholder="{{ __('sales::fields.name') }}" value=""
                name="code" :label="__('sales::fields.name')" />
        </x-form.input-div>
    </div>
</x-general.modal>

<script>
    function addDeviceModal() {
        $('#add_device_modal_form').on('submit', function(e) {
            e.preventDefault();
            let data = $(this).serializeArray();

            data.push({
                name: "_token",
                value: window.csrfToken
            });
            ajaxRequest("{{ route('devices.store') }}", "POST", data).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' +
                            value +
                            '</div>');
                    });
                }).done(function(response) {
                $('#add_device_modal').modal('toggle');
                deviceDataTable.ajax.reload();

                let newOption = new Option(response.data.name, response.data.id, true, true);
                $('select[name="devices"]').append(newOption).trigger('change');
            });
        });
    }
</script>
