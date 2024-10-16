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

function ajaxRequest(url, method, data = {}) {
    data._token = window.csrfToken;
    $.ajax({
        url: url,
        data: data,
        dataType: "json",
        type: method,
        success: handleAjaxResponse,
        error: errorAlert
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
        dataTable.ajax.reload();
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