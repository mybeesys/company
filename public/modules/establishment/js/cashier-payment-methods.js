function initCashierPaymentMethods() {
    const root = document.getElementById('cashier_payment_methods_root');
    if (!root) {
        return;
    }

    const rowsContainer = document.getElementById('cashier_payment_rows');
    const template = document.getElementById('cashier_payment_row_template');
    const addBtn = document.getElementById('cashier_add_payment_row');

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function initSelect2(scope) {
        $(scope).find('.select-2-cashier').each(function () {
            const $el = $(this);
            destroySelect2($el);
            $el.select2({
                allowClear: true,
                width: '100%',
                dropdownParent: $(root).closest('form')
            });
        });
    }

    function reindexRows() {
        $(rowsContainer).find('[data-cashier-row]').each(function (index) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (!name) {
                    return;
                }
                $(this).attr(
                    'name',
                    name.replace(/cashier_payment_rows\[\d+\]|cashier_payment_rows\[__INDEX__\]/, 'cashier_payment_rows[' + index + ']')
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
        initSelect2($newRow);
    });

    $(rowsContainer).on('click', '.cashier-remove-row', function () {
        const $rows = $(rowsContainer).children('[data-cashier-row]');
        if ($rows.length <= 1) {
            clearRow($(this).closest('[data-cashier-row]'));
            return;
        }
        $(this).closest('[data-cashier-row]').remove();
        reindexRows();
    });

    initSelect2(rowsContainer);
}
