function initInternalConsumptionTypes() {
    const root = document.getElementById('internal_consumption_types_root');
    if (!root) {
        return;
    }

    const rowsContainer = document.getElementById('internal_consumption_rows');
    const template = document.getElementById('internal_consumption_row_template');
    const addBtn = document.getElementById('internal_consumption_add_row');

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function initSelect2(scope) {
        $(scope).find('.select-2-internal-consumption').each(function () {
            const $el = $(this);
            destroySelect2($el);
            $el.select2({
                allowClear: true,
                width: '100%',
                dropdownParent: $(root).closest('form')
            });
        });
    }

    function toggleValueField($row) {
        const valueType = $row.find('.internal-consumption-value-type').val();
        const $input = $row.find('.internal-consumption-value-input');

        if (valueType === 'cost') {
            $input.prop('disabled', true).val('').attr('placeholder', '—');
        } else {
            $input.prop('disabled', false).attr('placeholder', '0');
        }
    }

    function reindexRows() {
        $(rowsContainer).find('[data-internal-consumption-row]').each(function (index) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) {
                    return;
                }
                $(this).attr(
                    'name',
                    name.replace(/internal_consumption_rows\[\d+\]|internal_consumption_rows\[__INDEX__\]/, 'internal_consumption_rows[' + index + ']')
                );
            });
        });
    }

    function clearRow($row) {
        $row.find('input[type="text"], input[type="number"]').not('[type="hidden"]').val('');
        $row.find('input[type="hidden"][name*="[id]"]').remove();
        $row.find('input[type="checkbox"][name*="[is_active]"]').prop('checked', true);
        $row.find('select').each(function () {
            if ($(this).hasClass('internal-consumption-value-type')) {
                $(this).val('cost');
            } else {
                $(this).val(null);
            }
        });
        toggleValueField($row);
        $row.find('select').trigger('change');
    }

    addBtn?.addEventListener('click', function () {
        if (!template) {
            return;
        }
        const html = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        const $newRow = $(rowsContainer).children('[data-internal-consumption-row]').last();
        reindexRows();
        toggleValueField($newRow);
        initSelect2($newRow);
    });

    $(rowsContainer).on('click', '.internal-consumption-remove-row', function () {
        const $rows = $(rowsContainer).children('[data-internal-consumption-row]');
        if ($rows.length <= 1) {
            clearRow($(this).closest('[data-internal-consumption-row]'));
            return;
        }
        $(this).closest('[data-internal-consumption-row]').remove();
        reindexRows();
    });

    $(rowsContainer).on('change', '.internal-consumption-value-type', function () {
        toggleValueField($(this).closest('[data-internal-consumption-row]'));
    });

    $(rowsContainer).find('[data-internal-consumption-row]').each(function () {
        toggleValueField($(this));
    });

    initSelect2(rowsContainer);
}
