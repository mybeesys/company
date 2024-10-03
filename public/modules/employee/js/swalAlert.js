function showAlert(text, confirmButtonText, cancelButtonText = '', confirmButton = 'btn-danger', cancelButton = false, icon                         ) {
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