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

function dashboardRolePermissionsForm() {

    $(`input[name*="permissions["][name*=".all."]`).change(function () {
        let nameParts = $(this).attr('name').replace('permissions[', '').replace(']', '').split('.');
        let moduleName = nameParts[0];
        let action = nameParts[2];
        let isChecked = $(this).is(':checked');

        // Toggle checkboxes for the specific action
        $(`input[name^="permissions[${moduleName}"]`).filter(`[name*=".${action}"]`).prop('checked', isChecked);

        const dependenciesMap = {
            delete: { enable: ['edit', 'create', 'print', 'show', 'delete'], disable: [] },
            edit: { enable: ['create', 'print', 'show', 'edit'], disable: ['delete'] },
            create: { enable: ['print', 'show', 'create'], disable: ['delete', 'edit'] },
            print: { enable: ['show', 'print'], disable: ['delete', 'edit', 'create'] },
            show: { enable: ['show'], disable: ['delete', 'edit', 'create', 'print'] }
        };

        let { enable, disable } = dependenciesMap[action] || { enable: [], disable: [] };

        // Handle dependencies
        toggleAllDependencies($(this), moduleName, enable, disable);
    });

    $(`input[name^="permissions["][name$="]"]:not([name*=".all."])`).change(function () {
        let name = $(this).attr('name');
        let moduleName = name.split('.')[0].replace('permissions[', '');
        let permissionName = name.split('.')[1];
        let action = name.split('.')[2].replace(']', '');

        const dependenciesMap = {
            delete: { enable: ['edit', 'create', 'print', 'show', 'delete'], disable: [] },
            edit: { enable: ['create', 'print', 'show', 'edit'], disable: ['delete'] },
            create: { enable: ['print', 'show', 'create'], disable: ['delete', 'edit'] },
            print: { enable: ['show', 'print'], disable: ['delete', 'edit', 'create'] },
            show: { enable: ['show'], disable: ['delete', 'edit', 'create', 'print'] }
        };

        let { enable, disable } = dependenciesMap[action] || { enable: [], disable: [] };

        toggleDependencies($(this), moduleName, enable, disable, permissionName);
    });

    // Handle child permissions logic
    $(`input[name^="permissions["][name$="]"]:not([name*=".all."])`).change(function () {
        let name = $(this).attr('name');
        let moduleName = name.split('.')[0].replace('permissions[', '');
        let permissionName = name.split('.')[1];
        let action = name.split('.')[2].replace(']', '');

        if (action === 'delete') {
            toggleDependencies($(this), moduleName, ['edit', 'create', 'print', 'show', 'delete'], [], permissionName);
        } else if (action === 'edit') {
            toggleDependencies($(this), moduleName, ['create', 'print', 'show', 'edit'], ['delete'], permissionName);
        } else if (action === 'create') {
            toggleDependencies($(this), moduleName, ['print', 'show', 'create'], ['delete', 'edit'], permissionName);
        } else if (action === 'print') {
            toggleDependencies($(this), moduleName, ['show', 'print'], ['delete', 'edit', 'create'], permissionName);
        } else if (action === 'show') {
            toggleDependencies($(this), moduleName, ['show'], ['delete', 'edit', 'create', 'print'], permissionName);
        }
    });

    function toggleAllDependencies($checkbox, moduleName, dependencies, uncheckDependencies) {
        let isChecked = $checkbox.is(':checked');

        // Toggle dependencies
        dependencies.forEach(function (dependency) {
            let childCheckbox = $(`input[name="permissions[${moduleName}.all.${dependency}]"]`);

            if (isChecked) {
                $(`input[name^="permissions[${moduleName}"]`).filter(`[name*=".${dependency}"]`).prop('checked', true);
                childCheckbox.prop('checked', true);
            } else {
                uncheckDependencies.forEach(function (dep) {
                    let uncheckAll = $(`input[name="permissions[${moduleName}.all.${dep}]"]`);
                    let uncheck = $(`input[name^="permissions[${moduleName}."][name*=".${dep}"]`);
                    uncheckAll.prop('checked', false);
                    uncheck.prop('checked', false);
                });
            }
        });

        // Handle the 'select all' state for the module
        handleSelectAll(moduleName);
    }

    function toggleDependencies($checkbox, moduleName, dependencies, uncheckDependencies, permissionName) {
        let isChecked = $checkbox.is(':checked');

        const actions = ['show', 'print', 'create', 'edit', 'delete'];

        dependencies.forEach(function (dependency) {
            let childCheckbox = $(`input[name="permissions[${moduleName}.${permissionName}.${dependency}]"]`);
            let childAllCheckbox = $(`input[name="permissions[${moduleName}.all.${dependency}]"]`);

            actions.forEach(action => {
                let allActionCheckbox = $(`input[name="permissions[${moduleName}.all.${action}]"]`);
                const allActionChecked = $(`input[name^="permissions[${moduleName}."][name$=".${action}]"]:not([name*=".all."])`).length ===
                    $(`input[name^="permissions[${moduleName}."][name$=".${action}]"]:checked:not([name*=".all."])`).length;

                if (allActionChecked) {
                    allActionCheckbox.prop('checked', true);
                }
            });

            if (isChecked) {
                childCheckbox.prop('checked', true);
            } else {
                uncheckDependencies.forEach(function (dep) {
                    let uncheckAll = $(`input[name="permissions[${moduleName}.all.${dep}]"]`);
                    let uncheck = $(`input[name="permissions[${moduleName}.${permissionName}.${dep}]"]`);
                    uncheckAll.prop('checked', false);
                    uncheck.prop('checked', false);
                });
                childAllCheckbox.prop('checked', false);
            }
        });
    }
    // Function to manage 'select all' checkbox state based on child checkboxes
    function handleSelectAll(moduleName) {
        ['show', 'print', 'create', 'edit', 'delete'].forEach(function (action) {
            let allChecked = true;
            $(`input[name^="permissions[${moduleName}"]`).each(function () {
                if ($(this).attr('name').includes(action) && !$(this).is(':checked')) {
                    allChecked = false;
                }
            });
            $(`input[name="permissions[${moduleName}.all.${action}"]`).prop('checked', allChecked);
        });
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