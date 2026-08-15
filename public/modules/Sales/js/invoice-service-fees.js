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
    }

    function syncVisibility() {
        const show = shouldShowUi();
        $("#invoice-service-fees-wrap, #service-fee-summary-card").toggleClass("d-none", !show);
        $("#input-service_fees_ready").val(show ? "1" : "0");
        return show;
    }

    function feesForEstablishment() {
        const establishmentId = currentEstablishmentId();
        return (config().fees || []).filter(function (fee) {
            return String(fee.establishment_id) === establishmentId && (fee.is_active || fee.active);
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

    function lineContext() {
        const lines = [];
        $("#salesTable tbody tr").each(function (index) {
            const qty = parseFloat($(this).find(`[name="products[${index}][qty]"]`).val()) || 0;
            const net = parseFloat($(this).find(".total_before_vat-field").val()) || 0;
            const vat = parseFloat($(this).find(".vat_value-field").val()) || 0;
            const gross = parseFloat($(this).find(".total_after_vat-field").val()) || 0;
            let taxRate = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
            if (taxRate <= 0 && net > 0 && vat > 0) {
                taxRate = (vat / net) * 100;
            }
            lines.push({ qty: qty, net: net, vat: vat, gross: gross, tax_rate: taxRate });
        });
        return lines;
    }

    function computeFee(fee, context) {
        const isPercent = String(fee.service_fee_type) === TYPE_PERCENT;
        const isItem = String(fee.application_type) === APPLY_ITEM;
        const afterTax = String(fee.calculation_method) === CALC_AFTER_TAX;
        const rateOrAmount = parseFloat(fee.amount) || 0;
        const taxable = !!fee.taxable;
        let feeAmount = 0;
        let taxAmount = 0;

        if (isItem) {
            context.lines.forEach(function (line) {
                if (line.qty <= 0) {
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
                feeAmount += lineFee;
                if (taxable) {
                    taxAmount += round2(lineFee * (line.tax_rate / 100));
                }
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
                ? `${amount}% على كل منتج ${afterTax ? "بعد الضريبة" : "قبل الضريبة"}`
                : `${amount}% per item ${afterTax ? "after tax" : "before tax"}`;
        }
        if (isPercent) {
            return locale
                ? `${amount}% على إجمالي الطلب ${afterTax ? "بعد الضريبة" : "قبل الضريبة"}`
                : `${amount}% on order ${afterTax ? "after tax" : "before tax"}`;
        }
        if (isItem) {
            return locale ? `${amount} × الكمية لكل منتج` : `${amount} × qty per item`;
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

            const row = $(`
                <label class="d-flex align-items-center justify-content-between gap-3 border border-gray-300 rounded px-3 py-2 mb-0">
                    <span class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input invoice-service-fee-check" name="applied_service_fee_ids[]" value="${id}" ${checked ? "checked" : ""}>
                        <span>
                            <span class="fw-semibold">${feeLabel(fee)}</span>
                            <span class="text-muted fs-8 d-block">${feeFormula(fee)}</span>
                        </span>
                    </span>
                    <span class="fw-bold invoice-service-fee-amount" data-fee-id="${id}">0.00</span>
                </label>
            `);
            $list.append(row);
        });
    }

    function applyToTotals(productContext) {
        if (!syncVisibility()) {
            clearAmounts();
            return { feeAmount: 0, feeTax: 0 };
        }

        const fees = feesForEstablishment();
        const checkedIds = $("#invoice-service-fees .invoice-service-fee-check:checked")
            .map(function () {
                return String($(this).val());
            })
            .get();

        let feeAmount = 0;
        let feeTax = 0;

        fees.forEach(function (fee) {
            if (checkedIds.indexOf(String(fee.id)) === -1) {
                $(`.invoice-service-fee-amount[data-fee-id="${fee.id}"]`).text("0.00");
                return;
            }
            const computed = computeFee(fee, productContext);
            feeAmount += computed.fee_amount;
            feeTax += computed.tax_amount;
            const label = computed.tax_amount > 0
                ? `${computed.fee_amount.toFixed(2)} + VAT ${computed.tax_amount.toFixed(2)}`
                : computed.fee_amount.toFixed(2);
            $(`.invoice-service-fee-amount[data-fee-id="${fee.id}"]`).text(label);
        });

        feeAmount = round2(feeAmount);
        feeTax = round2(feeTax);

        $("#input-service_fee_amount").val(feeAmount.toFixed(2));
        $("#input-service_fee_tax").val(feeTax.toFixed(2));
        $("#totalServiceFees").text((feeAmount + feeTax).toFixed(2));

        return { feeAmount: feeAmount, feeTax: feeTax };
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
        bind: bind,
        resetTouched: function () {
            userTouched = {};
        },
    };
})();
