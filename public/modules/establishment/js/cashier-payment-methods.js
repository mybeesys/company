function initCashierPaymentMethods() {
    const root = document.getElementById('cashier_payment_methods_root');
    if (!root) {
        return;
    }

    const rowsContainer = document.getElementById('cashier_payment_rows');
    const template = document.getElementById('cashier_payment_row_template');
    const accountRowTemplate = document.getElementById('cashier_branch_account_row_template');
    const addBtn = document.getElementById('cashier_add_payment_row');

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function initAccountSelect2(scope) {
        $(scope).find('.select-2-cashier, .select-2-branch-account').each(function () {
            const $el = $(this);
            destroySelect2($el);
            $el.select2({
                allowClear: true,
                width: '100%',
                placeholder: $el.data('placeholder') || '',
                dropdownParent: $(root).closest('form')
            });
        });
    }

    function rowNamePrefix($row) {
        const name = $row.find('input[name*="[name_ar]"]').attr('name')
            || $row.find('select[name*="[establishment_ids]"]').attr('name')
            || '';

        return name
            .replace(/\[name_ar]$/, '')
            .replace(/\[establishment_ids]\[]$/, '');
    }

    function selectedBranchIds($assignment) {
        const values = $assignment.find('.select-2-branch-assign').val() || [];

        return values.map(String).filter(Boolean);
    }

    function branchLabel($assignment, estId) {
        const text = $assignment.find('.select-2-branch-assign option[value="' + estId + '"]').text();

        return (text || ('#' + estId)).trim();
    }

    function toggleAccountEmpty($assignment, isEmpty) {
        $assignment.find('[data-branch-account-empty]').toggleClass('d-none', !isEmpty);
    }

    function buildAccountRow(namePrefix, estId, branchName, selectedAccountId) {
        if (!accountRowTemplate) {
            return '';
        }

        const html = accountRowTemplate.innerHTML
            .replaceAll('__INDEX__', '0')
            .replaceAll('__EST_ID__', String(estId))
            .replaceAll('__BRANCH_NAME__', branchName);

        const $wrap = $('<div>').html(html);
        const $row = $wrap.children().first();
        $row.attr('data-establishment-id', String(estId));
        $row.find('.branch-account-name').text(branchName);
        $row.find('select').attr('name', namePrefix + '[branch_accounts][' + estId + ']');
        if (selectedAccountId) {
            $row.find('select').val(String(selectedAccountId));
        }

        return $row;
    }

    function syncBranchAccountRows($assignment) {
        if (!$assignment.length || !$assignment.is('[data-with-accounts]')) {
            return;
        }

        const $list = $assignment.find('[data-branch-account-list]');
        const selected = selectedBranchIds($assignment);
        const namePrefix = rowNamePrefix($assignment.closest('[data-cashier-row]'));
        const existing = {};

        $list.find('[data-branch-account-row]').each(function () {
            existing[String($(this).data('establishment-id'))] = $(this).find('select').val() || '';
        });

        $list.find('[data-branch-account-row]').each(function () {
            const estId = String($(this).data('establishment-id'));
            if (selected.includes(estId)) {
                return;
            }
            destroySelect2($(this).find('select'));
            $(this).remove();
        });

        selected.forEach(function (estId) {
            if ($list.find('[data-branch-account-row][data-establishment-id="' + estId + '"]').length) {
                return;
            }
            const $row = buildAccountRow(namePrefix, estId, branchLabel($assignment, estId), existing[estId] || '');
            $list.append($row);
            initAccountSelect2($row);
        });

        selected.forEach(function (estId) {
            $list.append($list.find('[data-branch-account-row][data-establishment-id="' + estId + '"]'));
        });

        toggleAccountEmpty($assignment, selected.length === 0);
    }

    function reindexRows() {
        $(rowsContainer).find('[data-cashier-row]').each(function (index) {
            // تحديث data-index في تاب الرسوم والأزرار
            $(this).find('[data-fees-pane]').attr('data-index', index);
            $(this).find('.pmf-add-fee-btn').attr('data-index', index);

            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) {
                    return;
                }
                $(this).attr(
                    'name',
                    name.replace(/cashier_payment_rows\[\d+]|cashier_payment_rows\[__INDEX__]/, 'cashier_payment_rows[' + index + ']')
                );
            });
        });
    }

    function clearRow($row) {
        $row.find('input[type="text"]').val('');
        $row.find('input[type="hidden"]').remove();
        $row.find('select').val(null).trigger('change');
    }

    addBtn?.addEventListener('click', function () {
        if (!template) {
            return;
        }
        const html = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        const $newRow = $(rowsContainer).children('[data-cashier-row]').last();
        reindexRows();
        initAccountSelect2($newRow);
        if (typeof window.initBranchAssignmentSelects === 'function') {
            window.initBranchAssignmentSelects($newRow);
            $newRow.find('.branch-assign-all').trigger('click');
        }
        syncBranchAccountRows($newRow.find('[data-branch-assignment]'));
    });

    // ── رسوم طريقة الدفع ──────────────────────────────────────────────────
    const feeRowTemplate = document.getElementById('cashier_payment_fee_row_template');

    function feeRowCount($methodRow) {
        return $methodRow.find('[data-fees-pane] .pmf-fee-row').length;
    }

    function updateFeesTabBadge($methodRow) {
        const count = feeRowCount($methodRow);
        $methodRow.find('.pmf-fees-tab-count').text(count);
    }

    function updateFeeUnitLabel($feeRow) {
        const feeType = $feeRow.find('.pmf-fee-type-select').val();
        const currency = window.appCurrencySymbol || '';
        $feeRow.find('.pmf-unit-label').text(feeType === '1' ? '%' : currency);
    }

    function reindexFeeRows($methodRow) {
        const methodIndex = $methodRow.find('[data-fees-pane]').data('index');
        $methodRow.find('[data-fees-pane] .pmf-fee-row').each(function (feeIdx) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) return;
                $(this).attr(
                    'name',
                    name.replace(
                        /cashier_payment_rows\[\d+]\[fees]\[\d+]/g,
                        'cashier_payment_rows[' + methodIndex + '][fees][' + feeIdx + ']'
                    )
                );
            });
        });
    }

    function updateEmptyHint($methodRow) {
        const $pane = $methodRow.find('[data-fees-pane]');
        const hasFees = $pane.find('.pmf-fee-row').length > 0;
        $pane.find('.pmf-empty-hint').toggleClass('d-none', hasFees);
    }

    $(rowsContainer).on('click', '.pmf-add-fee-btn', function () {
        if (!feeRowTemplate) return;
        const methodIndex = $(this).data('index');
        const $methodRow  = $(this).closest('[data-cashier-row]');
        const feeIndex    = feeRowCount($methodRow);
        const html = feeRowTemplate.innerHTML
            .replaceAll('__METHOD_INDEX__', String(methodIndex))
            .replaceAll('__FEE_INDEX__',    String(feeIndex));
        const $list = $methodRow.find('.pmf-fees-list');
        $list.append(html);
        reindexFeeRows($methodRow);
        updateFeesTabBadge($methodRow);
        updateEmptyHint($methodRow);
    });

    $(rowsContainer).on('click', '.pmf-remove-fee-btn', function () {
        const $methodRow = $(this).closest('[data-cashier-row]');
        $(this).closest('.pmf-fee-row').remove();
        reindexFeeRows($methodRow);
        updateFeesTabBadge($methodRow);
        updateEmptyHint($methodRow);
    });

    $(rowsContainer).on('change', '.pmf-fee-type-select', function () {
        updateFeeUnitLabel($(this).closest('.pmf-fee-row'));
    });

    // تهيئة أولية
    $(rowsContainer).find('[data-cashier-row]').each(function () {
        updateEmptyHint($(this));
        $(this).find('.pmf-fee-row').each(function () {
            updateFeeUnitLabel($(this));
        });
    });
    // ────────────────────────────────────────────────────────────────────

    $(rowsContainer).on('click', '.cashier-remove-row', function () {
        const $rows = $(rowsContainer).children('[data-cashier-row]');
        if ($rows.length <= 1) {
            clearRow($(this).closest('[data-cashier-row]'));
            return;
        }
        $(this).closest('[data-cashier-row]').remove();
        reindexRows();
    });

    $(rowsContainer).on('change', '.select-2-branch-assign', function () {
        syncBranchAccountRows($(this).closest('[data-branch-assignment]'));
    });

    initAccountSelect2(rowsContainer);
    $(rowsContainer).find('[data-branch-assignment][data-with-accounts]').each(function () {
        syncBranchAccountRows($(this));
    });

    $(rowsContainer).find('[data-cashier-row]').each(function () {
        if (!$(this).find('.is-invalid').length) {
            return;
        }
        const tab = $(this).find('a[href*="-assign-"]')[0];
        if (tab && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tab).show();
        } else {
            $(tab).trigger('click');
        }
    });
}
