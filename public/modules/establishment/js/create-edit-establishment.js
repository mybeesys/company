function establishmentForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    checkErrors(saveButton);

    $(`#${id} input, #${id} select, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });
}