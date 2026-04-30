@props(['establishments'])
<x-general.modal module="screen" id='add_device_modal' title='add_device' class='mw-600px'>
    <style>
        #add_device_modal .modal-content {
            border-radius: 16px;
        }

        #add_device_modal .device-modal-hint {
            border-radius: 12px;
            border: 1px solid rgba(54, 153, 255, 0.22);
            background: linear-gradient(135deg, rgba(54, 153, 255, 0.09), rgba(137, 80, 252, 0.06));
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
        }
    </style>
    <div class="device-modal-hint d-flex align-items-start gap-3">
        <span class="text-primary pt-1"><i class="fas fa-circle-info"></i></span>
        <span class="text-gray-700 fs-7 lh-lg">@lang('screen::general.device_modal_hint')</span>
    </div>
    <div class="d-flex flex-wrap gap-4">
        <input type="hidden" name="id" value="">
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors placeholder="{{ __('sales::fields.name') }}" value=""
                name="code" :label="__('sales::fields.name')" />
        </x-form.input-div>
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.select name="establishment_id" :label="__('screen::general.branch')" :options="$establishments"
                :errors="$errors" data_allow_clear="false" required></x-form.select>
        </x-form.input-div>
    </div>
</x-general.modal>

<script>
    function addDeviceModal() {
        $('#add_device_modal_form').on('submit', function(e) {
            e.preventDefault();
            $('.invalid-feedback').remove();
            $('.is-invalid').removeClass('is-invalid');
            let data = $(this).serializeArray();
            const id = $('#add_device_modal_form [name="id"]').val();
            const isEdit = !!id;

            data.push({
                name: "_token",
                value: window.csrfToken
            });
            if (isEdit) {
                data.push({
                    name: "_method",
                    value: "PATCH"
                });
            }
            ajaxRequest(isEdit ? `{{ url('/device') }}/${id}` : "{{ route('devices.store') }}", "POST", data).fail(
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
                const savedId = $('#add_device_modal_form [name="id"]').val();
                $('#add_device_modal_form [name="id"]').val('');

                let $devices = $('select[name="devices"]');
                if (response?.data?.id) {
                    let newOption = new Option(response.data.name, response.data.id, true, true);
                    if (!$devices.find(`option[value="${response.data.id}"]`).length) {
                        $devices.append(newOption);
                    }
                } else if (savedId) {
                    $devices.find(`option[value="${savedId}"]`).text($('#add_device_modal_form [name="code"]').val());
                }
                $devices.trigger('change');
            });
        });
    }
</script>
