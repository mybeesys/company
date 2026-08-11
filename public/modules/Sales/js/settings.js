$(document).ready(function () {
    $("#toggledescrption").on("change", function () {
        if ($(this).is(":checked")) {
            $(".product-description").show();
        } else {
            $(".product-description").hide();
        }
    });

    $("#toggleCost_center").on("change", function () {
        if ($(this).is(":checked")) {
            $("#dev-costCenter").removeClass("d-none");
        } else {
            $("#dev-costCenter").addClass("d-none");
        }
    });

    $("#toggleStorehouse").on("change", function () {
        if ($(this).is(":checked")) {
            $("#div-storehouse").removeClass("d-none");
        } else {
            $("#div-storehouse").addClass("d-none");
        }
    });

    $("#toggleDelegates").on("change", function () {
        if ($(this).is(":checked")) {
            $("#div-Delegates").removeClass("d-none");
        } else {
            $("#div-Delegates").addClass("d-none");
        }
    });

    $(document).on("submit", "#sell_save", function (e) {
        const $form = $(this);
        const precheck = window.invoicePrecheckConfig || {};
        const missingAccounts = Array.isArray(precheck.missingAccounts) ? precheck.missingAccounts : [];
        const skipContactAccountCheck = String($form.data("invoice-document-only") || "") === "1";

        if (missingAccounts.length > 0) {
            e.preventDefault();
            const header = (precheck.messages && precheck.messages.missingAccountsHeader)
                ? precheck.messages.missingAccountsHeader
                : "Missing accounting configuration:";
            const list = missingAccounts.map((item) => `- ${item}`).join("<br>");
            toastr.warning(`${header}<br>${list}`);
            return false;
        }

        if (!skipContactAccountCheck) {
            const $client = $form.find("#client_id");
            const $option = $client.find(":selected");
            const clientId = $option.val();
            if (clientId) {
                const hasAccount = String($option.data("has-account") ?? "");
                const accountId = $option.data("account-id");
                const linked =
                    hasAccount === "1" ||
                    hasAccount === "true" ||
                    (accountId !== undefined && accountId !== null && String(accountId).trim() !== "" && Number(accountId) > 0);
                // Only enforce when the option exposes account metadata.
                const metadataPresent =
                    hasAccount !== "" ||
                    (accountId !== undefined && accountId !== null);
                if (metadataPresent && !linked) {
                    e.preventDefault();
                    const msg =
                        (precheck.messages && precheck.messages.contactMissingAccount) ||
                        "This customer/supplier has no linked accounting account.";
                    toastr.warning(msg);
                    $client.addClass("is-invalid").focus();
                    return false;
                }
            }
        }

        let missingUnit = false;
        $form.find('#salesTable tbody tr').each(function () {
            const hasProduct = !!$(this).find('[name$="[products_id]"]').val();
            if (!hasProduct) {
                return;
            }
            const unitValue = $(this).find('[name*="[unit]"]').val();
            if (!unitValue) {
                missingUnit = true;
                $(this).find('[name*="[unit]"]').addClass('is-invalid');
            } else {
                $(this).find('[name*="[unit]"]').removeClass('is-invalid');
            }
        });

        if (missingUnit) {
            e.preventDefault();
            const msg = (precheck.messages && precheck.messages.missingUnit)
                ? precheck.messages.missingUnit
                : "Please select a unit for all products before saving.";
            toastr.warning(msg);
            return false;
        }

        return true;
    });
});
