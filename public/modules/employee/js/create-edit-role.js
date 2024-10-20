function roleForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    saveButton.prop('disabled', true);

    $(`#${id} input`).on('change', function () {
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
            success: function () {
                input.siblings('.invalid-feedback ').remove();
                input.removeClass('is-invalid');
                checkErrors();
            },
            error: function (response) {
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

function rolePermissionsForm() {
    $('input[type="checkbox"][value!="all"]').on('change', function (e) {
        const selectAllCheckbox = $('input[type="checkbox"][value="all"]');
        if (!$(this).is(':checked')) {
            selectAllCheckbox.prop('checked', false);
        } else {
            const allChecked = $('input[name^="permissions"][value!="all"]').length === $(
                'input[name^="permissions"][value!="all"]:checked').length;
            if (allChecked) {
                selectAllCheckbox.prop('checked', true);
            }
        }
    });

    $('form').on('submit', function (event) {
        const selectAllCheckbox = $('input[type="checkbox"][value="all"]');

        if (selectAllCheckbox.is(':checked')) {
            const dataId = $('input[type="checkbox"][value="all"]').data('id');
            const selectAllPermissionId = dataId;
            $('input[type="checkbox"][value!="all"]').prop('disabled', true);
            selectAllCheckbox.val(selectAllPermissionId);
        }
    });

    dataTable = $('#role-permission-table').DataTable({
        paging: false,
        info: false,
        ordering: false,
        scrollX: false,
        scrollY: false,
    });
    handleSearchDatatable();
}