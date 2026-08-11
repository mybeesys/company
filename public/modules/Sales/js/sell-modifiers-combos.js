(function (window, $) {
    'use strict';

    if (!window.sellWithModifiersCombos) {
        return;
    }

    const locale = (localStorage.getItem('lang') || document.documentElement.lang || 'ar').slice(0, 2);
    const i18n = window.smcI18n || {};
    let modalInstance = null;
    let activeRow = null;
    let activeProduct = null;
    let selectedModifiers = {};
    let selectedCombos = {};
    let isApplyingSelection = false;

    function money(n) {
        return (Number(n) || 0).toFixed(2);
    }

    function labelOf(obj) {
        if (!obj) return '';
        return locale === 'en'
            ? (obj.name_en || obj.name_ar || obj.name || '')
            : (obj.name_ar || obj.name_en || obj.name || '');
    }

    function getModal() {
        const el = document.getElementById('sellModifiersCombosModal');
        if (!el || typeof bootstrap === 'undefined') return null;
        if (!modalInstance) {
            modalInstance = bootstrap.Modal.getOrCreateInstance(el);
        }
        return modalInstance;
    }

    function clearSelectionState() {
        selectedModifiers = {};
        selectedCombos = {};
    }

    function estimateTotal() {
        if (!activeProduct) return 0;
        let total = Number(activeProduct.price_with_tax || activeProduct.price || 0);
        Object.values(selectedModifiers).forEach((m) => {
            total += Number(m.price_with_tax || m.price || 0) * Number(m.quantity || 1);
        });
        Object.values(selectedCombos).forEach((c) => {
            total += Number(c.price_with_tax || c.price || 0) * Number(c.quantity || 1);
        });
        return total;
    }

    function refreshEstimate() {
        $('#smcEstimateTotal').text(money(estimateTotal()));
    }

    function renderModifierGroups(groups) {
        const $wrap = $('#smcModifiersGroups').empty();
        const $section = $('#smcModifiersSection');
        if (!groups || !groups.length) {
            $section.addClass('d-none');
            return;
        }
        $section.removeClass('d-none');
        groups.forEach((group) => {
            const $group = $('<div class="smc-group"/>');
            const req = group.required ? ` <span class="text-danger">*</span>` : '';
            $group.append(`<div class="smc-group-label">${labelOf(group)}${req}</div>`);
            const $grid = $('<div class="smc-chip-grid"/>');
            (group.options || []).forEach((opt) => {
                const key = String(opt.id);
                const $chip = $(`
                    <button type="button" class="smc-chip" data-mod-id="${opt.id}">
                        <span>${labelOf(opt)}</span>
                        <span class="smc-chip__price">+${money(opt.price_with_tax || opt.price)}</span>
                    </button>
                `);
                if (selectedModifiers[key]) {
                    $chip.addClass('is-selected');
                }
                $chip.on('click', function () {
                    const max = Number(group.max || 0);
                    if (selectedModifiers[key]) {
                        delete selectedModifiers[key];
                        $chip.removeClass('is-selected');
                    } else {
                        if (max === 1) {
                            $grid.find('.smc-chip').removeClass('is-selected');
                            Object.keys(selectedModifiers).forEach((k) => {
                                if ((group.options || []).some((o) => String(o.id) === k)) {
                                    delete selectedModifiers[k];
                                }
                            });
                        } else if (max > 1) {
                            const countInGroup = (group.options || []).filter((o) => selectedModifiers[String(o.id)]).length;
                            if (countInGroup >= max) return;
                        }
                        selectedModifiers[key] = {
                            modifier_id: Number(opt.id),
                            quantity: 1,
                            price: Number(opt.price || 0),
                            price_with_tax: Number(opt.price_with_tax || opt.price || 0),
                            discount_amount: 0,
                            discount_type: null,
                            tax_id: null,
                            tax_value: Number(opt.tax_value || 0),
                            total_before_vat: Number(opt.price || 0),
                            name: labelOf(opt),
                        };
                        $chip.addClass('is-selected');
                    }
                    refreshEstimate();
                });
                $grid.append($chip);
            });
            $group.append($grid);
            $wrap.append($group);
        });
    }

    function renderComboGroups(groups) {
        const $wrap = $('#smcComboGroups').empty();
        const $section = $('#smcCombosSection');
        if (!groups || !groups.length) {
            $section.addClass('d-none');
            return;
        }
        $section.removeClass('d-none');
        groups.forEach((group) => {
            const groupId = String(group.id);
            const $group = $('<div class="smc-group"/>');
            $group.append(`<div class="smc-group-label">${labelOf(group)}</div>`);
            const $grid = $('<div class="smc-chip-grid"/>');
            const need = Math.max(1, Number(group.quantity || 1));
            (group.options || []).forEach((opt) => {
                const key = `${groupId}:${opt.id}`;
                const price = Number(opt.price_with_tax || opt.price || 0);
                const $chip = $(`
                    <button type="button" class="smc-chip" data-combo-key="${key}">
                        <span>${labelOf(opt)}</span>
                        ${price > 0 ? `<span class="smc-chip__price">+${money(price)}</span>` : ''}
                    </button>
                `);
                if (selectedCombos[key]) {
                    $chip.addClass('is-selected');
                }
                $chip.on('click', function () {
                    if (selectedCombos[key]) {
                        delete selectedCombos[key];
                        $chip.removeClass('is-selected');
                    } else {
                        if (need === 1) {
                            $grid.find('.smc-chip').removeClass('is-selected');
                            Object.keys(selectedCombos).forEach((k) => {
                                if (k.startsWith(groupId + ':')) delete selectedCombos[k];
                            });
                        } else {
                            const count = Object.keys(selectedCombos).filter((k) => k.startsWith(groupId + ':')).length;
                            if (count >= need) return;
                        }
                        selectedCombos[key] = {
                            option_id: Number(opt.id),
                            combo_group_id: Number(group.id),
                            quantity: 1,
                            price: Number(opt.price || 0),
                            price_with_tax: price,
                            name: labelOf(opt),
                        };
                        $chip.addClass('is-selected');
                    }
                    refreshEstimate();
                });
                $grid.append($chip);
            });
            $group.append($grid);
            $wrap.append($group);
        });
    }

    function openModal(row, productData, prefill) {
        activeRow = row;
        activeProduct = productData;
        const hadConfirmed = !!activeRow.data('smc-confirmed');
        activeRow.data('smc-opening-fresh', !hadConfirmed);
        activeRow.data('smc-confirmed', false);
        clearSelectionState();

        if (prefill) {
            (prefill.modifiers || []).forEach((m) => {
                selectedModifiers[String(m.modifier_id)] = m;
            });
            (prefill.combos || []).forEach((c) => {
                const key = `${c.combo_group_id || 'x'}:${c.option_id}`;
                selectedCombos[key] = c;
            });
        }

        $('#sellModifiersCombosModalTitle').text(labelOf(productData) || productData.name || '');
        $('#smcBasePrice').text(money(productData.price_with_tax || productData.price || 0));

        const img = productData.image;
        if (img) {
            $('#smcProductImage').attr('src', img).removeClass('d-none');
            $('#smcProductImageFallback').addClass('d-none');
        } else {
            $('#smcProductImage').addClass('d-none').attr('src', '');
            $('#smcProductImageFallback').removeClass('d-none');
        }

        const hasMods = (productData.modifier_groups || []).length > 0;
        const hasCombos = (productData.combo_groups || []).length > 0;
        $('#smcEmptyState').toggleClass('d-none', hasMods || hasCombos);
        renderModifierGroups(productData.modifier_groups || []);
        renderComboGroups(productData.combo_groups || []);
        refreshEstimate();
        getModal()?.show();
    }

    function applyToRow(withExtras) {
        if (!activeRow || !activeRow.length) return;

        const modifiers = withExtras ? Object.values(selectedModifiers) : [];
        const combos = withExtras ? Object.values(selectedCombos) : [];
        let extrasBefore = 0;
        let extrasInc = 0;
        modifiers.forEach((m) => {
            const q = Number(m.quantity || 1);
            extrasBefore += Number(m.price || 0) * q;
            extrasInc += Number(m.price_with_tax || m.price || 0) * q;
        });
        combos.forEach((c) => {
            const q = Number(c.quantity || 1);
            extrasBefore += Number(c.price || 0) * q;
            extrasInc += Number(c.price_with_tax || c.price || 0) * q;
        });

        ensureHiddenFields(activeRow);
        activeRow.find('.smc-order-item-modifiers').val(JSON.stringify(modifiers));
        activeRow.find('.smc-order-item-combos').val(JSON.stringify(combos));
        activeRow.find('.smc-extras-before-vat').val(extrasBefore.toFixed(4));
        activeRow.find('.smc-extras-inc-tax').val(extrasInc.toFixed(4));
        activeRow.data('smc-confirmed', true);

        renderExtrasChips(activeRow, modifiers, combos);
        if (typeof updateSalesTotals === 'function') {
            updateSalesTotals();
        }
        isApplyingSelection = true;
        getModal()?.hide();
    }

    function ensureHiddenFields($row) {
        if (!$row.find('.smc-order-item-modifiers').length) {
            $row.find('td').eq(1).append(`
                <input type="hidden" class="smc-order-item-modifiers" name="" value="[]">
                <input type="hidden" class="smc-order-item-combos" name="" value="[]">
                <input type="hidden" class="smc-extras-before-vat" name="" value="0">
                <input type="hidden" class="smc-extras-inc-tax" name="" value="0">
                <div class="smc-extras-wrap"></div>
            `);
        }
        renameRowFields($row);
    }

    function renameRowFields($row) {
        const index = $row.index();
        $row.find('.smc-order-item-modifiers').attr('name', `products[${index}][order_item_modifiers]`);
        $row.find('.smc-order-item-combos').attr('name', `products[${index}][order_item_combos]`);
        $row.find('.smc-extras-before-vat').attr('name', `products[${index}][extras_before_vat]`);
        $row.find('.smc-extras-inc-tax').attr('name', `products[${index}][extras_inc_tax]`);
    }

    function renderExtrasChips($row, modifiers, combos) {
        ensureHiddenFields($row);
        const $wrap = $row.find('.smc-extras-wrap').empty();
        if ((!modifiers || !modifiers.length) && (!combos || !combos.length)) {
            return;
        }
        (modifiers || []).forEach((m) => {
            $wrap.append(`<span class="smc-extras-chip">+ ${m.name || m.modifier_id}</span>`);
        });
        (combos || []).forEach((c) => {
            $wrap.append(`<span class="smc-extras-chip smc-extras-chip--combo">${c.name || c.option_id}</span>`);
        });
        const $edit = $(`<button type="button" class="btn btn-sm btn-light smc-edit-extras">${i18n.edit || 'Edit'}</button>`);
        $edit.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const productId = $row.find('.product-select').val();
            if (!productId) return;
            fetchAndOpen($row, productId, {
                modifiers: JSON.parse($row.find('.smc-order-item-modifiers').val() || '[]'),
                combos: JSON.parse($row.find('.smc-order-item-combos').val() || '[]'),
            });
        });
        $wrap.append($edit);
    }

    function fetchAndOpen($row, productId, prefill) {
        $.ajax({
            url: `/product-sell-extras/${productId}`,
            method: 'GET',
            success: function (res) {
                if (!res.success || !res.data) return;
                if (!res.data.has_modifiers && !res.data.has_combos) {
                    ensureHiddenFields($row);
                    $row.find('.smc-order-item-modifiers').val('[]');
                    $row.find('.smc-order-item-combos').val('[]');
                    $row.find('.smc-extras-before-vat').val('0');
                    $row.find('.smc-extras-inc-tax').val('0');
                    $row.find('.smc-extras-wrap').empty();
                    return;
                }
                openModal($row, res.data, prefill || null);
            },
        });
    }

    function maybeOpenForSelect($select, data) {
        if (!window.sellWithModifiersCombos || !data || !data.id) return;
        const $row = $select.closest('tr');
        ensureHiddenFields($row);
        $row.data('smc-confirmed', false);
        $row.find('.smc-order-item-modifiers').val('[]');
        $row.find('.smc-order-item-combos').val('[]');
        $row.find('.smc-extras-before-vat').val('0');
        $row.find('.smc-extras-inc-tax').val('0');
        $row.find('.smc-extras-wrap').empty();

        // Prefer ajax flags when present; otherwise fetch (static prefilled selects).
        if (data.has_modifiers === false && data.has_combos === false) {
            return;
        }
        if (data.has_modifiers || data.has_combos) {
            fetchAndOpen($row, data.id);
            return;
        }
        fetchAndOpen($row, data.id);
    }

    function clearRowProduct($row) {
        const $select = $row.find('.product-select');
        $select.val(null).trigger('change');
        $row.find('.unit_price-field').val('');
        ensureHiddenFields($row);
        $row.find('.smc-order-item-modifiers').val('[]');
        $row.find('.smc-order-item-combos').val('[]');
        $row.find('.smc-extras-before-vat').val('0');
        $row.find('.smc-extras-inc-tax').val('0');
        $row.find('.smc-extras-wrap').empty();
        if (typeof updateSalesTotals === 'function') updateSalesTotals();
    }

    $(function () {
        $('#smcConfirmBtn').on('click', function () {
            applyToRow(true);
        });
        $('#smcSkipBtn').on('click', function () {
            applyToRow(false);
        });
        $('#smcCancelBtn').on('click', function () {
            if (activeRow && activeRow.data('smc-opening-fresh')) {
                clearRowProduct(activeRow);
            }
        });
        $('#sellModifiersCombosModal').on('hide.bs.modal', function () {
            if (isApplyingSelection) {
                isApplyingSelection = false;
                return;
            }
            // Backdrop / X close without confirm on a fresh product pick: clear selection.
            if (activeRow && activeRow.data('smc-opening-fresh')) {
                clearRowProduct(activeRow);
            }
        });
        $('#sellModifiersCombosModal').on('hidden.bs.modal', function () {
            activeRow = null;
            activeProduct = null;
            isApplyingSelection = false;
        });

        $(document).on('select2:select', '#salesTable .product-select', function (e) {
            maybeOpenForSelect($(this), e.params.data || {});
        });

        // Prefill chips for convert / duplicate rows rendered server-side.
        $('#salesTable tbody tr.sales-line-row').each(function () {
            const $row = $(this);
            const modsRaw = $row.find('.smc-order-item-modifiers').val();
            const combosRaw = $row.find('.smc-order-item-combos').val();
            if (!modsRaw && !combosRaw) return;
            try {
                const modifiers = JSON.parse(modsRaw || '[]');
                const combos = JSON.parse(combosRaw || '[]');
                if ((modifiers && modifiers.length) || (combos && combos.length)) {
                    renderExtrasChips($row, modifiers, combos);
                    $row.data('smc-confirmed', true);
                }
            } catch (err) {
                /* ignore bad JSON */
            }
        });

        if (typeof updateSalesTotals === 'function' && window.sellWithModifiersCombos) {
            updateSalesTotals();
        }
    });

    window.SellModifiersCombos = {
        maybeOpenForSelect,
        ensureHiddenFields,
        renameRowFields,
        renderExtrasChips,
        fetchAndOpen,
        prefillRow: function ($row, payload) {
            if (!$row || !payload) return;
            ensureHiddenFields($row);
            $row.find('.smc-order-item-modifiers').val(JSON.stringify(payload.modifiers || []));
            $row.find('.smc-order-item-combos').val(JSON.stringify(payload.combos || []));
            $row.find('.smc-extras-before-vat').val(Number(payload.extras_before_vat || 0).toFixed(4));
            $row.find('.smc-extras-inc-tax').val(Number(payload.extras_inc_tax || 0).toFixed(4));
            renderExtrasChips($row, payload.modifiers || [], payload.combos || []);
            $row.data('smc-confirmed', true);
        },
    };
})(window, jQuery);
