function dashboardRolePermissionsForm() {

    $(`input[name*="dashboard_permissions["][name*=".all."]`).on('change', function () {
        let nameParts = $(this).attr('name').replace('dashboard_permissions[', '').replace(']', '').split('.');
        let moduleName = nameParts[0];
        let action = nameParts[2];
        let isChecked = $(this).is(':checked');

        // Toggle checkboxes for the specific action
        $(`input[name^="dashboard_permissions[${moduleName}"]`).filter(`[name*=".${action}"]`).not(':disabled').prop('checked', isChecked);

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

    $(`input[name^="dashboard_permissions["][name$="]"]:not([name*=".all."])`).on('change', function () {
        let name = $(this).attr('name');
        let moduleName = name.split('.')[0].replace('dashboard_permissions[', '');
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
    $(`input[name^="dashboard_permissions["][name$="]"]:not([name*=".all."])`).on('change', function () {
        let name = $(this).attr('name');
        let moduleName = name.split('.')[0].replace('dashboard_permissions[', '');
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
            let childCheckbox = $(`input[name="dashboard_permissions[${moduleName}.all.${dependency}]"]`).not(':disabled');

            if (isChecked) {
                $(`input[name^="dashboard_permissions[${moduleName}"]`).filter(`[name*=".${dependency}"]`).not(':disabled').prop('checked', true);
                childCheckbox.prop('checked', true);
            } else {
                uncheckDependencies.forEach(function (dep) {
                    let uncheckAll = $(`input[name="dashboard_permissions[${moduleName}.all.${dep}]"]`);
                    let uncheck = $(`input[name^="dashboard_permissions[${moduleName}."][name*=".${dep}"]`);
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
            let childCheckbox = $(`input[name="dashboard_permissions[${moduleName}.${permissionName}.${dependency}]"]`).not(':disabled');
            let childAllCheckbox = $(`input[name="dashboard_permissions[${moduleName}.all.${dependency}]"]`).not(':disabled');

            actions.forEach(action => {
                let allActionCheckbox = $(`input[name="dashboard_permissions[${moduleName}.all.${action}]"]`);
                const allActionChecked = $(`input[name^="dashboard_permissions[${moduleName}."][name$=".${action}]"]:not([name*=".all."])`).not(':disabled').length ===
                    $(`input[name^="dashboard_permissions[${moduleName}."][name$=".${action}]"]:checked:not([name*=".all."])`).not(':disabled').length;

                if (allActionChecked) {
                    allActionCheckbox.not(':disabled').prop('checked', true);
                }
            });

            if (isChecked) {
                childCheckbox.not(':disabled').prop('checked', true);
            } else {
                uncheckDependencies.forEach(function (dep) {
                    let uncheckAll = $(`input[name="dashboard_permissions[${moduleName}.all.${dep}]"]`);
                    let uncheck = $(`input[name="dashboard_permissions[${moduleName}.${permissionName}.${dep}]"]`);
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
            $(`input[name^="dashboard_permissions[${moduleName}"]`).each(function () {
                if ($(this).attr('name').includes(action) && !$(this).is(':checked')) {
                    allChecked = false;
                }
            });
            $(`input[name="dashboard_permissions[${moduleName}.all.${action}"]`).not(':disabled').prop('checked', allChecked);
        });
    }

    $(`input[name*="dashboard_permissions["]:checked`).trigger('change');
}