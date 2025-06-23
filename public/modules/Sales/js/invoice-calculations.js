function updateSalesTotals() {
    let totalBeforeVat = 0;
    let totalVat = 0;
    let totalAfterVat = 0;
    let totalBeforeDiscountForVat = 0;
    let finalTotalAfterVat = 0;

    $("#salesTable tbody tr").each(function (index) {
        const qty = parseFloat($(this).find(`[name="products[${index}][qty]"]`).val()) || 0;
        const unitPriceOriginal = parseFloat($(this).find(`[name="products[${index}][unit_price]"]`).val()) || 0;
        const discountValue = parseFloat($(this).find(`[name="products[${index}][discount]"]`).val()) || 0;
        const discountType = $(this).find(`[name="products[${index}][discount_type]"]`).val();
        const taxType = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
        const isTaxGroup = $(this).find(`[name="products[${index}][is_tax_group]"]`).val() === "1";
        const subTaxes = JSON.parse($(this).find(`[name="products[${index}][sub_taxes]"]`).val() || "[]");
        const isInclusive = $(this).find(`[name="products[${index}][inclusive]"]`).is(":checked");

        let unitPrice = unitPriceOriginal;

        let discountAmount = 0;
        if (discountType === "percent") {
            discountAmount = qty * unitPrice * (discountValue / 100);
        } else {
            discountAmount = discountValue;
        }

        let totalBeforeDiscount = qty * unitPrice - discountAmount;

        if (isInclusive) {
            if (isTaxGroup && subTaxes.length > 0) {
                // حساب الضريبة الشاملة للضريبة المركبة
                let priceBeforeTax = unitPriceOriginal;
                for (let i = subTaxes.length - 1; i >= 0; i--) {
                    priceBeforeTax = priceBeforeTax / (1 + subTaxes[i].amount / 100);
                }
                unitPrice = priceBeforeTax;
                totalBeforeDiscount = qty * unitPrice;
            } else if (taxType > 0) {
                unitPrice = (unitPriceOriginal - discountAmount) / (1 + taxType / 100);
                totalBeforeDiscount = qty * unitPrice;
            }
        }

        let vatAmount = 0;
        if (isTaxGroup && subTaxes.length > 0) {
            // حساب الضريبة المركبة
            let currentAmount = totalBeforeDiscount;
            let totalVatForRow = 0;

            for (const subTax of subTaxes) {
                const subVatAmount = currentAmount * (subTax.amount / 100);
                totalVatForRow += subVatAmount;
                currentAmount += subVatAmount;
            }

            vatAmount = totalVatForRow;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (taxType > 0) {
            vatAmount = totalBeforeDiscount * (taxType / 100);
            totalBeforeDiscountForVat += totalBeforeDiscount;
        }

        const totalRow = totalBeforeDiscount + vatAmount;

        $(this).find(".total_before_vat-field").val(totalBeforeDiscount.toFixed(2));
        $(this).find(".vat_value-field").val(vatAmount.toFixed(2));
        $(this).find(".total_after_vat-field").val(totalRow.toFixed(2));

        totalBeforeVat += totalBeforeDiscount;
        totalVat += vatAmount;
        totalAfterVat += totalRow;
    });

    const invoiceDiscount = parseFloat($("#invoice_discount").val()) || 0;
    const discountType = $("#invoiced_discount_type").val();

    let totalDiscountAmount = 0;
    if (discountType === "percent") {
        totalDiscountAmount = totalBeforeVat * (invoiceDiscount / 100);
    } else {
        totalDiscountAmount = invoiceDiscount;
    }

    const adjustedTotalForVat = totalBeforeDiscountForVat - totalDiscountAmount;

    let adjustedVat = 0;
    $("#salesTable tbody tr").each(function (index) {
        const taxType = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
        const isTaxGroup = $(this).find(`[name="products[${index}][is_tax_group]"]`).val() === "1";
        const subTaxes = JSON.parse($(this).find(`[name="products[${index}][sub_taxes]"]`).val() || "[]");
        const rowTotalBeforeDiscount = parseFloat($(this).find(".total_before_vat-field").val()) || 0;

        if (taxType > 0 || isTaxGroup) {
            const rowDiscountShare = (rowTotalBeforeDiscount / totalBeforeVat) * totalDiscountAmount;
            const rowAdjustedTotal = rowTotalBeforeDiscount - rowDiscountShare;

            if (isTaxGroup && subTaxes.length > 0) {
                let currentAmount = rowAdjustedTotal;
                let totalVatForRow = 0;

                for (const subTax of subTaxes) {
                    const subVatAmount = currentAmount * (subTax.amount / 100);
                    totalVatForRow += subVatAmount;
                    currentAmount += subVatAmount;
                }

                adjustedVat += totalVatForRow;
            } else {
                adjustedVat += rowAdjustedTotal * (taxType / 100);
            }
        }
    });

    const totalAfterDiscount = totalBeforeVat - totalDiscountAmount;
    finalTotalAfterVat = totalAfterDiscount + adjustedVat;
    finalTotalAfterVat = isNaN(finalTotalAfterVat) ? 0 : finalTotalAfterVat;

    adjustedVat = adjustedVat > 0 ? adjustedVat : 0;

    $("#totalBeforeVat").text(totalBeforeVat.toFixed(2));
    $("#input-totalBeforeVat").val(totalBeforeVat.toFixed(2));
    $("#_invoiced_discount").text(totalDiscountAmount.toFixed(2));
    $("#input-invoiced_discount").val(totalDiscountAmount.toFixed(2));
    $("#totalAfterDiscount").text(totalAfterDiscount.toFixed(2));
    $("#input-totalAfterDiscount").val(totalAfterDiscount.toFixed(2));
    $("#totalVat").text(adjustedVat.toFixed(2));
    $("#input-totalVat").val(adjustedVat.toFixed(2));
    $("#totalAfterVat").text(finalTotalAfterVat.toFixed(2));
    $("#input-totalAfterVat").val(finalTotalAfterVat.toFixed(2));

    if ($("#invoice_type").val() === "due") {
        $("#paid_amount").val(0);
    } else {
        $("#paid_amount").val(finalTotalAfterVat.toFixed(2));
    }
}
