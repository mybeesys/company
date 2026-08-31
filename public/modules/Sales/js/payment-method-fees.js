/**
 * payment-method-fees.js
 * رسوم طرق الدفع — تُطبَّق عند اختيار طريقة الدفع.
 */
window.PaymentMethodFees = (function () {
    'use strict';

    var FEE_TYPE_PERCENT = '1';
    var APPLY_ITEM = '0';
    var CALC_AFTER_TAX = '1';
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

    function effectiveLineTaxRate(line) {
        var declared = parseFloat(line.tax_rate) || 0;
        if (declared > 0) {
            return declared;
        }
        var net = parseFloat(line.net) || 0;
        var vat = parseFloat(line.vat) || 0;
        if (net <= 0.0001 || vat <= 0) {
            return 0;
        }
        return (vat / net) * 100;
    }

    function readSalesLine($row) {
        var qty = parseFloat($row.find('.qty-field').val()) || 0;
        var net = parseFloat($row.find('.total_before_vat-field').val()) || 0;
        var vat = parseFloat($row.find('.vat_value-field').val()) || 0;
        var gross = parseFloat($row.find('.total_after_vat-field').val()) || 0;
        var taxRate = parseFloat($row.find("select[name*='[tax_vat]']").val()) || 0;
        if (taxRate <= 0 && net > 0 && vat > 0) {
            taxRate = (vat / net) * 100;
        }

        return {
            qty: qty,
            net: net,
            vat: vat,
            gross: gross,
            tax_rate: taxRate,
        };
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

    function lineTaxRateForRow($row) {
        var taxRate = parseFloat($row.find("select[name*='[tax_vat]']").val()) || 0;
        if (taxRate > 0) {
            return taxRate;
        }
        var qty = parseFloat($row.find('.qty-field').val()) || 0;
        var net = parseFloat($row.find('.total_before_vat-field').val()) || 0;
        var vat = parseFloat($row.find('.vat_value-field').val()) || 0;
        if (qty > 0 && net > 0 && vat > 0) {
            return (vat / net) * 100;
        }
        return 0;
    }

    function itemExtraPerUnit(baseUnit, qty, taxRate, fee) {
        var amount = parseFloat(fee.amount) || 0;
        var afterTax = String(fee.calculation_method || '0') === CALC_AFTER_TAX;
        if (String(fee.fee_type || '0') === FEE_TYPE_PERCENT) {
            var lineNet = Math.max(0, baseUnit * qty);
            var lineVat = lineNet * (taxRate / 100);
            var lineGross = lineNet + lineVat;
            var base = afterTax ? lineGross : lineNet;
            var feeTotal = base * (amount / 100);
            return qty > 0 ? feeTotal / qty : 0;
        }
        return amount;
    }

    function adjustedUnitFromBase(base, qty, taxRate, itemFees) {
        var extra = 0;
        (itemFees || []).forEach(function (fee) {
            extra += itemExtraPerUnit(base, qty, taxRate, fee);
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

    function collectContextLines() {
        var lines = [];
        $('#salesTable tbody tr.sales-line-row').each(function () {
            lines.push(readSalesLine($(this)));
        });
        return lines;
    }

    function computeFee(fee, context) {
        var isPercent = String(fee.fee_type || '0') === FEE_TYPE_PERCENT;
        var isItem = String(fee.application_type || '0') === APPLY_ITEM;
        var afterTax = String(fee.calculation_method || '0') === CALC_AFTER_TAX;
        var rateOrAmount = parseFloat(fee.amount) || 0;
        var taxable = !!fee.taxable;
        var feeAmount = 0;
        var taxAmount = 0;
        var lines = Array.isArray(context.lines) ? context.lines : [];

        if (isItem) {
            lines.forEach(function (line) {
                if (line.qty <= 0) {
                    return;
                }
                var lineNet = parseFloat(line.net) || 0;
                var lineGross = parseFloat(line.gross) || lineNet;
                var lineRate = effectiveLineTaxRate(line);
                var lineFee = 0;

                if (isPercent) {
                    var base = afterTax ? lineGross : lineNet;
                    lineFee = base * (rateOrAmount / 100);
                } else {
                    lineFee = rateOrAmount * line.qty;
                }

                lineFee = Math.max(0, round2(lineFee));
                var lineTax = 0;
                if (taxable) {
                    lineTax = round2(lineFee * (lineRate / 100));
                }
                feeAmount += lineFee;
                taxAmount += lineTax;
            });
        } else {
            if (isPercent) {
                var orderBase = afterTax
                    ? (parseFloat(context.productTotal) || 0)
                    : (parseFloat(context.subtotalAfterDiscount) || 0);
                feeAmount = Math.max(0, round2(orderBase * (rateOrAmount / 100)));
            } else {
                feeAmount = Math.max(0, round2(rateOrAmount));
            }

            if (taxable && feeAmount > 0) {
                var net = parseFloat(context.subtotalAfterDiscount) || 0;
                var vat = parseFloat(context.productVat) || 0;
                var effectiveRate = net > 0.0001 ? (vat / net) : 0;
                taxAmount = round2(feeAmount * effectiveRate);
            }
        }

        return {
            feeAmount: round2(feeAmount),
            taxAmount: round2(taxAmount),
        };
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

                var qty = parseFloat($row.find('.qty-field').val()) || 0;
                var taxRate = lineTaxRateForRow($row);
                var next = adjustedUnitFromBase(getBasePrice($row), qty, taxRate, itemFees);
                var current = parseFloat($field.val()) || 0;
                if (Math.abs(current - next) > 0.0001) {
                    $field.val(next.toFixed(2));
                }
            });
        } finally {
            applyingPrices = false;
        }
    }

    function applyToTotals(context) {
        var fees = currentFees();
        var lines = collectContextLines();
        var baseLines = collectBaseLines();
        var orderNet = 0;
        baseLines.forEach(function (line) {
            orderNet += line.net;
        });

        var ctx = {
            lines: lines,
            subtotalAfterDiscount: context && context.subtotalAfterDiscount !== undefined
                ? context.subtotalAfterDiscount
                : round2(orderNet),
            productVat: context && context.productVat !== undefined ? context.productVat : 0,
            productTotal: context && context.productTotal !== undefined ? context.productTotal : round2(orderNet),
        };

        var orderFeeAmount = 0;
        var feeTax = 0;
        var itemDisplay = 0;

        activeFees(fees).forEach(function (fee) {
            var computed = computeFee(fee, ctx);
            if (String(fee.application_type || '1') === APPLY_ITEM) {
                itemDisplay += computed.feeAmount;
            } else {
                orderFeeAmount += computed.feeAmount;
            }
            feeTax += computed.taxAmount;
        });

        var displayAmount = round2(itemDisplay + orderFeeAmount);

        return {
            feeAmount: round2(orderFeeAmount),
            feeTax: round2(feeTax),
            itemFeeAmount: round2(itemDisplay),
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
