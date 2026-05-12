$(document).ready(function () {
    $("#client_id").on("change", function () {
        var selectedOption = $(this).find(":selected");

        var clientName = selectedOption.data("name") || null;
        var mobileNumber = selectedOption.data("mobile_number") || null;
        var email = selectedOption.data("email") || "-";
        var taxNumber = selectedOption.data("tax_number") || "-";
        var billing_address =
            selectedOption.data("billing_address") || selectedOption.data("billing_street_name") || "-";

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
        if ($("#tax_number").length) {
            $("#tax_number").text(taxNumber);
        }
        if ($("#billing_address").length) {
            $("#billing_address").text(billing_address);
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

                $("#client_id")
                    .append(
                        `<option value="${response.id}" data-name="${response.name}"
                    data-mobile_number="${response.mobile_number}" data-email="${response.email}"
                    data-tax_number="${response.tax_number}" selected>${response.name}</option>`
                    )
                    .trigger("change");

                alert("@lang('sales::fields.client_added_success')");
            },
            error: function (xhr) {
                alert("@lang('sales::fields.client_add_error')");
                console.error(xhr.responseText);
            },
        });
    });
});
