$(document).ready(function () {
    $("#client_id").on("change", function () {
        var selectedOption = $(this).find(":selected");
        console.log(selectedOption);

        var clientName = selectedOption.data("name") || null;
        var mobileNumber = selectedOption.data("mobile_number") || null;
        var email = selectedOption.data("email") || "-";
        var taxNumber = selectedOption.data("tax_number") || "-";
        var billing_address = selectedOption.data("tax_number") || "-";

        $("#client_name").text(clientName);
        if (mobileNumber) {
            console.log(mobileNumber);

            $("#mobile_number").text(mobileNumber);
            $("#dev-mobile_number").show();
        } else {
            $("#dev-mobile_number").hide();
        }
        $("#email").text(email);
        $("#tax_number").text(taxNumber);
        $("#billing_address").text(billing_address);
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
