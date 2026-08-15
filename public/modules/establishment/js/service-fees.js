function initEstablishmentServiceFees() {
    const root = document.getElementById('service_fees_root');
    if (!root) {
        return;
    }

    const rowsContainer = document.getElementById('service_fee_rows');
    const template = document.getElementById('service_fee_row_template');
    const addBtn = document.getElementById('service_fee_add_row');
    const locale = (document.documentElement.lang || 'ar').toLowerCase();

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function initSelect2(scope) {
        $(scope).find('.select-2-service-fee').each(function () {
            const $el = $(this);
            destroySelect2($el);
            $el.select2({
                allowClear: $el.data('allow-clear') === true || $el.data('allow-clear') === 'true' || $el.prop('multiple'),
                width: '100%',
                placeholder: $el.data('placeholder') || '',
                dropdownParent: $(root).closest('form')
            });
        });
    }

    function getBranchPaymentMethods() {
        const methods = [];
        const seen = new Set();

        $('[data-cashier-row]').each(function () {
            const id = $(this).find('input[name*="[id]"]').val();
            const nameAr = String($(this).find('input[name*="[name_ar]"]').val() || '').trim();
            const nameEn = String($(this).find('input[name*="[name_en]"]').val() || '').trim();
            const label = locale.indexOf('ar') === 0 ? (nameAr || nameEn) : (nameEn || nameAr);
            if (!id || !label || seen.has(String(id))) {
                return;
            }

            seen.add(String(id));
            methods.push({ value: String(id), label: label });
        });

        return methods;
    }

    function fillBranchPaymentSelect($select, methods) {
        const current = $select.val();
        const placeholder = $select.data('placeholder') || '';
        $select.empty().append(new Option(placeholder, '', false, false));
        methods.forEach(function (method) {
            $select.append(new Option(method.label, method.value, false, String(current) === String(method.value)));
        });
    }

    function refreshBranchPaymentSelects(scope) {
        const methods = getBranchPaymentMethods();
        const $scope = scope ? $(scope) : $(rowsContainer);

        $scope.find('.service-fee-branch-payment').each(function () {
            const $select = $(this);
            fillBranchPaymentSelect($select, methods);
            $select.closest('[data-auto-apply]').find('.service-fee-branch-payment-empty')
                .toggleClass('d-none', methods.length > 0);
        });
    }

    function toggleAutoApply($row) {
        const type = String($row.find('.service-fee-auto-apply-type').val() ?? '');
        $row.find('.service-fee-auto-apply-field').each(function () {
            const match = String($(this).data('auto-apply')) === type;
            $(this).toggle(match);
            if (!match) {
                $(this).find('input:not([type="hidden"]):not([type="checkbox"])').val('');
                $(this).find('select').not('.service-fee-branch-payment').val(null).trigger('change');
            } else if (String($(this).data('auto-apply')) === '2') {
                refreshBranchPaymentSelects($row);
            }
            if (match) {
                initSelect2(this);
            }
        });
    }

    function reindexRows() {
        $(rowsContainer).find('[data-service-fee-row]').each(function (index) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) {
                    return;
                }
                $(this).attr(
                    'name',
                    name.replace(/service_fee_rows\[\d+\]|service_fee_rows\[__INDEX__\]/, 'service_fee_rows[' + index + ']')
                );
            });
        });
    }

    function clearRow($row) {
        $row.find('input[type="text"], input[type="number"], input[type="datetime-local"]').val('');
        $row.find('input[type="hidden"][name*="[id]"]').remove();
        $row.find('input[type="checkbox"][name*="[active]"]').prop('checked', true);
        $row.find('input[type="checkbox"][name*="[taxable]"]').prop('checked', false);
        $row.find('select').each(function () {
            if ($(this).hasClass('service-fee-type')) {
                $(this).val('0');
            } else if ($(this).attr('name') && $(this).attr('name').includes('[application_type]')) {
                $(this).val('1');
            } else if ($(this).attr('name') && $(this).attr('name').includes('[calculation_method]')) {
                $(this).val('0');
            } else {
                $(this).val(null);
            }
        });
        toggleAutoApply($row);
        $row.find('select').trigger('change');
    }

    addBtn?.addEventListener('click', function () {
        if (!template) {
            return;
        }
        const html = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        const $newRow = $(rowsContainer).children('[data-service-fee-row]').last();
        reindexRows();
        toggleAutoApply($newRow);
        initSelect2($newRow);
    });

    $(rowsContainer).on('click', '.service-fee-remove-row', function () {
        const $rows = $(rowsContainer).children('[data-service-fee-row]');
        if ($rows.length <= 1) {
            clearRow($(this).closest('[data-service-fee-row]'));
            return;
        }
        $(this).closest('[data-service-fee-row]').remove();
        reindexRows();
    });

    $(rowsContainer).on('change', '.service-fee-auto-apply-type', function () {
        toggleAutoApply($(this).closest('[data-service-fee-row]'));
    });

    $(rowsContainer).find('[data-service-fee-row]').each(function () {
        toggleAutoApply($(this));
    });

    initSelect2(rowsContainer);

    $('a[href="#establishment_service_fees_tab"]').on('shown.bs.tab', function () {
        refreshBranchPaymentSelects();
        initSelect2(rowsContainer);
    });
}
