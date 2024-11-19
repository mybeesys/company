function assignPosPermissionsToEmployee(getDataUrl, assignUrl) {
    $(document).on('click', '.edit-pos-permission-button', function (e) {
        e.preventDefault();
        const employeeId = $(this).data('id');

        ajaxRequest(`${getDataUrl}/${employeeId}`, 'GET', {},
            false, true)
            .done(function (response) {
                if (response.success) {
                    const employeeData = response.data;
                    const employeePermissions = employeeData.employeePermissions;
                    const allPermissionsId = employeeData.allPermissionsId;

                    $('#employee_pos_permissions_edit_form').find('input[name^="pos_permissions"]')
                        .each(
                            function () {
                                const permissionId = $(this).val();
                                $(this).prop('checked', employeePermissions.includes(parseInt(
                                    permissionId)) || employeePermissions.includes(
                                        allPermissionsId));
                                $(this).prop('disabled', false);
                            });
                    assignPosPermissionsToEmployeeForm(allPermissionsId, employeeId, assignUrl);
                    $('#employee_pos_permissions_edit').modal('toggle');
                }
            });
    });
}

function assignPosPermissionsToEmployeeForm(allPermissionsId, employeeId, assignUrl) {
    const selectAllCheckbox = $(
        `input[type="checkbox"][value="${allPermissionsId}"], input[type="checkbox"][value="all"]`);

    $(`input[type="checkbox"][value!="${allPermissionsId}"]`).on('change', function (e) {
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

    $('#employee_pos_permissions_edit_form').off('submit').on('submit', function (e) {
        e.preventDefault();

        if (selectAllCheckbox.is(':checked')) {
            $('input[type="checkbox"][value!="all"]').prop('disabled', true);
            selectAllCheckbox.val(allPermissionsId);
        }
        const checkedPermissions = $('input[name^="pos_permissions"]:checked:not(:disabled)').map(
            function () {
                return $(this).val();
            }).get();

        const url = assignUrl.replace(':id', employeeId);
        ajaxRequest(url, 'PATCH', {
            pos_permissions: checkedPermissions,
        }, true, true);

        $('#employee_pos_permissions_edit').modal('toggle');
    });

}



function assignDashboardPermissionsToEmployee(getDataUrl, assignUrl) {

    $(document).on('click', '.edit-ems-permission-button', function (e) {
        e.preventDefault();
        const employeeId = $(this).data('id');
        $("#employee_dashboard_permissions_edit_form #employee_id").val(employeeId);

        ajaxRequest(`${getDataUrl}/${employeeId}`,
            'GET', {}, false, true)
            .done(function (response) {
                if (response.success) {
                    const employeeData = response.data;
                    const userPermissions = employeeData.userPermissions;

                    $('#employee_dashboard_permissions_edit_form').find(
                        'input[name^="dashboard_permissions"]')
                        .each(
                            function () {
                                const permissionId = $(this).val();
                                $(this).prop('checked', userPermissions.includes(parseInt(
                                    permissionId)));
                            });
                    assignDashboardPermissionsToEmployeeForm(employeeId, assignUrl);
                    $(`input[name*="dashboard_permissions["]:checked`).trigger('change');
                    $('#employee_dashboard_permissions_edit').modal('toggle');
                }
            });
    });
}

function assignDashboardPermissionsToEmployeeForm(employeeId, assignUrl) {

    $('#employee_dashboard_permissions_edit_form').off('submit').on('submit', function (e) {
        e.preventDefault();
        const url = assignUrl.replace(':id', employeeId);

        const checkedPermissions = {};
        $('input[name^="dashboard_permissions"]:checked:not(:disabled)').each(function () {
            const name = $(this).attr('name');
            const value = $(this).val();
            const key = name.match(/\[(.*?)\]/)[1];
            checkedPermissions[key] = value;
        });

        ajaxRequest(url, 'PATCH', {
            dashboard_permissions: checkedPermissions,
        }, true, true);

        $('#employee_dashboard_permissions_edit').modal('toggle');
    });

}