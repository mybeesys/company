//  ممنوع من الحذف  -- مراجعة محمد امين قبل الحذف :)

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
                let priceBeforeTax = unitPriceOriginal;
                for (let i = subTaxes.length - 1; i >= 0; i--) {
                    priceBeforeTax =
                        priceBeforeTax / (1 + subTaxes[i].amount / 100);
                }
                unitPrice = priceBeforeTax;
                totalBeforeDiscount = qty * unitPrice;
            } else if (taxType > 0) {
                unitPrice =
                    (unitPriceOriginal - discountAmount) / (1 + taxType / 100);
                totalBeforeDiscount = qty * unitPrice;
            }
        }

        let vatAmount = 0;
        let totalRow = 0;
        if (isTaxGroup && subTaxes.length === 2) {
            let tax_1 = subTaxes[0].amount;
            let tax_2 = subTaxes[1].amount;
            let tax_1_minimum_limit = minimumLimits[0];
            let tax_2_minimum_limit = minimumLimits[1];
            totalBeforeDiscountForVat = totalBeforeDiscount;
            totalBeforeDiscountForVat +=
                totalBeforeDiscountForVat * (tax_1 / 100);

            if (totalBeforeDiscountForVat < tax_1_minimum_limit) {
                totalBeforeDiscountForVat =
                    totalBeforeDiscountForVat + tax_1_minimum_limit;
            }

            let tax__2= totalBeforeDiscountForVat * (tax_2 / 100);
            totalBeforeDiscountForVat +=
                totalBeforeDiscountForVat * (tax_2 / 100);

            if (totalBeforeDiscountForVat < tax_2_minimum_limit) {
                totalBeforeDiscountForVat =
                    totalBeforeDiscountForVat + tax_1_minimum_limit;
            }

            vatAmount = tax_1 + tax__2;

            const finalAmount = totalBeforeDiscountForVat;



            totalRow = finalAmount;

            // totalBeforeDiscountForVat += totalBeforeDiscount;
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


    ////////////////////////------
//    const adjustedTotalForVat = totalBeforeDiscountForVat - totalDiscountAmount;

//     let adjustedVat = 0;
//     $("#salesTable tbody tr").each(function (index) {
//         const taxType =
//             parseFloat(
//                 $(this).find(`[name="products[${index}][tax_vat]"]`).val()
//             ) || 0;
//         const rowTotalBeforeDiscount =
//             parseFloat($(this).find(".total_before_vat-field").val()) || 0;

//         if (taxType > 0) {
//             const rowDiscountShare =
//                 (rowTotalBeforeDiscount / totalBeforeVat) * totalDiscountAmount;
//             const rowAdjustedTotal = rowTotalBeforeDiscount - rowDiscountShare;
//             adjustedVat += rowAdjustedTotal * (taxType / 100);
//         }
//     });

//     const totalAfterDiscount = totalBeforeVat - totalDiscountAmount;
//     finalTotalAfterVat = totalAfterDiscount + adjustedVat;
//     finalTotalAfterVat= isNaN(finalTotalAfterVat) ? 0 : finalTotalAfterVat;

//     adjustedVat = adjustedVat > 0 ? adjustedVat : 0;

//     $("#totalBeforeVat").text(totalBeforeVat.toFixed(2));
//     $("#input-totalBeforeVat").val(totalBeforeVat.toFixed(2));
//     $("#_invoiced_discount").text(totalDiscountAmount.toFixed(2));
//     $("#input-invoiced_discount").val(totalDiscountAmount.toFixed(2));
//     $("#totalAfterDiscount").text(totalAfterDiscount.toFixed(2));
//     $("#input-totalAfterDiscount").val(totalAfterDiscount.toFixed(2));
//     $("#totalVat").text(adjustedVat.toFixed(2));
//     $("#input-totalVat").val(adjustedVat.toFixed(2));
//     $("#totalAfterVat").text(finalTotalAfterVat.toFixed(2));
//     $("#input-totalAfterVat").val(finalTotalAfterVat.toFixed(2));

//     if ($("#invoice_type").val() === "due") {
//         $("#paid_amount").val(0);
//     } else {
//         $("#paid_amount").val(finalTotalAfterVat.toFixed(2));
//     }
// }



   const adjustedTotalForVat = totalBeforeDiscountForVat - totalDiscountAmount;

let adjustedVat = 0;
$("#salesTable tbody tr").each(function (index) {
    const taxType = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
    const isTaxGroup = $(this).find(`[name="products[${index}][is_tax_group]"]`).val() === "1";
    const subTaxes = JSON.parse($(this).find(`[name="products[${index}][sub_taxes]"]`).val() || "[]");
    const rowTotalBeforeDiscount = parseFloat($(this).find(".total_before_vat-field").val()) || 0;
    const minimumLimits = JSON.parse($(this).find(`[name="products[${index}][minimum_limits]"]`).val() || "[]");

    if (taxType > 0 || isTaxGroup) {
        const rowDiscountShare = (rowTotalBeforeDiscount / totalBeforeVat) * totalDiscountAmount;
        const rowAdjustedTotal = rowTotalBeforeDiscount - rowDiscountShare;

        if (isTaxGroup && subTaxes.length === 2) {
            const tax_1 = subTaxes[0].amount;
            const tax_2 = subTaxes[1].amount;
            const tax_1_minimum_limit = minimumLimits[0] || 0;
            const tax_2_minimum_limit = minimumLimits[1] || 0;

            let firstTaxAmount = rowAdjustedTotal * (tax_1 / 100);
            if (firstTaxAmount < tax_1_minimum_limit) {
                firstTaxAmount = tax_1_minimum_limit;
            }

            let amountAfterFirstTax = rowAdjustedTotal + firstTaxAmount;

            let secondTaxAmount = amountAfterFirstTax * (tax_2 / 100);
            if (secondTaxAmount < tax_2_minimum_limit) {
                secondTaxAmount = tax_2_minimum_limit;
            }

            adjustedVat += firstTaxAmount + secondTaxAmount;
        } else if (taxType > 0) {
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
