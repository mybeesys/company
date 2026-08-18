/**
 * payment-method-fees.js
 * تجريب رسوم طرق الدفع في صفحة إنشاء الفاتورة.
 *
 * - على كل منتج: تُضاف مباشرة إلى سعر المبيع في السطر، ثم يُعاد حساب إجمالي السطر.
 * - على الفاتورة: تُحسب على الإجمالي قبل الخصم وتُضاف للمجموع النهائي فقط.
 */
window.PaymentMethodFees = (function () {
    'use strict';

    var FEE_TYPE_PERCENT = '1';
    var APPLY_ITEM = '0';
    var applyingPrices = false;

    function cfg() {
        return window.paymentMethodFeesConfig || { methods: [], locale: 'ar' };
    }

    function round2(v) {
        return Math.round((Number(v) || 0) * 100) / 100;
    }

    function isInternalConsumption() {
        return typeof isInternalConsumptionInvoiceMode === 'function'
            && isInternalConsumptionInvoiceMode();
    }

    function activeFees(fees) {
        return (Array.isArray(fees) ? fees : []).filter(function (fee) {
            if (!fee) {
                return false;
            }
            if (fee.is_active === false || fee.is_active === 0 || fee.is_active === '0') {
                return false;
            }
            return true;
        });
    }

    function itemFeesOf(fees) {
        return activeFees(fees).filter(function (fee) {
            return String(fee.application_type || '1') === APPLY_ITEM;
        });
    }

    function orderFeesOf(fees) {
        return activeFees(fees).filter(function (fee) {
            return String(fee.application_type || '1') !== APPLY_ITEM;
        });
    }

    function unitPriceField($row) {
        return $row.find('.unit_price-field').first();
    }

    function getBasePrice($row) {
        var $field = unitPriceField($row);
        var stored = $field.data('pmf-base-price');
        if (stored === undefined || stored === null || stored === '') {
            stored = parseFloat($field.val()) || 0;
            $field.data('pmf-base-price', stored);
            $field.data('pmf-base-for-product', String($row.find('[name$="[products_id]"]').val() || ''));
        }
        return parseFloat(stored) || 0;
    }

    function setBasePrice($row, value) {
        var $field = unitPriceField($row);
        var productId = String($row.find('[name$="[products_id]"]').val() || '');
        $field.data('pmf-base-price', round2(parseFloat(value) || 0));
        $field.data('pmf-base-for-product', productId);
        $row.data('pmf-product-id', productId);
    }

    function captureCatalogPrice($row, price) {
        if (!$row || !$row.length) {
            return;
        }
        setBasePrice($row, price);
    }

    function adjustedUnitFromBase(base, itemFees) {
        var extra = 0;
        (itemFees || []).forEach(function (fee) {
            var amount = parseFloat(fee.amount) || 0;
            if (String(fee.fee_type || '0') === FEE_TYPE_PERCENT) {
                extra += base * (amount / 100);
            } else {
                extra += amount;
            }
        });
        return round2(Math.max(0, base + extra));
    }

    function collectBaseLines() {
        var lines = [];
        $('#salesTable tbody tr.sales-line-row').each(function () {
            var qty = parseFloat($(this).find('.qty-field').val()) || 0;
            var unit = getBasePrice($(this));
            lines.push({
                qty: qty,
                unit: unit,
                net: round2(Math.max(0, unit * qty)),
            });
        });
        return lines;
    }

    function computeItemFeeAmount(fees, lines) {
        var total = 0;
        itemFeesOf(fees).forEach(function (fee) {
            var amount = parseFloat(fee.amount) || 0;
            lines.forEach(function (line) {
                if (line.qty <= 0 || line.unit <= 0) {
                    return;
                }
                if (String(fee.fee_type || '0') === FEE_TYPE_PERCENT) {
                    total += round2(line.net * (amount / 100));
                } else {
                    total += round2(amount * line.qty);
                }
            });
        });
        return round2(total);
    }

    function computeOrderFeeAmount(fees, orderNet) {
        var total = 0;
        orderFeesOf(fees).forEach(function (fee) {
            var amount = parseFloat(fee.amount) || 0;
            if (String(fee.fee_type || '0') === FEE_TYPE_PERCENT) {
                total += round2(orderNet * (amount / 100));
            } else {
                total += amount;
            }
        });
        return round2(total);
    }

    function feesForMethodId(methodId) {
        var methods = cfg().methods || [];
        var methodStr = String(methodId);
        for (var i = 0; i < methods.length; i++) {
            if (String(methods[i].id) === methodStr) {
                return Array.isArray(methods[i].fees) ? methods[i].fees : [];
            }
        }
        return [];
    }

    function feesForSelectedAccount(accountId) {
        var methods = cfg().methods || [];
        var accountStr = String(accountId);
        for (var i = 0; i < methods.length; i++) {
            var method = methods[i];
            var branchAccounts = method.branch_accounts || {};
            var found = false;
            Object.keys(branchAccounts).forEach(function (estId) {
                if (String(branchAccounts[estId]) === accountStr) {
                    found = true;
                }
            });
            if (!found && String(method.account_id) === accountStr) {
                found = true;
            }
            if (found) {
                return Array.isArray(method.fees) ? method.fees : [];
            }
        }
        return [];
    }

    function currentFees() {
        var methodId = $('#payment_method_fee_demo_method').val() || '';
        var accountId = $('#account_id').val() || '';
        if (methodId) {
            return feesForMethodId(methodId);
        }
        if (accountId) {
            return feesForSelectedAccount(accountId);
        }
        return [];
    }

    function applyItemFeesToLines() {
        if (isInternalConsumption()) {
            return;
        }

        var itemFees = itemFeesOf(currentFees());
        applyingPrices = true;
        try {
            $('#salesTable tbody tr.sales-line-row').each(function () {
                var $row = $(this);
                var $field = unitPriceField($row);
                if (!$field.length || $field.prop('readonly')) {
                    return;
                }

                var productId = String($row.find('[name$="[products_id]"]').val() || '');
                var lastProductId = String($row.data('pmf-product-id') || '');
                var baseForProduct = String($field.data('pmf-base-for-product') || '');
                if (productId !== lastProductId) {
                    $row.data('pmf-product-id', productId);
                }
                if (baseForProduct !== productId) {
                    setBasePrice($row, $field.val());
                }

                var next = adjustedUnitFromBase(getBasePrice($row), itemFees);
                var current = parseFloat($field.val()) || 0;
                if (Math.abs(current - next) > 0.0001) {
                    $field.val(next.toFixed(2));
                }
            });
        } finally {
            applyingPrices = false;
        }
    }

    function applyToTotals() {
        var fees = currentFees();
        var lines = collectBaseLines();
        var orderNet = 0;
        lines.forEach(function (line) {
            orderNet += line.net;
        });
        orderNet = round2(orderNet);

        var itemAmount = computeItemFeeAmount(fees, lines);
        var orderAmount = computeOrderFeeAmount(fees, orderNet);
        var displayAmount = round2(itemAmount + orderAmount);

        return {
            feeAmount: orderAmount,
            feeTax: 0,
            itemFeeAmount: itemAmount,
            displayAmount: displayAmount,
        };
    }

    function updateDisplay(totals) {
        var result = totals || applyToTotals();
        var display = round2(result.displayAmount || 0);
        $('#pmf-total-display').text(display.toFixed(2));
        $('#pmf-amount-input').val(display.toFixed(2));
        $('#pmf-summary-card').toggleClass('d-none', display <= 0);
    }

    function refreshTotals() {
        if (typeof window.updateSalesTotals === 'function') {
            window.updateSalesTotals();
            return;
        }
        applyItemFeesToLines();
        updateDisplay();
    }

    function init() {
        if (!window.paymentMethodFeesConfig) {
            return;
        }

        $(document).on('input', '#salesTable .unit_price-field', function () {
            if (applyingPrices) {
                return;
            }
            setBasePrice($(this).closest('tr'), $(this).val());
        });

        $(document).on('change', '#account_id, #payment_method_fee_demo_method', function () {
            refreshTotals();
        });

        applyItemFeesToLines();
        updateDisplay();
    }

    $(document).ready(function () {
        init();
    });

    return {
        captureCatalogPrice: captureCatalogPrice,
        applyItemFeesToLines: applyItemFeesToLines,
        applyToTotals: applyToTotals,
        updateDisplay: updateDisplay,
    };
}());
