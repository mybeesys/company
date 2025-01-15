function showAlert(text, confirmButtonText, cancelButtonText = '', confirmButton = 'btn-danger', cancelButton = false, icon) {
    return Swal.fire({
        text: text,
        icon: icon,
        showCancelButton: cancelButton,
        buttonsStyling: false,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
        customClass: {
            confirmButton: `btn fw-bold ${confirmButton}`,
            cancelButton: "btn fw-bold btn-active-light-primary"
        }
    });
}

function validateField(input, validationUrl, saveButton) {
    let field = input.attr('name');
    let data;
    if (input[0].files) {
        data = new FormData();
        data.append(field, input[0].files[0]);
        data.append("_token", window.csrfToken);
    } else {
        data = {
            [field]: input.val(),
            "_token": window.csrfToken
        };
    }
    ajaxRequest(validationUrl, 'POST', data, false, false, false).done(function () {
        input.siblings('.invalid-feedback ').remove();
        input.removeClass('is-invalid');
        input.siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
        $('#image_error').removeClass('d-block');
        checkErrors(saveButton);
    }).fail(function (response) {
        input.siblings('.invalid-feedback').remove();
        input.removeClass('is-invalid');
        input.siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
        $('#image_error').removeClass('d-block');
        if (response.responseJSON) {
            let errorMsg = response.responseJSON.errors[field];
            if (errorMsg) {
                input.addClass('is-invalid');
                input.siblings('.select2-container').find('.select2-selection').addClass('is-invalid');
                if (input.attr('type') === 'file') {
                    input.closest('div').after(
                        '<div class="invalid-feedback d-block" id="image_error">' +
                        errorMsg[0] + '</div>');
                } else {
                    input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
                }
            }
        }
        checkErrors(saveButton);
    });
}

function ajaxRequest(url, method, data = {}, handleResponse = true, handleError = true, showProgressBar = true) {
    data._token = window.csrfToken;

    const progressBar = $("#ajax-progress-bar");
    const progressBarInner = progressBar.find('.progress-bar');

    return $.ajax({
        url: url,
        type: method,
        data: data,
        contentType: data instanceof FormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
        processData: !(data instanceof FormData),
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            if (showProgressBar) {
                progressBar.show();
            }
            progressBarInner.css("width", "0%");
            progressBarInner.stop().animate({
                width: "5%"
            }, {
                duration: 100,
                easing: 'linear',
                step: function (now) {
                    progressBarInner.attr("aria-valuenow", Math.ceil(now));
                }
            });

            return xhr;
        },
        beforeSend: function () {
            progressBarInner.css("width", "0%");
        },
        complete: function () {
            progressBarInner.stop().animate({
                width: "100%"
            }, 300, function () {
                setTimeout(() => {
                    progressBar.hide();
                    progressBarInner.css("width",
                        "0%");
                }, 200);
            });
        },
        success: function (response) {
            if (handleResponse) {
                handleAjaxResponse(response)
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            if (handleError) {
                errorAlert()
            }
        }
    });
}

function handleAjaxError(xhr, status, error) {
    errorAlert;
}

function handleAjaxResponse(response) {
    if (response.error) {
        errorAlert;
    } else {
        showAlert(response.message, Lang.get('general.close'), undefined, "btn-primary", false,
            "success");
    }
}

const errorAlert = function () {
    return showAlert(
        Lang.get('responses.something_wrong_happened'),
        Lang.get('general.try_again'),
        undefined, undefined,
        false, "error"
    );
};

function checkErrors(saveButton) {
    if ($('.is-invalid').length > 0) {
        saveButton.prop('disabled', true);
    } else {
        saveButton.prop('disabled', false);
    }
}

function handleImageInput(id, name) {

    const $imageInputElement = $(`#${id}`);
    const imageInput = KTImageInput.getInstance($imageInputElement[0]);
    const $hiddenInput = $(`input[name="${name}_old"]`);


    // Check if there is an initial image
    const initialImage = $imageInputElement.data("initial-image");
    const $imageWrapper = $imageInputElement.find(".image-input-wrapper");

    if (initialImage && imageInput) {
        // Set the uploaded state manually
        $imageWrapper.css("background-image", `url('${initialImage}')`);

        // Show the cancel button
        $imageInputElement.removeClass("image-input-empty").addClass("image-input-changed");
    } else {
        $imageWrapper.css(
            "background-image",
            "url('/assets/media/svg/files/blank-image.svg')"
        );
    }

    imageInput.on("kt.imageinput.canceled", function () {
        $imageWrapper.css(
            "background-image",
            "url('/assets/media/svg/files/blank-image.svg')"
        );
        $imageInputElement.addClass("image-input-empty").removeClass("image-input-changed");

        if ($hiddenInput.length) {
            $hiddenInput.val("0");
        }
    });
}

function selectDeselectAll(selectAllBtn, deselectAllBtn, selectElement) {
    selectAllBtn.on('click', function () {
        $(selectElement).select2('destroy');
        $(selectElement).select2();
        let allValues = $(`${selectElement} option`).map(
            function () {
                return $(this).val();
            }).get().filter(function (value) {
                return value !== '';
            });
        $(selectElement).val(allValues).trigger(
            'change');
    });

    deselectAllBtn.on('click', function () {
        $(selectElement).select2('destroy');
        $(selectElement).select2();
        $(selectElement).val(null).trigger('change');
    });
}