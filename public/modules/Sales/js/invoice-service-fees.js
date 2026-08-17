window.InvoiceServiceFees = (function () {
    const TYPE_PERCENT = "1";
    const APPLY_ITEM = "0";
    const CALC_AFTER_TAX = "1";
    const AUTO_DINING = "0";
    const AUTO_GUEST = "1";
    const AUTO_PAYMENT = "2";
    const AUTO_TIME = "3";

    let userTouched = {};

    function config() {
        return window.invoiceServiceFeesConfig || {
            fees: [],
            defaultEstablishmentId: 0,
            locale: "ar",
            enabled: true,
        };
    }

    function round2(value) {
        return Math.round((Number(value) || 0) * 100) / 100;
    }

    function currentEstablishmentId() {
        if ($("#toggleStorehouse").is(":checked")) {
            const selected = $("#storehouse").val();
            if (selected) {
                return String(selected);
            }
        }
        return String(config().defaultEstablishmentId || $("#storehouse").val() || "");
    }

    function isFeatureEnabled() {
        const $toggle = $("#toggleServiceFees");
        if ($toggle.length) {
            return $toggle.is(":checked");
        }

        return config().enabled !== false;
    }

    function isInternalConsumption() {
        return typeof isInternalConsumptionInvoiceMode === "function" && isInternalConsumptionInvoiceMode();
    }

    function shouldShowUi() {
        return isFeatureEnabled() && !isInternalConsumption() && feesForEstablishment().length > 0;
    }

    function clearAmounts() {
        $("#input-service_fee_amount").val("0.00");
        $("#input-service_fee_tax").val("0.00");
        $("#totalServiceFees").text("0.00");
        $("#input-service_fees_ready").val("0");
        try {
            hideLineFeeMarks();
            $(".invoice-service-fee-lines").addClass("d-none").empty();
        } catch (e) {
            /* never block invoice totals */
        }
    }

    function syncVisibility() {
        const show = shouldShowUi();
        $("#invoice-service-fees-wrap, #service-fee-summary-card").toggleClass("d-none", !show);
        $("#input-service_fees_ready").val(show ? "1" : "0");
        return show;
    }

    function feesForEstablishment() {
        const establishmentId = String(currentEstablishmentId());
        return (config().fees || []).filter(function (fee) {
            if (!(fee.is_active || fee.active)) {
                return false;
            }
            const assigned = Array.isArray(fee.establishment_ids)
                ? fee.establishment_ids.map(String).filter(Boolean)
                : [];
            if (assigned.length) {
                return assigned.indexOf(establishmentId) !== -1;
            }
            return String(fee.establishment_id || "") === establishmentId;
        });
    }

    function cashAccountId() {
        return parseInt($("#cash_account").val() || $("#account_id").val() || "0", 10) || 0;
    }

    function transactionDate() {
        return $("#transaction_date").val() || "";
    }

    function shouldAutoApply(fee) {
        const type = String(fee.auto_apply_type || "");
        if (type === "") {
            return true;
        }
        if (type === AUTO_PAYMENT) {
            const accountId = parseInt(fee.payment_account_id || "0", 10) || 0;
            return accountId > 0 && accountId === cashAccountId();
        }
        if (type === AUTO_TIME) {
            const at = transactionDate() ? new Date(transactionDate()) : new Date();
            if (Number.isNaN(at.getTime())) {
                return false;
            }
            if (fee.from_date && at < new Date(fee.from_date)) {
                return false;
            }
            if (fee.to_date && at > new Date(fee.to_date)) {
                return false;
            }
            return !!(fee.from_date || fee.to_date);
        }
        if (type === AUTO_GUEST || type === AUTO_DINING) {
            return false;
        }
        return false;
    }

    function readSalesLine($row) {
        const qty = parseFloat($row.find(".qty-field").val()) || 0;
        const net = parseFloat($row.find(".total_before_vat-field").val()) || 0;
        const vat = parseFloat($row.find(".vat_value-field").val()) || 0;
        const gross = parseFloat($row.find(".total_after_vat-field").val()) || 0;
        let taxRate = parseFloat($row.find(".qty-field").closest("tr").find('[name$="[tax_vat]"]').first().val()) || 0;
        if (!taxRate) {
            taxRate = parseFloat($row.find("select[name*='[tax_vat]']").val()) || 0;
        }
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

    function collectLines() {
        const lines = [];
        try {
            $("#salesTable tbody tr.sales-line-row").each(function () {
                try {
                    lines.push(readSalesLine($(this)));
                } catch (e) {
                    lines.push({ qty: 0, net: 0, vat: 0, gross: 0, tax_rate: 0 });
                }
            });
        } catch (e) {
            return lines;
        }
        return lines;
    }

    function lineLabel(index) {
        const template = (config().i18n && config().i18n.lineN) || (config().locale === "ar" ? "سطر :n" : "Line :n");
        return String(template).replace(":n", String(index + 1));
    }

    function lineFeePrefix() {
        return (config().i18n && config().i18n.onLine) || (config().locale === "ar" ? "رسوم السطر" : "Line fee");
    }

    function formatMoney(feeAmount, taxAmount) {
        const fee = round2(feeAmount);
        const tax = round2(taxAmount);
        if (tax > 0) {
            const vatTpl = (config().i18n && config().i18n.plusVat) || (config().locale === "ar" ? " + ضريبة :n" : " + VAT :n");
            return fee.toFixed(2) + String(vatTpl).replace(":n", tax.toFixed(2));
        }
        return fee.toFixed(2);
    }

    function hideLineFeeMarks() {
        const $rows = $("#salesTable tbody tr.sales-line-row");
        $rows.find("[data-line-fee]").addClass("d-none");
        $rows.find("[data-line-fee-text]").text("");
    }

    function paintLineFeeMarks(lineTotals) {
        $("#salesTable tbody tr.sales-line-row").each(function (index) {
            const $mark = $(this).find("[data-line-fee]").first();
            if (!$mark.length) {
                return;
            }
            const row = lineTotals[index] || { fee: 0, tax: 0 };
            const fee = round2(row.fee);
            const $text = $mark.find("[data-line-fee-text]").first();
            if (fee <= 0) {
                $mark.addClass("d-none");
                $text.text("");
                return;
            }
            $text.text(lineFeePrefix() + ": " + formatMoney(fee, row.tax));
            $mark.removeClass("d-none");
        });
    }

    function paintFeeLineBreakdown(feeId, computed) {
        const $box = $('.invoice-service-fee-lines[data-fee-id="' + feeId + '"]');
        if (!$box.length) {
            return;
        }
        if (String(computed.application_type) !== APPLY_ITEM) {
            $box.addClass("d-none").empty();
            return;
        }
        $box.empty();
        let shown = 0;
        (computed.line_amounts || []).forEach(function (line, index) {
            if (!line || round2(line.fee_amount) <= 0) {
                return;
            }
            shown += 1;
            const $row = $("<div/>", { class: "d-flex justify-content-between gap-3 fs-8 text-gray-700" });
            $row.append($("<span/>").text(lineLabel(index)));
            $row.append($("<span/>", { class: "fw-semibold" }).text(formatMoney(line.fee_amount, line.tax_amount)));
            $box.append($row);
        });
        $box.toggleClass("d-none", shown === 0);
    }

    function lineContext() {
        return collectLines();
    }

    function computeFee(fee, context) {
        const isPercent = String(fee.service_fee_type) === TYPE_PERCENT;
        const isItem = String(fee.application_type) === APPLY_ITEM;
        const afterTax = String(fee.calculation_method) === CALC_AFTER_TAX;
        const rateOrAmount = parseFloat(fee.amount) || 0;
        const taxable = !!fee.taxable;
        let feeAmount = 0;
        let taxAmount = 0;

        const lineAmounts = [];
        const lines = Array.isArray(context.lines) ? context.lines : [];

        if (isItem) {
            lines.forEach(function (line) {
                if (line.qty <= 0) {
                    lineAmounts.push({ fee_amount: 0, tax_amount: 0 });
                    return;
                }
                let lineFee = 0;
                if (isPercent) {
                    const base = afterTax ? line.gross : line.net;
                    lineFee = base * (rateOrAmount / 100);
                } else {
                    lineFee = rateOrAmount * line.qty;
                }
                lineFee = Math.max(0, round2(lineFee));
                const lineTax = taxable ? round2(lineFee * ((line.tax_rate || 0) / 100)) : 0;
                feeAmount += lineFee;
                taxAmount += lineTax;
                lineAmounts.push({
                    fee_amount: lineFee,
                    tax_amount: lineTax,
                });
            });
        } else if (isPercent) {
            const base = afterTax ? context.productTotal : context.subtotalAfterDiscount;
            feeAmount = Math.max(0, round2(base * (rateOrAmount / 100)));
            if (taxable && feeAmount > 0) {
                const net = context.subtotalAfterDiscount;
                const vat = context.productVat;
                const effectiveRate = net > 0.0001 ? vat / net : 0;
                taxAmount = round2(feeAmount * effectiveRate);
            }
        } else {
            feeAmount = Math.max(0, round2(rateOrAmount));
            if (taxable && feeAmount > 0) {
                const net = context.subtotalAfterDiscount;
                const vat = context.productVat;
                const effectiveRate = net > 0.0001 ? vat / net : 0;
                taxAmount = round2(feeAmount * effectiveRate);
            }
        }

        return {
            id: fee.id,
            fee_amount: round2(feeAmount),
            tax_amount: round2(taxAmount),
            application_type: String(fee.application_type || ""),
            line_amounts: lineAmounts,
        };
    }

    function feeFormula(fee) {
        const isPercent = String(fee.service_fee_type) === TYPE_PERCENT;
        const isItem = String(fee.application_type) === APPLY_ITEM;
        const afterTax = String(fee.calculation_method) === CALC_AFTER_TAX;
        const amount = parseFloat(fee.amount) || 0;
        const locale = config().locale === "ar";
        if (isPercent && isItem) {
            return locale
                ? `${amount}% على كل سطر منتج ${afterTax ? "بعد الضريبة" : "قبل الضريبة"}`
                : `${amount}% on each product line ${afterTax ? "after tax" : "before tax"}`;
        }
        if (isPercent) {
            return locale
                ? `${amount}% على إجمالي الطلب ${afterTax ? "بعد الضريبة" : "قبل الضريبة"}`
                : `${amount}% on order ${afterTax ? "after tax" : "before tax"}`;
        }
        if (isItem) {
            return locale ? `${amount} × الكمية على كل سطر منتج` : `${amount} × qty on each product line`;
        }
        return locale ? `مبلغ ثابت على الطلب` : `Fixed amount on order`;
    }

    function feeLabel(fee) {
        return config().locale === "ar"
            ? fee.name_ar || fee.name_en
            : fee.name_en || fee.name_ar;
    }

    function renderList() {
        const $list = $("#invoice-service-fees");
        if (!$list.length) {
            return;
        }

        const fees = feesForEstablishment();
        const previousChecked = {};
        $list.find(".invoice-service-fee-check").each(function () {
            previousChecked[String($(this).val())] = $(this).is(":checked");
        });

        $list.empty();

        if (!syncVisibility()) {
            clearAmounts();
            return;
        }

        fees.forEach(function (fee) {
            const id = String(fee.id);
            let checked = shouldAutoApply(fee);
            if (Object.prototype.hasOwnProperty.call(userTouched, id)) {
                checked = !!userTouched[id];
            } else if (Object.prototype.hasOwnProperty.call(previousChecked, id)) {
                checked = previousChecked[id];
            }

            const row = $(
                '<div class="border border-gray-300 rounded px-3 py-2">' +
                    '<label class="d-flex align-items-center justify-content-between gap-3 mb-0">' +
                        '<span class="d-flex align-items-center gap-2">' +
                            '<input type="checkbox" class="form-check-input invoice-service-fee-check" name="applied_service_fee_ids[]" value="' + id + '"' + (checked ? " checked" : "") + ">" +
                            "<span>" +
                                '<span class="fw-semibold"></span>' +
                                '<span class="text-muted fs-8 d-block"></span>' +
                            "</span>" +
                        "</span>" +
                        '<span class="fw-bold invoice-service-fee-amount" data-fee-id="' + id + '">0.00</span>' +
                    "</label>" +
                    '<div class="invoice-service-fee-lines d-none" data-fee-id="' + id + '"></div>' +
                "</div>"
            );
            row.find(".fw-semibold").first().text(feeLabel(fee) || "");
            row.find(".text-muted").first().text(feeFormula(fee) || "");
            $list.append(row);
        });
    }

    function applyToTotals(productContext) {
        try {
            if (!syncVisibility()) {
                clearAmounts();
                return { feeAmount: 0, feeTax: 0 };
            }

            const context = Object.assign({}, productContext || {});
            if (!Array.isArray(context.lines)) {
                context.lines = collectLines();
            }

            const fees = feesForEstablishment();
            const checkedIds = $("#invoice-service-fees .invoice-service-fee-check:checked")
                .map(function () {
                    return String($(this).val());
                })
                .get();

            let feeAmount = 0;
            let feeTax = 0;
            const lineTotals = context.lines.map(function () {
                return { fee: 0, tax: 0 };
            });
            let hasItemFee = false;

            fees.forEach(function (fee) {
                const feeId = String(fee.id);
                if (checkedIds.indexOf(feeId) === -1) {
                    $('.invoice-service-fee-amount[data-fee-id="' + feeId + '"]').text("0.00");
                    try {
                        paintFeeLineBreakdown(feeId, { application_type: fee.application_type, line_amounts: [] });
                    } catch (e) {
                        /* ignore paint errors */
                    }
                    return;
                }
                const computed = computeFee(fee, context);
                feeAmount += computed.fee_amount;
                feeTax += computed.tax_amount;
                $('.invoice-service-fee-amount[data-fee-id="' + feeId + '"]').text(
                    formatMoney(computed.fee_amount, computed.tax_amount)
                );
                try {
                    paintFeeLineBreakdown(feeId, computed);
                } catch (e) {
                    /* ignore paint errors */
                }
                if (String(computed.application_type) === APPLY_ITEM) {
                    hasItemFee = true;
                    (computed.line_amounts || []).forEach(function (line, index) {
                        if (!lineTotals[index]) {
                            lineTotals[index] = { fee: 0, tax: 0 };
                        }
                        lineTotals[index].fee += line.fee_amount || 0;
                        lineTotals[index].tax += line.tax_amount || 0;
                    });
                }
            });

            feeAmount = round2(feeAmount);
            feeTax = round2(feeTax);

            $("#input-service_fee_amount").val(feeAmount.toFixed(2));
            $("#input-service_fee_tax").val(feeTax.toFixed(2));
            $("#totalServiceFees").text((feeAmount + feeTax).toFixed(2));

            window.requestAnimationFrame(function () {
                try {
                    if (hasItemFee) {
                        paintLineFeeMarks(lineTotals);
                    } else {
                        hideLineFeeMarks();
                    }
                } catch (e) {
                    /* never touch Select2 / never abort product selection */
                }
            });

            return { feeAmount: feeAmount, feeTax: feeTax };
        } catch (e) {
            try {
                clearAmounts();
            } catch (e2) {
                /* ignore */
            }
            return { feeAmount: 0, feeTax: 0 };
        }
    }

    function bind() {
        $(document).on("change", ".invoice-service-fee-check", function () {
            userTouched[String($(this).val())] = $(this).is(":checked");
            if (typeof updateSalesTotals === "function") {
                updateSalesTotals();
            }
        });

        $("#storehouse, #cash_account, #account_id, #transaction_date, #toggleStorehouse, #toggleServiceFees").on(
            "change select2:select",
            function () {
                userTouched = {};
                renderList();
                if (typeof updateSalesTotals === "function") {
                    updateSalesTotals();
                }
            }
        );

        $(document).on(
            "change",
            "#toggleInternalConsumption, #internal_consumption_type_id",
            function () {
                if (typeof updateSalesTotals === "function") {
                    updateSalesTotals();
                }
            }
        );
    }

    return {
        renderList: renderList,
        applyToTotals: applyToTotals,
        collectLines: collectLines,
        bind: bind,
        resetTouched: function () {
            userTouched = {};
        },
    };
})();
