$("#billing_country").select2();
$("#shipping_status").select2();
$("#cost_center").select2();
$("#Delegates").select2({
    width: "resolve",
});
$("#invoice_type").select2({
    width: "resolve",
});
$("#account_id").select2();

$("#payment_type").select2({
    width: "resolve",
});
$("#cash_account").select2({
    width: "resolve",
});

$("#client_id").select2({
    width: "resolve",
});
$("#storehouse").select2({
    width: "resolve",
});

$("#internal_consumption_type_id").select2({
    width: "resolve",
    allowClear: true,
});

// Line-item #unit / #tax_vat removed: duplicate ids per row break Select2; use initPrefilledSalesLineSelect2 (line-items-select2.js).

// $('#products').select2();
$("#payment_terms").select2({
    width: "resolve",
});

$("#cost_center").select2({
    width: "resolve",
});

$('#customer').select2();
$('#payment_status').select2();
$('#favorite-filter').select2();

$('favorite-filter').select2();
$("#transactions").select2({
    // width: "resolve",
    placeholder: "Select transactions",
    allowClear: true
});


