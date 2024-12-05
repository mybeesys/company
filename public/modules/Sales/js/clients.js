$(document).ready(function () {

    $("#client_id").on("change", function () {
        var selectedOption = $(this).find(":selected");

        var clientName = selectedOption.data("name") || "--";
        var mobileNumber = selectedOption.data("mobile_number") || "--";
        var email = selectedOption.data("email") || "--";
        var taxNumber = selectedOption.data("tax_number") || "--";

        $("#client_name").text(clientName);
        $("#mobile_number").text(mobileNumber);
        $("#email").text(email);
        $("#tax_number").text(taxNumber);
    });
});
