function establishmentForm(id, validationUrl) {
    const $form = $(`#${id}`);
    let saveButton = $(`#${id}_button`);
    checkErrors(saveButton);

    $form.find('input, select, input[type="file"]').on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });

    const select2Base = { width: '100%' };

    $form.find('select[name="parent_id"]').select2(select2Base);
    const $perpAcc = $form.find('#perpetual_inventory_account_id');
    if ($perpAcc.length) {
        $perpAcc.select2({
            ...select2Base,
            allowClear: true,
            placeholder: $perpAcc.data('placeholder') || '',
        });
    }

    $form.find('.select2-selection--single').css({ height: '38px', minHeight: '38px' });

    $('#is_main').on('change', function () {
        if ($(this).is(':checked')) {
            $(this).val(1);
        } else {
            $(this).val(0);
        }
    });

    $('#is_active').on("change", function () {
        if ($(this).is(':checked')) {
            $(this).val(1);
        } else {
            showAlert(Lang.get('general.disable_enable_main_est'),
                Lang.get('general.deactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (!t.isConfirmed) {                        
                        $('#is_active').prop('checked', true);
                    }
                });
        }
    });
}