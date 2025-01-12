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
            $("#dev-costCenter").show();
        } else {
            $("#dev-costCenter").hide();
        }
    });

    $("#toggleStorehouse").on("change", function () {
        if ($(this).is(":checked")) {
            $("#div-storehouse").show();
        } else {
            $("#div-storehouse").hide();
        }
    });

    $("#toggleDelegates").on("change", function () {
        if ($(this).is(":checked")) {
            $("#div-Delegates").show();
        } else {
            $("#div-Delegates").hide();
        }
    });
});
