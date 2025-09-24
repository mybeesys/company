// A helper function to manage the permission checkboxes
function setPermissionsFromList(permissionIds, allPermissionsId) {
    const permissionsCheckboxContainer = $(
        "#employee_pos_permissions_edit_form"
    );

    // Uncheck all checkboxes first to reset the state
    permissionsCheckboxContainer
        .find('input[type="checkbox"]')
        .not(`#pos_select_all_permissions`)
        .prop("checked", false);

    // If the 'all permissions' ID is included, check all checkboxes
    if (permissionIds.includes(allPermissionsId)) {
        permissionsCheckboxContainer
            .find('input[type="checkbox"]')
            .prop("checked", true);
    } else {
        // Otherwise, check only the specific permissions provided
        permissionIds.forEach((id) => {
            permissionsCheckboxContainer
                .find(`input[value="${id}"]`)
                .prop("checked", true);
        });
        const allChecked =
            permissionsCheckboxContainer.find(
                'input[name^="pos_permissions"][value!="all"]'
            ).length ===
            permissionsCheckboxContainer.find(
                'input[name^="pos_permissions"][value!="all"]:checked'
            ).length;
        if (allChecked) {
            permissionsCheckboxContainer
                .find(`input[value="${allPermissionsId}"]`)
                .prop("checked", true);
        }
    }
}

// The main function to handle POS permissions assignment
function assignPosPermissionsToEmployee(getDataUrl, assignUrl) {
    const permissionsCheckboxContainer = $(
        "#employee_pos_permissions_edit_form"
    );

    // Event handler for clicking the 'edit pos permission' button
    $(document).on("click", ".edit-pos-permission-button", function (e) {
        e.preventDefault();
        const employeeId = $(this).data("id");

        // Fetch employee data including their current permissions and roles
        ajaxRequest(`${getDataUrl}/${employeeId}`, "GET", {}, false, true).done(
            function (response) {
                if (response.success) {
                    const employeeData = response.data;
                    const employeePermissions =
                        employeeData.employeePermissions;
                    const allPermissionsId = employeeData.allPermissionsId;
                    const posRoleIds = employeeData.pos_role_ids || [];
                    const selectElement = $("#pos_role_ids");

                    // 1. Set the role dropdown value first.
                    if (posRoleIds.length > 0) {
                        selectElement.val(posRoleIds);
                        // Trigger Select2 to update the displayed roles
                        if (selectElement.data("select2")) {
                            selectElement.trigger("change.select2");
                        }
                    } else {
                        // Reset the role dropdown if no roles are assigned
                        selectElement.val(null).trigger("change.select2");
                    }

                    // 2. Conditionally set permissions based on employee data or role data.
                    if (employeePermissions.length > 0) {
                        // If the employee has specific permissions, set them directly.
                        setPermissionsFromList(
                            employeePermissions,
                            allPermissionsId
                        );
                    } else if (posRoleIds.length > 0) {
                        // If the employee has no direct permissions but has roles,
                        // trigger the role change event to load permissions from the roles.
                        if (selectElement.data("select2")) {
                            selectElement.trigger("change");
                        }
                        setPermissionsFromList(
                            employeePermissions,
                            allPermissionsId
                        );
                    } else {
                        // Reset all permissions if no permissions or roles are assigned.
                        setPermissionsFromList([], allPermissionsId);
                    }

                    // Pass data to the form submission handler
                    assignPosPermissionsToEmployeeForm(
                        allPermissionsId,
                        employeeId,
                        assignUrl
                    );

                    // Open the modal
                    $("#employee_pos_permissions_edit").modal("toggle");
                }
            }
        );
    });

    // Event handler for changes in the multi-select roles dropdown
    $(document).on("change", "#pos_role_ids", function () {
        const roleIds = $(this).val() || [];
        if (roleIds.length > 0) {
            let combinedPermissions = new Set();
            let allPermissionsId = null;

            // Fetch permissions for all selected roles using Promise.all
            const requests = roleIds.map((roleId) => {
                return ajaxRequest(
                    `/permission/get-pos-role-permissions/${roleId}`,
                    "GET"
                );
            });
            Promise.all(requests).then((responses) => {
                responses.forEach((response) => {
                    if (response.allPermissionsId) {
                        allPermissionsId = response.allPermissionsId;
                    }
                    response.permissions.forEach((permissionId) =>
                        combinedPermissions.add(permissionId)
                    );
                });

                // Set checkboxes based on the combined permissions
                setPermissionsFromList(
                    [...combinedPermissions],
                    allPermissionsId
                );
            });
        } else {
            // If no roles are selected, uncheck all permission boxes
            permissionsCheckboxContainer
                .find('input[type="checkbox"]')
                .prop("checked", false);
        }
    });

    // Event handler for the 'select all permissions' checkbox
    $(document).on("change", "#pos_select_all_permissions", function () {
        const isChecked = $(this).is(":checked");
        permissionsCheckboxContainer
            .find('input[type="checkbox"]')
            .prop("checked", isChecked);
    });
}

// Function to handle the form submission for assigning permissions
function assignPosPermissionsToEmployeeForm(
    allPermissionsId,
    employeeId,
    assignUrl
) {
    const selectAllCheckbox = $(
        `input[type="checkbox"][value="${allPermissionsId}"], input[type="checkbox"][value="all"]`
    );

    // Event handler to manage the 'select all' checkbox state
    $(`input[type="checkbox"][value!="${allPermissionsId}"]`).on(
        "change",
        function (e) {
            if (!$(this).is(":checked")) {
                selectAllCheckbox.prop("checked", false);
            } else {
                const allChecked =
                    $('input[name^="pos_permissions"][value!="all"]').length ===
                    $('input[name^="pos_permissions"][value!="all"]:checked')
                        .length;
                if (allChecked) {
                    selectAllCheckbox.prop("checked", true);
                }
            }
        }
    );

    // Form submission handler
    $("#employee_pos_permissions_edit_form")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();

            let checkedPermissions = [];

            // If 'select all' is checked, get all permission IDs
            if (selectAllCheckbox.is(":checked")) {
                checkedPermissions = $(
                    'input[type="checkbox"][name^="pos_permissions"]'
                )
                    .map(function () {
                        return parseInt($(this).val(), 10);
                    })
                    .get();
                // Filter out NaN values
                checkedPermissions = checkedPermissions.filter(
                    (value) => !isNaN(value)
                );
            } else {
                // Otherwise, get only the manually checked permissions
                checkedPermissions = $(
                    'input[name^="pos_permissions"]:checked:not(:disabled)'
                )
                    .map(function () {
                        return parseInt($(this).val(), 10);
                    })
                    .get();
                // Filter out NaN values
                checkedPermissions = checkedPermissions.filter(
                    (value) => !isNaN(value)
                );
            }

            const url = assignUrl.replace(":id", employeeId);
            const selectedRoleIds = $("#pos_role_ids").val() || [];

            ajaxRequest(
                url,
                "PATCH",
                {
                    pos_permissions: checkedPermissions,
                    pos_role_ids: selectedRoleIds,
                },
                true,
                true
            );

            $("#employee_pos_permissions_edit").modal("toggle");
        });
}

// A helper function specifically for managing dashboard permission checkboxes
function setDashboardPermissionsFromList(permissionIds) {
    const permissionsCheckboxContainer = $(
        "#employee_dashboard_permissions_edit_form"
    );

    // Uncheck all checkboxes first to reset the state
    permissionsCheckboxContainer
        .find('input[type="checkbox"]')
        .prop("checked", false);

    // Check only the specific permissions provided
    permissionIds.forEach((id) => {
        permissionsCheckboxContainer
            .find(`input[value="${id}"]`)
            .prop("checked", true);
    });
}
function assignDashboardPermissionsToEmployee(getDataUrl, assignUrl) {
    const permissionsCheckboxContainer = $(
        "#employee_dashboard_permissions_edit_form"
    );

    $(document).on("click", ".edit-ems-permission-button", function (e) {
        e.preventDefault();
        const employeeId = $(this).data("id");

        ajaxRequest(`${getDataUrl}/${employeeId}`, "GET", {}, false, true).done(
            function (response) {
                if (response.success) {
                    const employeeData = response.data;
                    const userPermissions = employeeData.userPermissions || [];
                    const dashboardRoleIds =
                        employeeData.dashboard_role_ids || [];
                    const selectElement = $("#dashboard_role_ids");

                    if (dashboardRoleIds.length > 0) {
                        selectElement.val(dashboardRoleIds);
                        if (selectElement.data("select2")) {
                            selectElement.trigger("change.select2");
                        }
                    } else {
                        selectElement.val(null).trigger("change.select2");
                    }

                    if (userPermissions.length > 0) {
                        setDashboardPermissionsFromList(userPermissions);
                    } else if (dashboardRoleIds.length > 0) {
                        if (selectElement.data("select2")) {
                            selectElement.trigger("change");
                        }
                    } else {
                        setDashboardPermissionsFromList([]);
                    }

                    assignDashboardPermissionsToEmployeeForm(
                        employeeId,
                        assignUrl
                    );

                    $("#employee_dashboard_permissions_edit").modal("toggle");
                }
            }
        );
    });

    // Event handler for changes in the multi-select roles dropdown
    $(document).on("change", "#dashboard_role_ids", function () {
        const roleIds = $(this).val() || [];
        if (roleIds.length > 0) {
            let combinedPermissions = new Set();
            const requests = roleIds.map((roleId) => {
                return ajaxRequest(
                    `/permission/get-dashboard-role-permissions/${roleId}`,
                    "GET"
                );
            });
            Promise.all(requests).then((responses) => {
                responses.forEach((response) => {
                    response.permissions.forEach((permissionId) =>
                        combinedPermissions.add(permissionId)
                    );
                });
                setDashboardPermissionsFromList([...combinedPermissions]);
            });
        } else {
            permissionsCheckboxContainer
                .find('input[type="checkbox"]')
                .prop("checked", false);
        }
    });
}

function assignDashboardPermissionsToEmployeeForm(employeeId, assignUrl) {
    // Form submission handler
    $("#employee_dashboard_permissions_edit_form")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();

            // Get only the manually checked permissions
            let checkedPermissions = $(
                'input[name^="dashboard_permissions"]:checked:not(:disabled)'
            )
                .map(function () {
                    return parseInt($(this).val(), 10);
                })
                .get();

            // Filter out NaN values
            checkedPermissions = checkedPermissions.filter(
                (value) => !isNaN(value)
            );

            const url = assignUrl.replace(":id", employeeId);
            const selectedRoleIds = $("#dashboard_role_ids").val() || [];

            ajaxRequest(
                url,
                "PATCH",
                {
                    dashboard_permissions: checkedPermissions,
                    dashboard_role_ids: selectedRoleIds,
                },
                true,
                true
            );

            $("#employee_dashboard_permissions_edit").modal("toggle");
        });
}
