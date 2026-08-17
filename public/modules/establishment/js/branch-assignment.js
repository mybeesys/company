function initBranchAssignment(rootSelector) {
    const root = document.querySelector(rootSelector);
    if (!root || typeof $ === 'undefined') {
        return;
    }

    function destroySelect2($el) {
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function updateCount($wrap) {
        const selected = $wrap.find('.select-2-branch-assign').val() || [];
        const count = String(selected.length);
        $wrap.closest('[data-catalog-item]').find('.branch-assign-tab-count').text(count);
    }

    function initSelect2(scope) {
        $(scope).find('.select-2-branch-assign').each(function () {
            const $el = $(this);
            destroySelect2($el);
            $el.select2({
                width: '100%',
                placeholder: $el.data('placeholder') || '',
                closeOnSelect: false,
                allowClear: true,
                dropdownParent: $(root).closest('form')
            });
            updateCount($el.closest('[data-branch-assignment]'));
        });
    }

    $(root).on('click', '.branch-assign-all', function (e) {
        e.preventDefault();
        const $wrap = $(this).closest('[data-branch-assignment]');
        const $select = $wrap.find('.select-2-branch-assign');
        const values = $select.find('option').map(function () {
            return $(this).val();
        }).get().filter(Boolean);
        $select.val(values).trigger('change');
    });

    $(root).on('click', '.branch-assign-none', function (e) {
        e.preventDefault();
        const $wrap = $(this).closest('[data-branch-assignment]');
        $wrap.find('.select-2-branch-assign').val(null).trigger('change');
    });

    $(root).on('change', '.select-2-branch-assign', function () {
        updateCount($(this).closest('[data-branch-assignment]'));
    });

    initSelect2(root);

    window.initBranchAssignmentSelects = initSelect2;
}
