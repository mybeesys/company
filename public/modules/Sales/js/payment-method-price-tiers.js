/**
 * payment-method-price-tiers.js
 * Apply product price-tier prices when a payment method with price_tier_id is selected.
 * If the method has no tier, or the product has no price for that tier → keep catalog base.
 */
window.PaymentMethodPriceTiers = (function () {
    'use strict';

    var applying = false;

    function cfg() {
        return window.paymentMethodPriceTiersConfig || { methods: [], locale: 'ar' };
    }

    function round2(v) {
        return Math.round((Number(v) || 0) * 100) / 100;
    }

    function isInternalConsumption() {
        return typeof isInternalConsumptionInvoiceMode === 'function'
            && isInternalConsumptionInvoiceMode();
    }

    function methods() {
        return Array.isArray(cfg().methods) ? cfg().methods : [];
    }

    function resolveSelectedMethod() {
        var methodId = String($('#payment_method_fee_demo_method').val() || '');
        var accountId = String($('#account_id').val() || '');
        var list = methods();

        if (methodId) {
            for (var i = 0; i < list.length; i++) {
                if (String(list[i].id) === methodId) {
                    return list[i];
                }
            }
        }

        if (!accountId) {
            return null;
        }

        for (var j = 0; j < list.length; j++) {
            var m = list[j];
            if (String(m.account_id || '') === accountId) {
                return m;
            }
            var branches = m.branch_accounts || {};
            for (var estId in branches) {
                if (!Object.prototype.hasOwnProperty.call(branches, estId)) {
                    continue;
                }
                if (String(branches[estId]) === accountId) {
                    return m;
                }
            }
        }

        return null;
    }

    function captureCatalogPrice($row, price) {
        var $field = $row.find('.unit_price-field');
        if (!$field.length) {
            return;
        }
        var productId = String($row.find('[name$="[products_id]"]').val() || '');
        $row.data('pmpt-catalog-price', round2(price));
        $row.data('pmpt-product-id', productId);
        $field.data('pmpt-base-for-product', productId);
    }

    function getCatalogPrice($row) {
        var stored = $row.data('pmpt-catalog-price');
        if (stored !== undefined && stored !== null && stored !== '') {
            return round2(stored);
        }
        var $field = $row.find('.unit_price-field');
        return round2($field.val());
    }

    function tierPriceForProduct($row, priceTierId) {
        if (!priceTierId) {
            return null;
        }
        var tiers = $row.data('pmpt-price-tiers');
        if (!Array.isArray(tiers)) {
            return null;
        }
        for (var i = 0; i < tiers.length; i++) {
            if (String(tiers[i].price_tier_id) === String(priceTierId)) {
                var p = parseFloat(tiers[i].price);
                return isNaN(p) ? null : round2(p);
            }
        }
        return null;
    }

    function applyToLines() {
        if (isInternalConsumption()) {
            return;
        }

        var method = resolveSelectedMethod();
        var priceTierId = method && method.price_tier_id ? parseInt(method.price_tier_id, 10) : 0;

        applying = true;
        try {
            $('#salesTable tbody tr.sales-line-row').each(function () {
                var $row = $(this);
                var $field = $row.find('.unit_price-field');
                if (!$field.length || $field.prop('readonly')) {
                    return;
                }

                var productId = String($row.find('[name$="[products_id]"]').val() || '');
                if (!productId) {
                    return;
                }

                var baseForProduct = String($field.data('pmpt-base-for-product') || '');
                if (baseForProduct !== productId) {
                    captureCatalogPrice($row, $field.val());
                }

                var next = getCatalogPrice($row);
                if (priceTierId > 0) {
                    var tierPrice = tierPriceForProduct($row, priceTierId);
                    if (tierPrice !== null) {
                        next = tierPrice;
                    }
                }

                var current = parseFloat($field.val()) || 0;
                if (Math.abs(current - next) > 0.0001) {
                    $field.val(next.toFixed(2));
                }
            });
        } finally {
            applying = false;
        }

        if (window.PaymentMethodFees && typeof window.PaymentMethodFees.applyItemFeesToLines === 'function') {
            window.PaymentMethodFees.applyItemFeesToLines();
        }
        if (typeof window.updateSalesTotals === 'function') {
            window.updateSalesTotals();
        }
    }

    function rememberProductTiers($row, tiers) {
        $row.data('pmpt-price-tiers', Array.isArray(tiers) ? tiers : []);
    }

    function init() {
        if (!window.paymentMethodPriceTiersConfig) {
            return;
        }

        $(document).on('input', '#salesTable .unit_price-field', function () {
            if (applying) {
                return;
            }
            // Manual edit becomes the new catalog base for this line.
            captureCatalogPrice($(this).closest('tr'), $(this).val());
        });

        $(document).on('change', '#account_id, #payment_method_fee_demo_method', function () {
            applyToLines();
        });
    }

    return {
        init: init,
        applyToLines: applyToLines,
        captureCatalogPrice: captureCatalogPrice,
        rememberProductTiers: rememberProductTiers,
    };
})();

$(function () {
    if (window.PaymentMethodPriceTiers) {
        window.PaymentMethodPriceTiers.init();
    }
});
