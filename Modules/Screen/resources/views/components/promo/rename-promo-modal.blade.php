<x-general.modal module="screen" id='rename_promo_modal' title='rename_promo' class='mw-600px'>
    <div class="d-flex flex-wrap gap-4">
        <x-form.input-div class="mb-10 w-100 px-2">
            <x-form.input required :errors=$errors placeholder="{{ __('sales::fields.name') }}" value=""
                name="name" :label="__('sales::fields.name')" />
            <input type="hidden" name="id">
        </x-form.input-div>
    </div>
</x-general.modal>

<script>
    function renameModal() {
        $('#rename_promo_modal_form').on('submit', function(e) {
            e.preventDefault();
            let data = $(this).serializeArray();
            const id = $(this).find('input[name="id"]').val();

            const url = "{{ route('promos.update', ['promo' => ':id']) }}".replace(':id', id);
            data.push({
                name: "_token",
                value: window.csrfToken
            });
            ajaxRequest(url, "PATCH", data).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' +
                            value +
                            '</div>');
                    });
                }).done(function() {
                $('#rename_promo_modal').modal('toggle');
                promoDataTable.ajax.reload();
            });
        });
    }
</script>
