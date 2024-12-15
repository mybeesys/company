function roleForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);

    $(`#${id} input`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });
}

function rolePermissionsForm(all = true) {
    $('table input[type="checkbox"][value!="all"]').on('change', function (e) {
        const selectAllCheckbox = $('input[type="checkbox"][value="all"]');
        if (!$(this).is(':checked')) {
            selectAllCheckbox.prop('checked', false);
        } else {
            const allChecked = $('input[name^="pos_permissions"][value!="all"]').length === $(
                'input[name^="pos_permissions"][value!="all"]:checked').length;
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
            $('input[type="checkbox"][value!="all"][name!="is_active"]').prop('disabled', true);
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