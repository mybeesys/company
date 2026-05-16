function updateSalesTotals() {
    let totalBeforeVat = 0;
    let totalVat = 0;
    let totalAfterVat = 0;
    let totalBeforeDiscountForVat = 0;
    let finalTotalAfterVat = 0;

    $("#salesTable tbody tr").each(function (index) {
        const qty =
            parseFloat(
                $(this).find(`[name="products[${index}][qty]"]`).val()
            ) || 0;
        const unitPriceOriginal =
            parseFloat(
                $(this).find(`[name="products[${index}][unit_price]"]`).val()
            ) || 0;
        const discountValue =
            parseFloat(
                $(this).find(`[name="products[${index}][discount]"]`).val()
            ) || 0;
        const discountType = $(this)
            .find(`[name="products[${index}][discount_type]"]`)
            .val();
        const taxType =
            parseFloat(
                $(this).find(`[name="products[${index}][tax_vat]"]`).val()
            ) || 0;
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

        const lineGross = qty * unitPriceOriginal;

        let discountAmount = 0;
        if (discountType === "percent") {
            discountAmount = lineGross * (discountValue / 100);
        } else {
            discountAmount = discountValue;
        }

        const lineAfterDiscount = Math.max(0, lineGross - discountAmount);

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
                const rate = parseFloat(subTaxes[i].amount) || 0;
                if (rate > 0) {
                    netUnit = netUnit / (1 + rate / 100);
                }
            }
            totalBeforeDiscount = qty * netUnit;
            vatAmount = Math.max(0, lineAfterDiscount - totalBeforeDiscount);
            totalRow = lineAfterDiscount;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (isInclusive && taxType > 0) {
            totalBeforeDiscount = lineAfterDiscount / (1 + taxType / 100);
            vatAmount = lineAfterDiscount - totalBeforeDiscount;
            totalRow = lineAfterDiscount;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (isTaxGroup && subTaxes.length === 2) {
            let tax_1 = parseFloat(subTaxes[0].amount) || 0;
            let tax_2 = parseFloat(subTaxes[1].amount) || 0;
            let tax_1_minimum_limit = parseFloat(minimumLimits[0]) || 0;
            let tax_2_minimum_limit = parseFloat(minimumLimits[1]) || 0;

            let taxableBase = totalBeforeDiscount;
            let tax1Amount = taxableBase * (tax_1 / 100);
            if (tax_1_minimum_limit > 0 && tax1Amount < tax_1_minimum_limit) {
                tax1Amount = tax_1_minimum_limit;
            }
            let baseAfterTax1 = taxableBase + tax1Amount;
            let tax2Amount = baseAfterTax1 * (tax_2 / 100);
            if (tax_2_minimum_limit > 0 && tax2Amount < tax_2_minimum_limit) {
                tax2Amount = tax_2_minimum_limit;
            }
            vatAmount = tax1Amount + tax2Amount;
            totalRow = totalBeforeDiscount + vatAmount;
            totalBeforeDiscountForVat += totalBeforeDiscount;
        } else if (taxType > 0) {
            vatAmount = totalBeforeDiscount * (taxType / 100);
            if (minimumLimits[0] && vatAmount < minimumLimits[0]) {
                vatAmount = minimumLimits[0];
            }
            totalBeforeDiscountForVat += totalBeforeDiscount;
            totalRow = totalBeforeDiscount + vatAmount;
        }

        $(this)
            .find(".total_before_vat-field")
            .val(totalBeforeDiscount.toFixed(2));
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

    let adjustedVat = 0;
    $("#salesTable tbody tr").each(function (index) {
        const taxType =
            parseFloat(
                $(this).find(`[name="products[${index}][tax_vat]"]`).val()
            ) || 0;
        const isInclusive = $(this)
            .find(`[name="products[${index}][inclusive]"]`)
            .is(":checked");
        const rowVat =
            parseFloat($(this).find(".vat_value-field").val()) || 0;
        const rowTotalBeforeDiscount =
            parseFloat($(this).find(".total_before_vat-field").val()) || 0;
        const rowAfterVat =
            parseFloat($(this).find(".total_after_vat-field").val()) || 0;

        if (isInclusive && taxType > 0 && rowAfterVat > 0) {
            const rowDiscountShare =
                totalBeforeVat > 0
                    ? (rowTotalBeforeDiscount / totalBeforeVat) *
                      totalDiscountAmount
                    : 0;
            const adjustedNet = Math.max(
                0,
                rowTotalBeforeDiscount - rowDiscountShare
            );
            const adjustedGross =
                rowTotalBeforeDiscount > 0
                    ? rowAfterVat * (adjustedNet / rowTotalBeforeDiscount)
                    : 0;
            adjustedVat += Math.max(0, adjustedGross - adjustedNet);
        } else if (taxType > 0) {
            const rowDiscountShare =
                totalBeforeVat > 0
                    ? (rowTotalBeforeDiscount / totalBeforeVat) *
                      totalDiscountAmount
                    : 0;
            const rowAdjustedTotal =
                rowTotalBeforeDiscount - rowDiscountShare;
            adjustedVat += rowAdjustedTotal * (taxType / 100);
        } else {
            adjustedVat += rowVat;
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
