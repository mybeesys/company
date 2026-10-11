window.BeeMoney = window.BeeMoney || {
    parse: function (value) {
        if (value === null || value === undefined || value === "") {
            return 0;
        }
        if (typeof value === "number") {
            return Number.isFinite(value) ? value : 0;
        }
        var s = String(value).trim();
        if (!s || s === "-" || s === "." || s === ",") {
            return 0;
        }
        var eastern = "٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹";
        var western = "01234567890123456789";
        var out = "";
        for (var i = 0; i < s.length; i++) {
            var idx = eastern.indexOf(s.charAt(i));
            out += idx >= 0 ? western.charAt(idx) : s.charAt(i);
        }
        s = out.replace(/٫/g, ".").replace(/٬/g, ",").replace(/[\s\u00a0\u202f]/g, "");
        var lastComma = s.lastIndexOf(",");
        var lastDot = s.lastIndexOf(".");
        if (lastComma >= 0 && lastDot >= 0) {
            if (lastComma > lastDot) {
                s = s.replace(/\./g, "").replace(",", ".");
            } else {
                s = s.replace(/,/g, "");
            }
        } else if (lastComma >= 0) {
            var after = s.length - lastComma - 1;
            if (after === 3 && /^\d{1,3}(,\d{3})+$/.test(s)) {
                s = s.replace(/,/g, "");
            } else {
                s = s.replace(",", ".");
            }
        }
        var n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    },
    round: function (value) {
        return Math.round((this.parse(value) + Number.EPSILON) * 100) / 100;
    },
    format: function (value) {
        return this.round(value).toFixed(2);
    },
    exclusiveTaxedLine: function (qty, net, rate) {
        net = this.round(net);
        rate = this.parse(rate);
        if (!(rate > 0)) {
            return { net: net, vat: 0, gross: net };
        }
        if (!(this.parse(qty) > 0)) {
            var vatOnly = this.round(net * (rate / 100));
            return { net: net, vat: vatOnly, gross: this.round(net + vatOnly) };
        }
        var unitGross = this.round((net / this.parse(qty)) * (1 + rate / 100));
        var gross = this.round(unitGross * this.parse(qty));
        var vat = this.round(gross - net);
        if (vat < 0) {
            vat = 0;
            gross = net;
        }
        return { net: net, vat: vat, gross: gross };
    },
};

function updateSalesTotals() {
    if (window.PaymentMethodFees && typeof window.PaymentMethodFees.applyItemFeesToLines === "function") {
        try {
            window.PaymentMethodFees.applyItemFeesToLines();
        } catch (e) {
            /* never block invoice totals */
        }
    }

    let totalBeforeVat = 0;
    let totalVat = 0;
    let totalAfterVat = 0;
    let totalBeforeDiscountForVat = 0;
    let finalTotalAfterVat = 0;

    $("#salesTable tbody tr").each(function (index) {
        const qty = BeeMoney.parse(
            $(this).find(`[name="products[${index}][qty]"]`).val()
        );
        const unitPriceOriginal = BeeMoney.parse(
            $(this).find(`[name="products[${index}][unit_price]"]`).val()
        );
        const discountValue = BeeMoney.parse(
            $(this).find(`[name="products[${index}][discount]"]`).val()
        );
        const discountType = $(this)
            .find(`[name="products[${index}][discount_type]"]`)
            .val();
        const taxType = BeeMoney.parse(
            $(this).find(`[name="products[${index}][tax_vat]"]`).val()
        );
        const isTaxGroup =
            $(this).find(`[name="products[${index}][is_tax_group]"]`).val() ===
            "1";
        const subTaxes = JSON.parse(
            $(this).find(`[name="products[${index}][sub_taxes]"]`).val() || "[]"
        );
        const isInclusive = $(this)
            .find(`[name="products[${index}][inclusive]"]`)
            .is(":checked");
        const minimumLimits = JSON.parse(
            $(this).find(`[name="products[${index}][minimum_limits]"]`).val() ||
                "[]"
        );

        const lineGross = BeeMoney.round(qty * unitPriceOriginal);

        let discountAmount = 0;
        if (discountType === "percent") {
            discountAmount = BeeMoney.round(lineGross * (discountValue / 100));
        } else {
            discountAmount = BeeMoney.round(discountValue);
        }

        const lineAfterDiscount = BeeMoney.round(Math.max(0, lineGross - discountAmount));

        let totalBeforeDiscount = lineAfterDiscount;
        let vatAmount = 0;
        let totalRow = lineAfterDiscount;

        if (isInclusive && isTaxGroup && subTaxes.length > 0) {
            let inclusiveUnit = unitPriceOriginal;
            if (discountType === "percent" && discountValue > 0) {
                inclusiveUnit = unitPriceOriginal * (1 - discountValue / 100);
            } else if (discountType !== "percent" && qty > 0) {
                inclusiveUnit = Math.max(
                    0,
                    unitPriceOriginal - discountAmount / qty
                );
            }
            let netUnit = inclusiveUnit;
            for (let i = subTaxes.length - 1; i >= 0; i--) {
                const rate = BeeMoney.parse(subTaxes[i].amount);
                if (rate > 0) {
                    netUnit = netUnit / (1 + rate / 100);
                }
            }
            totalBeforeDiscount = BeeMoney.round(qty * netUnit);
            vatAmount = BeeMoney.round(Math.max(0, lineAfterDiscount - totalBeforeDiscount));
            totalRow = lineAfterDiscount;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (isInclusive && taxType > 0) {
            totalBeforeDiscount = BeeMoney.round(
                lineAfterDiscount / (1 + taxType / 100)
            );
            vatAmount = BeeMoney.round(lineAfterDiscount - totalBeforeDiscount);
            totalRow = lineAfterDiscount;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (isTaxGroup && subTaxes.length === 2) {
            let tax_1 = BeeMoney.parse(subTaxes[0].amount);
            let tax_2 = BeeMoney.parse(subTaxes[1].amount);
            let tax_1_minimum_limit = BeeMoney.parse(minimumLimits[0]);
            let tax_2_minimum_limit = BeeMoney.parse(minimumLimits[1]);

            let taxableBase = totalBeforeDiscount;
            let tax1Amount = BeeMoney.round(taxableBase * (tax_1 / 100));
            if (tax_1_minimum_limit > 0 && tax1Amount < tax_1_minimum_limit) {
                tax1Amount = tax_1_minimum_limit;
            }
            let baseAfterTax1 = BeeMoney.round(taxableBase + tax1Amount);
            let tax2Amount = BeeMoney.round(baseAfterTax1 * (tax_2 / 100));
            if (tax_2_minimum_limit > 0 && tax2Amount < tax_2_minimum_limit) {
                tax2Amount = tax_2_minimum_limit;
            }
            vatAmount = BeeMoney.round(tax1Amount + tax2Amount);
            totalRow = BeeMoney.round(totalBeforeDiscount + vatAmount);
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (taxType > 0) {
            const taxed = BeeMoney.exclusiveTaxedLine(qty, totalBeforeDiscount, taxType);
            vatAmount = taxed.vat;
            totalRow = taxed.gross;
            if (minimumLimits[0] && vatAmount < BeeMoney.parse(minimumLimits[0])) {
                vatAmount = BeeMoney.parse(minimumLimits[0]);
                totalRow = BeeMoney.round(totalBeforeDiscount + vatAmount);
            }
            totalBeforeDiscountForVat += totalBeforeDiscount;
        }

        $(this)
            .find(".total_before_vat-field")
            .val(BeeMoney.format(totalBeforeDiscount));
        $(this).find(".vat_value-field").val(BeeMoney.format(vatAmount));
        $(this).find(".total_after_vat-field").val(BeeMoney.format(totalRow));

        totalBeforeVat += totalBeforeDiscount;
        totalVat += vatAmount;
        totalAfterVat += totalRow;

        const extrasBefore =
            BeeMoney.parse($(this).find(".smc-extras-before-vat").val()) ||
            BeeMoney.parse(
                $(this).find(`[name="products[${index}][extras_before_vat]"]`).val()
            );
        const extrasInc =
            BeeMoney.parse($(this).find(".smc-extras-inc-tax").val()) ||
            BeeMoney.parse(
                $(this).find(`[name="products[${index}][extras_inc_tax]"]`).val()
            );
        if (extrasBefore > 0 || extrasInc > 0) {
            const extrasVat = Math.max(0, extrasInc - extrasBefore);
            // Extras are stored as child sell lines — keep parent row fields product-only,
            // but include extras in invoice-level totals.
            totalBeforeVat += extrasBefore;
            totalVat += extrasVat;
            totalAfterVat += extrasInc;
            totalBeforeDiscountForVat += extrasBefore;
        }
    });

    totalBeforeVat = BeeMoney.round(totalBeforeVat);
    totalVat = BeeMoney.round(totalVat);
    totalAfterVat = BeeMoney.round(totalAfterVat);

    const invoiceDiscount = BeeMoney.parse($("#invoice_discount").val());
    const discountType = $("#invoiced_discount_type").val();

    let totalDiscountAmount = 0;
    if (discountType === "percent") {
        totalDiscountAmount = BeeMoney.round(totalBeforeVat * (invoiceDiscount / 100));
    } else {
        totalDiscountAmount = BeeMoney.round(invoiceDiscount);
    }

    let adjustedVat = 0;
    $("#salesTable tbody tr").each(function (index) {
        const taxType = BeeMoney.parse(
            $(this).find(`[name="products[${index}][tax_vat]"]`).val()
        );
        const isInclusive = $(this)
            .find(`[name="products[${index}][inclusive]"]`)
            .is(":checked");
        const rowVat = BeeMoney.parse($(this).find(".vat_value-field").val());
        const rowTotalBeforeDiscount = BeeMoney.parse(
            $(this).find(".total_before_vat-field").val()
        );
        const rowAfterVat = BeeMoney.parse(
            $(this).find(".total_after_vat-field").val()
        );

        if (isInclusive && taxType > 0 && rowAfterVat > 0) {
            const rowDiscountShare =
                totalBeforeVat > 0
                    ? BeeMoney.round(
                          (rowTotalBeforeDiscount / totalBeforeVat) *
                              totalDiscountAmount
                      )
                    : 0;
            const adjustedNet = BeeMoney.round(
                Math.max(0, rowTotalBeforeDiscount - rowDiscountShare)
            );
            const adjustedGross =
                rowTotalBeforeDiscount > 0
                    ? BeeMoney.round(
                          rowAfterVat * (adjustedNet / rowTotalBeforeDiscount)
                      )
                    : 0;
            adjustedVat += BeeMoney.round(
                Math.max(0, adjustedGross - adjustedNet)
            );
        } else if (taxType > 0) {
            const rowDiscountShare =
                totalBeforeVat > 0
                    ? BeeMoney.round(
                          (rowTotalBeforeDiscount / totalBeforeVat) *
                              totalDiscountAmount
                      )
                    : 0;
            const rowAdjustedTotal = BeeMoney.round(
                rowTotalBeforeDiscount - rowDiscountShare
            );
            const qty = BeeMoney.parse(
                $(this).find(`[name="products[${index}][qty]"]`).val()
            );
            adjustedVat += BeeMoney.exclusiveTaxedLine(
                qty,
                rowAdjustedTotal,
                taxType
            ).vat;
        } else {
            adjustedVat += BeeMoney.round(rowVat);
        }
    });

    const totalAfterDiscount = BeeMoney.round(totalBeforeVat - totalDiscountAmount);
    adjustedVat = BeeMoney.round(Math.max(0, adjustedVat));
    finalTotalAfterVat = BeeMoney.round(totalAfterDiscount + adjustedVat);
    finalTotalAfterVat = isNaN(finalTotalAfterVat) ? 0 : finalTotalAfterVat;

    let serviceFeeAmount = 0;
    let serviceFeeTax = 0;
    if (window.InvoiceServiceFees && typeof window.InvoiceServiceFees.applyToTotals === "function") {
        try {
            const serviceFees = window.InvoiceServiceFees.applyToTotals({
                lines: (typeof window.InvoiceServiceFees.collectLines === "function")
                    ? window.InvoiceServiceFees.collectLines()
                    : [],
                subtotalAfterDiscount: totalAfterDiscount,
                productVat: adjustedVat,
                productTotal: finalTotalAfterVat,
            }) || {};
            serviceFeeAmount = BeeMoney.round(serviceFees.feeAmount || 0);
            serviceFeeTax = BeeMoney.round(serviceFees.feeTax || 0);
            adjustedVat = BeeMoney.round(adjustedVat + serviceFeeTax);
            finalTotalAfterVat = BeeMoney.round(finalTotalAfterVat + serviceFeeAmount + serviceFeeTax);
        } catch (e) {
            serviceFeeAmount = 0;
            serviceFeeTax = 0;
        }
    }

    let paymentMethodFeeAmount = 0;
    let paymentMethodFeeTax = 0;
    let paymentMethodFeeDisplay = 0;
    if (window.PaymentMethodFees && typeof window.PaymentMethodFees.applyToTotals === "function") {
        try {
            const paymentMethodFees = window.PaymentMethodFees.applyToTotals({
                subtotalAfterDiscount: totalAfterDiscount,
                productVat: adjustedVat,
                productTotal: finalTotalAfterVat,
            }) || {};
            paymentMethodFeeAmount = BeeMoney.round(paymentMethodFees.feeAmount || 0);
            paymentMethodFeeTax = BeeMoney.round(paymentMethodFees.feeTax || 0);
            paymentMethodFeeDisplay = BeeMoney.round(paymentMethodFees.displayAmount || paymentMethodFeeAmount);
            adjustedVat = BeeMoney.round(adjustedVat + paymentMethodFeeTax);
            finalTotalAfterVat = BeeMoney.round(finalTotalAfterVat + paymentMethodFeeAmount + paymentMethodFeeTax);
        } catch (e) {
            paymentMethodFeeAmount = 0;
            paymentMethodFeeTax = 0;
            paymentMethodFeeDisplay = 0;
        }
    }

    $("#totalBeforeVat").text(BeeMoney.format(totalBeforeVat));
    $("#input-totalBeforeVat").val(BeeMoney.format(totalBeforeVat));
    $("#_invoiced_discount").text(BeeMoney.format(totalDiscountAmount));
    $("#input-invoiced_discount").val(BeeMoney.format(totalDiscountAmount));
    $("#totalAfterDiscount").text(BeeMoney.format(totalAfterDiscount));
    $("#input-totalAfterDiscount").val(BeeMoney.format(totalAfterDiscount));
    $("#totalVat").text(BeeMoney.format(adjustedVat));
    $("#input-totalVat").val(BeeMoney.format(adjustedVat));
    $("#totalAfterVat").text(BeeMoney.format(finalTotalAfterVat));
    $("#input-totalAfterVat").val(BeeMoney.format(finalTotalAfterVat));

    if ($("#pmf-total-display").length) {
        $("#pmf-total-display").text(BeeMoney.format(paymentMethodFeeDisplay));
        $("#pmf-amount-input").val(BeeMoney.format(paymentMethodFeeDisplay));
        $("#pmf-summary-card").toggleClass("d-none", paymentMethodFeeDisplay <= 0);
    }

    const invoiceType = String($("#invoice_type").val() || "").toLowerCase();
    if (invoiceType !== "due" && invoiceType !== "credit") {
        $("#paid_amount").val(BeeMoney.format(finalTotalAfterVat));
    }
}
