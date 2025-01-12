$(document).ready(function () {

    $("#client_id").on("change", function () {
        var selectedOption = $(this).find(":selected");

        var clientName = selectedOption.data("name") || "--";
        var mobileNumber = selectedOption.data("mobile_number") || "-";
        var email = selectedOption.data("email") || "-";
        var taxNumber = selectedOption.data("tax_number") || "-";
        var billing_address = selectedOption.data("billing_address") || "-";
        var billing_street_name =
            selectedOption.data("billing_street_name") || "-";
        var billing_city = selectedOption.data("billing_city") || "-";

        console.log(billing_address);

        $("#client_name").text(clientName);
        if (billing_street_name != "-" || billing_city != "-") {
            $("#billing_address").text(billing_address);
            $("#dev-billing_address").show();
        } else {
            $("#dev-billing_address").hide();
        }

        if (mobileNumber != "-") {
            $("#mobile_number").text(mobileNumber);
            $("#dev-mobile_number").show();
        } else {
            $("#dev-mobile_number").hide();
        }
        if (email != "-") {
            $("#email").text(email);
            $("#dev-email").show();
        } else {
            $("#dev-email").hide();
        }
        if (taxNumber != "-") {
            $("#tax_number").text(taxNumber);
            $("#dev-tax_number").show();
        } else {
            $("#dev-tax_number").hide();
        }
        // $("#tax_number").text(taxNumber);
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

                // alert("@lang('sales::fields.client_added_success')");
            },
            error: function (xhr) {
                // alert("@lang('sales::fields.client_add_error')");
                console.error(xhr.responseText);
            },
        });
    });
});
