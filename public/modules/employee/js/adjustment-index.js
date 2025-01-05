function initAdjustmentDatatable() {
    adjustmentDataTable = $(adjustmentTable).DataTable({
        processing: true,
        serverSide: true,
        ajax: adjustmentDataUrl,
        info: false,
        columns: [{
            data: 'id',
            name: 'id',
            className: 'text-start'
        },
        {
            data: 'adjustment_type_name',
            name: 'adjustment_type_name'
        },
        {
            data: 'employee',
            name: 'employee'
        },
        {
            data: 'type',
            name: 'type'
        },
        {
            data: 'amount',
            name: 'amount'
        },
        {
            data: 'amount_type',
            name: 'amount_type'
        },
        {
            data: 'applicable_date',
            name: 'applicable_date'
        },
        {
            data: 'apply_once',
            name: 'apply_once'
        },
        {
            data: 'actions',
            name: 'actions',
            orderable: false,
            searchable: false
        }
        ],
        order: [],
        scrollX: true,
        pageLength: 10,
        drawCallback: function () {
            KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
        }
    });
};

function addAdjustmentForm(storeAdjustmentUrl) {
    $('#add_adjustment_modal_form').on('submit', function (e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.attr('disabled', 'disabled');

        let data = $(this).serializeArray();
        data.push({
            name: "_token",
            value: window.csrfToken
        });
        ajaxRequest(storeAdjustmentUrl, "POST", data).fail(
            function (data) {
                $.each(data.responseJSON.errors, function (key, value) {
                    $(`[name='${key}']`).addClass('is-invalid');
                    $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                        '</div>');
                });
                submitButton.removeAttr('disabled');
            }).done(function () {
                submitButton.removeAttr('disabled');
                $('#add_adjustment_modal').modal('toggle');
                adjustmentDataTable.ajax.reload();
            });
    });
}

$(document).on('click', '.nav-link-adjustment', function() {
    adjustmentDataTable.ajax.reload();
});

$(document).on('click', '.edit-btn', function() {
    var id = $(this).data('id');
    var adjustmentType = $(this).data('adjustmentType');
    var employeeId = $(this).data('employeeId');
    var amount = $(this).data('amount');
    var amountType = $(this).data('amountType');
    var applyOnce = $(this).data('applyOnce');
    var applicableDate = $(this).data('applicableDate').substring(0, 7);
    var type = $(this).data('type');

    $('#add_adjustment_modal').modal('toggle');
    $('#id').val(id);
    $('.modal-header h2').html(Lang.get('general.edit_adjustment'));
    $('#amount').val(amount);
    $('select[name="amount_type"]').val(amountType).trigger('change');
    $('select[name="adjustment_type"]').val(adjustmentType).trigger('change');
    $('select[name="employee_id"]').val(employeeId).trigger('change');
    $('select[name="type"]').val(type).trigger('change');
    $('#applicable_date').val(applicableDate);
    $('#apply_once').val(applyOnce);

    if (applyOnce) {
        $('#apply_once').attr('checked', applyOnce);
    }
});