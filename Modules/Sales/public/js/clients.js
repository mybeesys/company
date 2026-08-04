$(document).ready(function () {
    function contactMissingAccountMessage() {
        const precheck = window.invoicePrecheckConfig || {};
        if (precheck.messages && precheck.messages.contactMissingAccount) {
            return precheck.messages.contactMissingAccount;
        }
        return "This customer/supplier has no linked accounting account.";
    }

    function selectedContactMissingAccount($select) {
        const $option = $select.find(":selected");
        if (!$option.length || !$option.val()) {
            return false;
        }
        const hasAccount = String($option.data("has-account") ?? "");
        const accountId = $option.data("account-id");
        if (hasAccount === "1" || hasAccount === "true") {
            return false;
        }
        if (accountId !== undefined && accountId !== null && String(accountId).trim() !== "" && Number(accountId) > 0) {
            return false;
        }
        if (hasAccount === "" && (accountId === undefined || accountId === null)) {
            return false;
        }
        return true;
    }

    $("#client_id").on("change", function () {
        var selectedOption = $(this).find(":selected");

        var clientName = selectedOption.data("name") || null;
        var mobileNumber = selectedOption.data("mobile_number") || null;
        var email = selectedOption.data("email") || "-";
        var taxNumber = selectedOption.data("tax_number") || "-";
        var billing_address =
            selectedOption.data("billing_address") || selectedOption.data("billing_street_name") || "-";
        var payment_terms = parseInt(selectedOption.data("payment_terms")) || 0;

        if ($("#due_date").length && payment_terms) {
            var today = new Date();
            today.setDate(today.getDate() + payment_terms);
            $("#due_date").val(today.toISOString().split("T")[0]);
        }

        // Receipts create page no longer shows client detail rows; other screens keep them.
        if ($("#client_name").length) {
            $("#client_name").text(clientName || "--");
        }
        if ($("#mobile_number").length) {
            $("#mobile_number").text(mobileNumber || "--");
        }
        if ($("#dev-mobile_number").length) {
            if (mobileNumber) {
                $("#dev-mobile_number").show();
            } else {
                $("#dev-mobile_number").hide();
            }
        }
        if ($("#email").length) {
            $("#email").text(email);
        }
        if ($("#dev-email").length) {
            if (email && email !== "-") {
                $("#dev-email").show();
            } else {
                $("#dev-email").hide();
            }
        }
        if ($("#tax_number").length) {
            $("#tax_number").text(taxNumber);
        }
        if ($("#dev-tax_number").length) {
            if (taxNumber && taxNumber !== "-") {
                $("#dev-tax_number").show();
            } else {
                $("#dev-tax_number").hide();
            }
        }
        if ($("#billing_address").length) {
            $("#billing_address").text(billing_address);
        }
        if ($("#dev-billing_address").length) {
            if (billing_address && billing_address !== "-") {
                $("#dev-billing_address").show();
            } else {
                $("#dev-billing_address").hide();
            }
        }

        if (selectedContactMissingAccount($(this))) {
            toastr.warning(contactMissingAccountMessage());
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }
    });

    $("#addClientForm").on("submit", function (e) {
        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({
            url: "/client-save",
            method: "POST",
            data: formData,
            success: function (response) {
                $("#addClientModal").modal("hide");

                $("#addClientForm")[0].reset();

                const hasAccount = response.account_id ? "1" : "0";
                $("#client_id")
                    .append(
                        `<option value="${response.id}" data-name="${response.name}"
                    data-mobile_number="${response.mobile_number || ""}" data-email="${response.email || ""}"
                    data-tax_number="${response.tax_number || ""}" data-account-id="${response.account_id || ""}"
                    data-has-account="${hasAccount}" selected>${response.name}</option>`
                    )
                    .trigger("change");
            },
            error: function (xhr) {
                console.error(xhr.responseText);
            },
        });
    });
});
