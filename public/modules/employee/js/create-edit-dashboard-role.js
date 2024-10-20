function dashboardRolePermissionsForm() {

    $(`input[name*="permissions["][name*=".all."]`).on('change', function () {
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

    $(`input[name^="permissions["][name$="]"]:not([name*=".all."])`).on('change', function () {
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

    $(`input[name*="permissions["]:checked`).trigger('change');
}