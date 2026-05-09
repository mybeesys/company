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

        if (missingAccounts.length > 0) {
            e.preventDefault();
            const header = (precheck.messages && precheck.messages.missingAccountsHeader)
                ? precheck.messages.missingAccountsHeader
                : "Missing accounting configuration:";
            const list = missingAccounts.map((item) => `- ${item}`).join("<br>");
            toastr.warning(`${header}<br>${list}`);
            return false;
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
