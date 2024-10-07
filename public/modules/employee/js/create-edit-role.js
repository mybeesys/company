function roleForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    saveButton.prop('disabled', true);

    $(`#${id} input`).on('change', function() {
        let input = $(this);
        validateField(input);
    });

    function validateField(input) {
        let field = input.attr('name');
        let formData = new FormData();
        formData.append(field, input.val());
        formData.append("_token", window.csrfToken);

        $.ajax({
            url: validationUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function() {
                input.siblings('.invalid-feedback ').remove();
                input.removeClass('is-invalid');
                checkErrors();
            },
            error: function(response) {
                input.siblings('.invalid-feedback').remove();
                input.removeClass('is-invalid');

                let errorMsg = response.responseJSON.errors[field];
                if (errorMsg) {
                    input.addClass('is-invalid');
                    input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
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