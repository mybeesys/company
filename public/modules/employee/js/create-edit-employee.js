function form(id, validationUrl, generatePinUrl) {
    let saveButton = $(`#${id}_button`);
    saveButton.prop('disabled', true);

    $('#isActive').on("change", function () {
        if ($(this).is(':checked')) {
            $(this).val(1);
        } else {
            showAlert(Lang.get('responses.change_status_warning'),
                Lang.get('general.diactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (t.isConfirmed) {
                        $(this).val(1);
                    } else {
                        $(this).val(0);
                        $('#isActive').prop('checked', true);
                    }
                });
        }
    });

    $(`#${id} input, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input);
    });

    $('#generate_pin').on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url: generatePinUrl,
            type: 'GET',
            success: function (response) {
                $('#PIN').val(response.data);
            },
            error: function () {
                showAlert(Lang.get('responses.something_wrong_happened'),
                    Lang.get('general.try_again'),
                    undefined, undefined,
                    false, "error");
            }
        });
    });

    function validateField(input) {
        let field = input.attr('name');
        let formData = new FormData();
        formData.append(field, input[0].files ? input[0].files[0] : input.val());
        formData.append("_token", window.csrfToken);

        $.ajax({
            url: validationUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                input.siblings('.invalid-feedback ').remove();
                input.removeClass('is-invalid');
                $('#image_error').removeClass('d-block');
                checkErrors();
            },
            error: function (response) {
                input.siblings('.invalid-feedback').remove();
                input.removeClass('is-invalid');
                $('#image_error').removeClass('d-block');

                let errorMsg = response.responseJSON.errors[field];
                if (errorMsg) {
                    input.addClass('is-invalid');
                    if (input.attr('type') === 'file') {
                        input.closest('div').after(
                            '<div class="invalid-feedback d-block" id="image_error">' +
                            errorMsg[0] + '</div>');
                    } else {
                        input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
                    }
                }
                checkErrors();
            }
        });
    }

    function checkErrors() {
        if ($('.is-invalid').length > 0) {
            saveButton.prop('disabled', true);
        } else {
            saveButton.prop('disabled', false);
        }
    }
}