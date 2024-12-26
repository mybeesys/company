function establishmentForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    checkErrors(saveButton);

    $(`#${id} input, #${id} select, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });

    $('select[name="parent_id"]').select2();

    $('.select2-selection.select2-selection--single').attr('style', function (i, style) {
        return 'height: 36.05px !important;  min-height: 36.05px !important;';
    });

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