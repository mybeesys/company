function initDashboardPermissionHints(root) {
    if (typeof bootstrap === 'undefined' || !bootstrap.Popover) {
        return;
    }

    const scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('[data-ems-perm-hint]').forEach(function (el) {
        const existing = bootstrap.Popover.getInstance(el);
        if (existing) {
            existing.dispose();
        }
        new bootstrap.Popover(el, {
            container: 'body',
            trigger: 'hover focus',
            html: true,
            sanitize: true
        });
    });
}

function dashboardRolePermissionsForm() {

    $(`input[name*="dashboard_permissions["][name*=".all."]`).on('change', function () {
        let nameParts = $(this).attr('name').replace('dashboard_permissions[', '').replace(']', '').split('.');
        let moduleName = nameParts[0];
        let action = nameParts[2];
        let isChecked = $(this).is(':checked');

        // Toggle checkboxes for the specific action
        $(`input[name^="dashboard_permissions[${moduleName}"]`).filter(`[name*=".${action}"]`).not(':disabled').prop('checked', isChecked);

        if (moduleName === 'screens') {
            $(this).closest('[data-ems-perm-root]')
                .find(`input[data-ems-companion][name="dashboard_permissions[screen_module.all.${action}]"]`)
                .not(':disabled')
                .prop('checked', isChecked);
        }

        const dependenciesMap = {
            delete: { enable: ['update', 'create', 'print', 'show', 'delete'], disable: [] },
            update: { enable: ['create', 'print', 'show', 'update'], disable: ['delete'] },
            create: { enable: ['print', 'show', 'create'], disable: ['delete', 'update'] },
            print: { enable: ['show', 'print'], disable: ['delete', 'update', 'create'] },
            show: { enable: ['show'], disable: ['delete', 'update', 'create', 'print'] }
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
            delete: { enable: ['update', 'create', 'print', 'show', 'delete'], disable: [] },
            update: { enable: ['create', 'print', 'show', 'update'], disable: ['delete'] },
            create: { enable: ['print', 'show', 'create'], disable: ['delete', 'update'] },
            print: { enable: ['show', 'print'], disable: ['delete', 'update', 'create'] },
            show: { enable: ['show'], disable: ['delete', 'update', 'create', 'print'] }
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
            toggleDependencies($(this), moduleName, ['update', 'create', 'print', 'show', 'delete'], [], permissionName);
        } else if (action === 'update') {
            toggleDependencies($(this), moduleName, ['create', 'print', 'show', 'update'], ['delete'], permissionName);
        } else if (action === 'create') {
            toggleDependencies($(this), moduleName, ['print', 'show', 'create'], ['delete', 'update'], permissionName);
        } else if (action === 'print') {
            toggleDependencies($(this), moduleName, ['show', 'print'], ['delete', 'update', 'create'], permissionName);
        } else if (action === 'show') {
            toggleDependencies($(this), moduleName, ['show'], ['delete', 'update', 'create', 'print'], permissionName);
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

        const actions = ['show', 'print', 'create', 'update', 'delete'];

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
        ['show', 'print', 'create', 'update', 'delete'].forEach(function (action) {
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

    initDashboardPermissionHints();
    emsPermissionsUi();
}

function emsPermissionsUi() {
    document.querySelectorAll('[data-ems-perm-root]').forEach(function (shell) {
        bindEmsPermissionShell(shell);
        refreshEmsPermissionCounts(shell);
    });
    bindRoleStatusCard();
}

function bindRoleStatusCard() {
    document.querySelectorAll('[data-ems-role-status]').forEach(function (card) {
        if (card.dataset.emsBound === '1') {
            return;
        }
        const input = card.querySelector('input[name="is_active"][type="checkbox"]');
        const label = card.querySelector('[data-ems-role-status-label]');
        if (!input) {
            return;
        }
        card.dataset.emsBound = '1';
        const sync = function () {
            card.classList.toggle('is-on', input.checked);
            if (label) {
                label.textContent = input.checked
                    ? (card.getAttribute('data-label-on') || '')
                    : (card.getAttribute('data-label-off') || '');
            }
        };
        input.addEventListener('change', sync);
        sync();
    });
}

function refreshEmsPermissionCounts(root) {
    const shells = root && root.matches && root.matches('[data-ems-perm-root]')
        ? [root]
        : (root && root.querySelectorAll
            ? Array.from(root.querySelectorAll('[data-ems-perm-root]'))
            : Array.from(document.querySelectorAll('[data-ems-perm-root]')));

    shells.forEach(function (shell) {
        const tpl = shell.getAttribute('data-ems-selected-tpl') || ':count';
        let total = 0;

        shell.querySelectorAll('[data-ems-module]').forEach(function (mod) {
            const count = emsCountablePermissionInputs(mod).filter(function (box) { return box.checked; }).length;
            total += count;
            const badge = mod.querySelector('[data-ems-module-count]');
            if (badge) {
                badge.textContent = String(count);
                badge.classList.toggle('has-selected', count > 0);
            }
            const id = mod.getAttribute('data-ems-module');
            const chip = shell.querySelector('[data-ems-chip="' + id + '"]');
            if (chip) {
                const chipCount = chip.querySelector('[data-ems-chip-count]');
                if (chipCount) {
                    chipCount.textContent = String(count);
                }
                chip.classList.toggle('has-selected', count > 0);
            }
            syncModuleSelectAll(mod);
        });

        const totalEl = shell.querySelector('[data-ems-total]');
        if (totalEl) {
            totalEl.textContent = tpl.replace(':count', String(total));
        }
    });
}

function bindPermissionLegend(shell) {
    const legend = shell.querySelector('[data-ems-legend]');
    if (!legend) {
        return;
    }

    const storageKey = 'emsPermLegendSeen';
    let seen = false;
    try {
        seen = window.localStorage.getItem(storageKey) === '1';
    } catch (e) {
        seen = false;
    }

    if (seen) {
        legend.remove();
        return;
    }

    legend.hidden = false;
    legend.classList.add('is-visible');
    try {
        window.localStorage.setItem(storageKey, '1');
    } catch (e) {
        // ignore quota / private mode
    }

    const close = legend.querySelector('[data-ems-legend-close]');
    if (close) {
        close.addEventListener('click', function () {
            legend.classList.remove('is-visible');
            legend.hidden = true;
        });
    }
}

function bindEmsPermissionShell(shell) {
    if (shell.dataset.emsBound === '1') {
        return;
    }
    shell.dataset.emsBound = '1';
    bindPermissionLegend(shell);

    const search = shell.querySelector('[data-ems-perm-search]');
    const empty = shell.querySelector('[data-ems-empty]');
    const expandAll = shell.querySelector('[data-ems-expand-all]');
    const collapseAll = shell.querySelector('[data-ems-collapse-all]');

    function setModuleOpen(mod, open) {
        const body = mod.querySelector('.ems-perm-module__body');
        const head = mod.querySelector('.ems-perm-module__head');
        if (!body || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            return;
        }
        const instance = bootstrap.Collapse.getOrCreateInstance(body, { toggle: false });
        if (open) {
            instance.show();
            head && head.classList.remove('collapsed');
        } else {
            instance.hide();
            head && head.classList.add('collapsed');
        }
    }

    function applySearch() {
        const q = (search && search.value ? search.value : '').trim().toLowerCase();
        let visibleModules = 0;

        shell.querySelectorAll('[data-ems-module]').forEach(function (mod) {
            const moduleHay = mod.getAttribute('data-ems-haystack') || '';
            const moduleMatch = !q || moduleHay.indexOf(q) !== -1;
            let visibleRows = 0;

            mod.querySelectorAll('[data-ems-row]').forEach(function (row) {
                const hay = row.getAttribute('data-ems-haystack') || '';
                const show = !q || moduleMatch || hay.indexOf(q) !== -1;
                row.classList.toggle('is-hidden', !show);
                if (show) {
                    visibleRows += 1;
                }
            });

            const showModule = visibleRows > 0;
            mod.classList.toggle('is-hidden', !showModule);
            const id = mod.getAttribute('data-ems-module');
            const chip = shell.querySelector('[data-ems-chip="' + id + '"]');
            if (chip) {
                chip.classList.toggle('d-none', !showModule);
            }
            if (showModule) {
                visibleModules += 1;
                if (q) {
                    setModuleOpen(mod, true);
                }
            }
        });

        if (empty) {
            empty.classList.toggle('is-visible', visibleModules === 0);
        }
    }

    if (search) {
        search.addEventListener('input', applySearch);
    }

    if (expandAll) {
        expandAll.addEventListener('click', function () {
            shell.querySelectorAll('[data-ems-module]:not(.is-hidden)').forEach(function (mod) {
                setModuleOpen(mod, true);
            });
        });
    }

    if (collapseAll) {
        collapseAll.addEventListener('click', function () {
            shell.querySelectorAll('[data-ems-module]').forEach(function (mod) {
                setModuleOpen(mod, false);
            });
        });
    }

    shell.querySelectorAll('[data-ems-chip]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            const id = chip.getAttribute('data-ems-chip');
            const mod = shell.querySelector('[data-ems-module="' + id + '"]');
            if (!mod || mod.classList.contains('is-hidden')) {
                return;
            }
            shell.querySelectorAll('[data-ems-chip]').forEach(function (other) {
                other.classList.toggle('is-active', other === chip);
            });
            setModuleOpen(mod, true);
            mod.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    shell.querySelectorAll('.ems-perm-module__head').forEach(function (head) {
        head.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                head.click();
            }
        });
    });

    shell.addEventListener('change', function (e) {
        if (e.target && e.target.matches('input.form-check-input')) {
            refreshEmsPermissionCounts(shell);
        }
    });

    shell.addEventListener('shown.bs.collapse', function () {
        initDashboardPermissionHints(shell);
    });

    bindModuleSelectAll(shell);
}

function emsCountablePermissionInputs(mod) {
    return Array.from(mod.querySelectorAll('input.form-check-input:not(:disabled):not([data-ems-master]):not([data-ems-companion])'));
}

function syncModuleSelectAll(mod) {
    const master = mod.querySelector('[data-ems-module-select-all]');
    if (!master) {
        return;
    }
    const boxes = emsCountablePermissionInputs(mod);
    if (boxes.length === 0) {
        master.checked = false;
        master.indeterminate = false;
        return;
    }
    const checked = boxes.filter(function (box) { return box.checked; }).length;
    master.checked = checked === boxes.length;
    master.indeterminate = checked > 0 && checked < boxes.length;
}

function setModulePermissions(mod, want) {
    const allBoxes = Array.from(mod.querySelectorAll('input[name*=".all."]:not(:disabled):not([data-ems-master])'));
    const order = want
        ? ['show', 'print', 'create', 'update', 'delete']
        : ['delete', 'update', 'create', 'print', 'show'];

    if (allBoxes.length) {
        order.forEach(function (action) {
            allBoxes.forEach(function (input) {
                if (input.name.indexOf('.all.' + action + ']') === -1) {
                    return;
                }
                if (input.checked !== want) {
                    input.checked = want;
                    $(input).trigger('change');
                }
            });
        });
        return;
    }

    mod.querySelectorAll('input.form-check-input:not(:disabled):not([data-ems-master])').forEach(function (input) {
        if (input.checked !== want) {
            input.checked = want;
            $(input).trigger('change');
        }
    });
}

function bindModuleSelectAll(shell) {
    shell.querySelectorAll('[data-ems-select-all-wrap]').forEach(function (wrap) {
        wrap.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        wrap.addEventListener('mousedown', function (e) {
            e.stopPropagation();
        });
    });

    shell.querySelectorAll('[data-ems-module-select-all]').forEach(function (master) {
        master.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        master.addEventListener('change', function (e) {
            e.stopPropagation();
            const mod = master.closest('[data-ems-module]');
            if (!mod) {
                return;
            }
            setModulePermissions(mod, master.checked);
            refreshEmsPermissionCounts(shell);
        });
    });
}

function fixedTableHeader() {
    emsPermissionsUi();
}