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

function ajaxRequest(url, method, data = {}, handleResponse = true, handleError = true) {
    data._token = window.csrfToken;

    const progressBar = $("#ajax-progress-bar");
    const progressBarInner = progressBar.find('.progress-bar');

    return $.ajax({
        url: url,
        type: method,
        data: data,
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            progressBar.show();
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