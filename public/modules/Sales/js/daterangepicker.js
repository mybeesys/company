$(document).ready(function () {
    

    $("#favorite-filter,#customer,#payment_status").change(function () {
        dataTable.ajax.reload();
    });

    $("#due_date_range").on("apply.daterangepicker", function (ev, picker) {
        dueDateRangeValue =
            picker.startDate.format("YYYY-MM-DD") +
            (currentLang === "ar" ? " إلى " : " to ") +
            picker.endDate.format("YYYY-MM-DD");

        $(this).val(dueDateRangeValue);

        dataTable.ajax.reload();
    });
    $("#sale_date_range").on("apply.daterangepicker", function (ev, picker) {
        sale_date_range =
            picker.startDate.format("YYYY-MM-DD") +
            (currentLang === "ar" ? " إلى " : " to ") +
            picker.endDate.format("YYYY-MM-DD");

        $(this).val(sale_date_range);

        dataTable.ajax.reload();
    });
    $("#clearFilter").on("click", function () {
        $("#due_date_range").val("");
        $("#favorite-filter").val("").trigger("change");
        $("#customer").val("").trigger("change");
        $("#payment_status").val("").trigger("change");
        $("#sale_date_range").val("");
        dueDateRangeValue = "";
        sale_date_range = "";

        dataTable.ajax.reload();
    });

    let ranges = currentLang === "ar" ? arabicRanges : customRanges;

    $("#due_date_range").daterangepicker({
        locale: localeSettings[currentLang],
        opens: currentLang === "ar" ? "right" : "left",
        autoUpdateInput: false,
        ranges: ranges,
    });

    $("#due_date_range").on("apply.daterangepicker", function (ev, picker) {
        $(this).val(
            picker.startDate.format("YYYY-MM-DD") +
                (currentLang === "ar" ? " إلى " : " to ") +
                picker.endDate.format("YYYY-MM-DD")
        );
    });

    $("#sale_date_range").daterangepicker({
        locale: localeSettings[currentLang],
        opens: currentLang === "ar" ? "right" : "left",
        autoUpdateInput: false,
        ranges: ranges,
    });

    $("#sale_date_range").on("apply.daterangepicker", function (ev, picker) {
        $(this).val(
            picker.startDate.format("YYYY-MM-DD") +
                (currentLang === "ar" ? " إلى " : " to ") +
                picker.endDate.format("YYYY-MM-DD")
        );
    });
});
