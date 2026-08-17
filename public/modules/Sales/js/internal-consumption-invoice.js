(function () {
    const config = window.internalConsumptionInvoiceConfig || {};

    function prerequisitesMet() {
        return $('#toggleStorehouse').is(':checked');
    }

    function settingEnabled() {
        return $('#toggleInternalConsumption').is(':checked');
    }

    function isModeActive() {
        const typeSelected = !!$('#internal_consumption_type_id').val();
        const icToggleOn = settingEnabled();

        if (icToggleOn && typeSelected) {
            return true;
        }

        return prerequisitesMet() && icToggleOn && typeSelected;
    }

    function getEstablishmentId() {
        if ($('#toggleStorehouse').is(':checked')) {
            return parseInt($('#storehouse').val(), 10) || 0;
        }

        return parseInt($('#storehouse').val(), 10) || parseInt(config.defaultEstablishmentId, 10) || 0;
    }

    function zeroTaxValue() {
        return config.zeroTaxValue !== undefined && config.zeroTaxValue !== null
            ? String(config.zeroTaxValue)
            : '0';
    }

    function syncSettingsMenuItem() {
        const $item = $('#toggleInternalConsumptionItem');
        if (!$item.length) {
            return;
        }

        if (prerequisitesMet()) {
            $item.removeClass('d-none');
        } else {
            $item.addClass('d-none');
            $('#toggleInternalConsumption').prop('checked', false);
            hideFieldSection();
        }
    }

    function hideFieldSection() {
        $('#div-internal-consumption, #internal-consumption-hint').addClass('d-none');
        $('#internal_consumption_type_id').val('').trigger('change');
        clearMode();
    }

    function showFieldSection() {
        $('#div-internal-consumption, #internal-consumption-hint').removeClass('d-none');
        loadTypes();
    }

    function syncFieldSection() {
        if (prerequisitesMet() && settingEnabled()) {
            showFieldSection();
        } else {
            hideFieldSection();
        }
    }

    function loadTypes() {
        const estId = getEstablishmentId();
        const $select = $('#internal_consumption_type_id');
        const current = $select.val();

        if (!config.typesUrl || estId <= 0) {
            $select.empty().append(`<option value="">${config.selectPlaceholder || ''}</option>`);
            return;
        }

        $select.prop('disabled', true);
        $.get(config.typesUrl, { establishment_id: estId })
            .done(function (response) {
                const types = Array.isArray(response.data)
                    ? response.data
                    : (Array.isArray(response) ? response : []);
                const lang = localStorage.getItem('lang') || 'ar';

                $select.empty().append(`<option value="">${config.selectPlaceholder || ''}</option>`);
                types.forEach(function (type) {
                    const label = lang === 'ar' ? type.name_ar : (type.name_en || type.name_ar);
                    $select.append(`<option value="${type.id}">${label}</option>`);
                });

                const restoreId = current || String(config.initialTypeId || '');
                if (restoreId && $select.find(`option[value="${restoreId}"]`).length) {
                    $select.val(restoreId).trigger('change');
                } else if (current) {
                    $select.val('');
                    clearMode();
                }
            })
            .always(function () {
                $select.prop('disabled', false);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.trigger('change.select2');
                }
            });
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function resolveRowCost($row) {
        const stored = parseFloat($row.data('inventory-cost'));
        if (!Number.isNaN(stored)) {
            return stored;
        }

        const $productSelect = $row.find('[name$="[products_id]"]');
        const selectedOption = $productSelect.find('option:selected');
        const inventoryCost = parseFloat(selectedOption.data('inventory-cost'));
        if (!Number.isNaN(inventoryCost)) {
            return inventoryCost;
        }
        const optionCost = parseFloat(selectedOption.data('cost'));
        if (!Number.isNaN(optionCost)) {
            return optionCost;
        }

        return parseFloat($row.find('.unit_price-field').val()) || 0;
    }

    let costRequest = null;
    let costTimer = null;

    function collectCostLines() {
        const lines = [];
        $('#salesTable tbody tr.sales-line-row').each(function () {
            lines.push({
                product_id: parseInt($(this).find('[name$="[products_id]"]').val(), 10) || 0,
                qty: parseFloat($(this).find('.qty-field').val()) || 0,
                unit_id: parseInt($(this).find('.unit').val(), 10) || 0,
            });
        });
        return lines;
    }

    function applyCostToRows(costs) {
        $('#salesTable tbody tr.sales-line-row').each(function (index) {
            const $row = $(this);
            const hasProduct = !!$row.find('[name$="[products_id]"]').val();
            if (!hasProduct) {
                return;
            }
            const $price = $row.find('.unit_price-field');
            if ($price.data('sale-price') === undefined) {
                $price.data('sale-price', parseFloat($price.val()) || 0);
            }
            const rowCost = costs && costs[index] ? parseFloat(costs[index].unit_cost) : NaN;
            const cost = !Number.isNaN(rowCost) ? rowCost : resolveRowCost($row);
            $row.data('inventory-cost', cost);
            $price.val(cost > 0 ? cost.toFixed(4) : '0').prop('readonly', true);
            setRowZeroTax($row);
            $row.find('[name*="[discount]"]').prop('readonly', true);
        });
    }

    function refreshInventoryCosts() {
        if (!isModeActive()) {
            return;
        }

        const estId = getEstablishmentId();
        if (!config.costsUrl || estId <= 0) {
            applyCostToRows(null);
            if (typeof updateSalesTotals === 'function') {
                updateSalesTotals();
            }
            return;
        }

        if (costRequest && typeof costRequest.abort === 'function') {
            costRequest.abort();
        }

        costRequest = $.ajax({
            url: config.costsUrl,
            method: 'POST',
            data: {
                _token: csrfToken(),
                establishment_id: estId,
                lines: collectCostLines(),
            },
        }).done(function (response) {
            applyCostToRows(Array.isArray(response.data) ? response.data : null);
            if (typeof updateSalesTotals === 'function') {
                updateSalesTotals();
            }
        }).fail(function (xhr) {
            if (xhr && xhr.statusText === 'abort') {
                return;
            }
            applyCostToRows(null);
            if (typeof updateSalesTotals === 'function') {
                updateSalesTotals();
            }
        });
    }

    function scheduleInventoryCostRefresh() {
        if (!isModeActive()) {
            return;
        }
        clearTimeout(costTimer);
        costTimer = setTimeout(refreshInventoryCosts, 200);
    }

    function setRowZeroTax($row) {
        const zeroTax = zeroTaxValue();
        const $taxSelect = $row.find('[name*="[tax_vat]"]');
        if ($taxSelect.find(`option[value="${zeroTax}"]`).length) {
            $taxSelect.val(zeroTax).trigger('change');
        }
        $row.find('[name*="[inclusive]"]').prop('checked', false);
        $row.find('[name*="[discount]"]').val('0');
        $row.find('[name*="[discount_type]"]').val('fixed');
    }

    function applyInternalConsumptionPricing() {
        $('#transaction_purpose').val('internal_consumption');
        applyCostToRows(null);

        $('#invoice_discount, #invoiced_discount_type').prop('readonly', true);
        $('#div-cash_account').addClass('d-none').hide();
        $('#cash_account').prop('required', false).removeAttr('required');
        $('#client_id').prop('required', false).removeAttr('required');
        $('#client_l_id').removeClass('required');
        $('#internal_consumption_type_id').prop('required', true).attr('required', 'required');

        if (typeof updateSalesTotals === 'function') {
            updateSalesTotals();
        }

        scheduleInventoryCostRefresh();
    }

    function clearMode() {
        $('#transaction_purpose').val('standard');
        $('#internal_consumption_type_id').prop('required', false).removeAttr('required').removeClass('is-invalid');

        $('#salesTable tbody tr').each(function () {
            const $row = $(this);
            const $price = $row.find('.unit_price-field');
            const salePrice = $price.data('sale-price');

            if (salePrice !== undefined) {
                $price.val(salePrice).removeData('sale-price');
            }

            $price.prop('readonly', false);
            $row.find('[name*="[discount]"]').prop('readonly', false);
            $row.removeData('inventory-cost');
        });

        $('#invoice_discount, #invoiced_discount_type').prop('readonly', false);

        if (typeof window.refreshInvoiceTypeAccountVisibility === 'function') {
            window.refreshInvoiceTypeAccountVisibility();
        } else {
            $('#div-cash_account').removeClass('d-none');
        }

        if (typeof updateSalesTotals === 'function') {
            updateSalesTotals();
        }
    }

    window.isInternalConsumptionInvoiceMode = isModeActive;
    window.syncInternalConsumptionSettingsUi = function () {
        syncSettingsMenuItem();
        syncFieldSection();
    };

    window.resolveInvoiceProductUnitPrice = function (productData) {
        if (isModeActive()) {
            const inventoryCost = parseFloat(productData.inventory_cost);
            if (!Number.isNaN(inventoryCost)) {
                return inventoryCost;
            }
            const cost = parseFloat(productData.cost);
            if (!Number.isNaN(cost)) {
                return cost;
            }
        }

        return parseFloat(productData.price) || 0;
    };

    window.reapplyInternalConsumptionPricing = applyInternalConsumptionPricing;
    window.initInternalConsumptionInvoice = function () {
        $('#toggleStorehouse, #toggleInternalConsumption').on('change', function () {
            syncSettingsMenuItem();
            syncFieldSection();
        });

        $('#storehouse').on('change', function () {
            if (prerequisitesMet() && settingEnabled()) {
                const hadType = !!$('#internal_consumption_type_id').val();
                loadTypes();
                if (hadType) {
                    $('#internal_consumption_type_id').val('').trigger('change');
                }
            }
        });

        $('#internal_consumption_type_id').on('change', function () {
            if ($(this).val()) {
                applyInternalConsumptionPricing();
            } else {
                clearMode();
            }
        });

        $(document).on(
            'input change',
            '#salesTable .qty-field, #salesTable .unit',
            function () {
                if (isModeActive()) {
                    scheduleInventoryCostRefresh();
                }
            }
        );

        $(document).on('submit', '#sell_save', function () {
            if (window.__sellInvoiceNativeSubmit || typeof window.validateSellInvoiceForm === 'function') {
                return true;
            }

            if (!settingEnabled() || !prerequisitesMet()) {
                $('#transaction_purpose').val('standard');
                return true;
            }

            if ($('#internal_consumption_type_id').val()) {
                $('#transaction_purpose').val('internal_consumption');
                applyInternalConsumptionPricing();
                return true;
            }

            if ($('#transaction_purpose').val() === 'internal_consumption') {
                const msg = config.typeRequiredMessage || 'Please select an internal expense type.';
                if (typeof window.showSellInvoiceFeedback === 'function') {
                    window.showSellInvoiceFeedback('warning', msg);
                } else if (typeof toastr !== 'undefined') {
                    toastr.warning(msg);
                }
                $('#internal_consumption_type_id').addClass('is-invalid').focus();
                return false;
            }

            return true;
        });

        syncSettingsMenuItem();
        syncFieldSection();

        if (config.initialTypeId && settingEnabled() && prerequisitesMet()) {
            loadTypes();
        } else if (isModeActive()) {
            applyInternalConsumptionPricing();
        }
    };
})();
