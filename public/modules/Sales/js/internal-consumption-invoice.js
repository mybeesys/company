(function () {
    const config = window.internalConsumptionInvoiceConfig || {};

    function prerequisitesMet() {
        return $('#toggleStorehouse').is(':checked');
    }

    function settingEnabled() {
        return $('#toggleInternalConsumption').is(':checked');
    }

    function isModeActive() {
        return prerequisitesMet() && settingEnabled() && !!$('#internal_consumption_type_id').val();
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

                if (current && $select.find(`option[value="${current}"]`).length) {
                    $select.val(current);
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

    function resolveRowCost($row) {
        const $productSelect = $row.find('[name$="[products_id]"]');
        const selectedOption = $productSelect.find('option:selected');
        const optionCost = parseFloat(selectedOption.data('cost'));
        if (!Number.isNaN(optionCost)) {
            return optionCost;
        }

        const select2Data = $productSelect.data('select2')
            ? $productSelect.select2('data')[0]
            : null;
        if (select2Data && select2Data.cost !== undefined) {
            return parseFloat(select2Data.cost) || 0;
        }

        return parseFloat($row.find('.unit_price-field').val()) || 0;
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

        $('#salesTable tbody tr').each(function () {
            const $row = $(this);
            const $price = $row.find('.unit_price-field');
            const hasProduct = !!$row.find('[name$="[products_id]"]').val();

            if (!hasProduct) {
                return;
            }

            if ($price.data('sale-price') === undefined) {
                $price.data('sale-price', parseFloat($price.val()) || 0);
            }

            const cost = resolveRowCost($row);
            $price.val(cost > 0 ? cost.toFixed(4) : '0').prop('readonly', true);
            setRowZeroTax($row);
            $row.find('[name*="[discount]"]').prop('readonly', true);
        });

        $('#invoice_discount, #invoiced_discount_type').prop('readonly', true);
        $('#div-cash_account').addClass('d-none');
        $('#cash_account').prop('required', false);
        $('#client_id').prop('required', false);
        $('#client_l_id').removeClass('required');

        if (typeof updateSalesTotals === 'function') {
            updateSalesTotals();
        }
    }

    function clearMode() {
        $('#transaction_purpose').val('standard');

        $('#salesTable tbody tr').each(function () {
            const $row = $(this);
            const $price = $row.find('.unit_price-field');
            const salePrice = $price.data('sale-price');

            if (salePrice !== undefined) {
                $price.val(salePrice).removeData('sale-price');
            }

            $price.prop('readonly', false);
            $row.find('[name*="[discount]"]').prop('readonly', false);
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

        $(document).on('submit', '#sell_save', function (e) {
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
                e.preventDefault();
                const msg = config.typeRequiredMessage || 'Please select an internal expense type.';
                if (typeof toastr !== 'undefined') {
                    toastr.warning(msg);
                }
                $('#internal_consumption_type_id').addClass('is-invalid').focus();
                return false;
            }

            return true;
        });

        syncSettingsMenuItem();
        syncFieldSection();
    };
})();
