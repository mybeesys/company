
function initAdjustmentTypeDatatable() {
    adjustmentTypeDataTable = $(adjustmentTypeTable).DataTable({
        processing: true,
        serverSide: true,
        ajax: adjustmentTypeDataUrl,
        info: false,
        columns: [{
            data: 'id',
            name: 'id',
            className: 'text-start'
        },
        {
            data: 'name',
            name: 'name'
        },
        {
            data: 'name_en',
            name: 'name_en'
        },
        {
            data: 'type',
            name: 'type'
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

function addAdjustmentTypeForm(addAdjustmentTypeUrl) {
    $('#add_adjustment_type_modal_form').on('submit', function (e) {
        e.preventDefault();
        let data = $(this).serializeArray();
        const submitButton = $(this).find('button[type="submit"]');
        submitButton.attr('disabled', 'disabled');

        data.push({
            name: "_token",
            value: window.csrfToken
        });
        ajaxRequest(addAdjustmentTypeUrl, "POST", data).fail(
            function (data) {
                $.each(data.responseJSON.errors, function (key, value) {
                    $(`[name='${key}']`).addClass('is-invalid');
                    $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                        '</div>');
                });
                submitButton.removeAttr('disabled');

            }).done(function () {
                submitButton.removeAttr('disabled');
                $('#add_adjustment_type_modal').modal('toggle');
                adjustmentTypeDataTable.ajax.reload();
            });
    });
}

$(document).on('click', '.nav-link-adjustment-type', function() {
    adjustmentTypeDataTable.ajax.reload();
});

$(document).on('click', '.adjustment-type-edit-btn', function() {
    var id = $(this).data('id');
    var adjustmentTypeType = $(this).data('adjustmentTypeType');
    var name = $(this).data('name');
    var name_en = $(this).data('nameEn');

    $('#add_adjustment_type_modal').modal('toggle');
    $('#adjustment_type_id').val(id);
    $('.modal-header h2').html(Lang.get('general.edit_adjustment'));
    $('#amount').val(amount);
    $('select[name="adjustment_type_type"]').val(adjustmentTypeType).trigger('change');
    $('#name').val(name);
    $('#name_en').val(name_en);
});