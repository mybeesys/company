$(document).ready(function () {
    //Purchase & Sell report
    //Date range as a button
    if ($('#purchase_sell_date_filter').length == 1) {
        $('#purchase_sell_date_filter').daterangepicker(dateRangeSettings, function (start, end) {
            $('#purchase_sell_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            updatePurchaseSell();
        });
        $('#purchase_sell_date_filter').on('cancel.daterangepicker', function (ev, picker) {
            $('#purchase_sell_date_filter').html(
                '<i class="fa fa-calendar"></i> ' + LANG.filter_by_date
            );
        });
        updatePurchaseSell();
    }

    if ($('#scr_date_filter').length == 1) {
        $('#scr_date_filter').daterangepicker(dateRangeSettings, function (start, end) {
            $('#scr_date_filter').val(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            supplier_report_tbl.ajax.reload();
        });
        $('#scr_date_filter').on('cancel.daterangepicker', function (ev, picker) {
            $('#scr_date_filter').val('');
            supplier_report_tbl.ajax.reload();
        });
    }
});
