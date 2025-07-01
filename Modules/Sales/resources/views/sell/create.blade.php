@extends('layouts.app')

@section('title', __('sales::lang.Create a sales invoice'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .custom-width {
            min-width: 60%;
            width: 60%;
        }

        .custom-height {
            height: 35px;
            width: 60%;
        }

        .custom-input {
            height: 35px;
        }

        .custom-header {
            background-color: #f1f1f4 !important;
            min-height: 50px !important;
        }

        .me-3 {
            margin-right: 0 !important;
        }

        .table.gy-4 td {
            padding-left: 2px;
        }

        #discount_type+.select2-container {
            width: max-content !important;
        }

        #unit+.select2-container {
            width: max-content !important;
        }
    </style>


@stop
@section('content')
    <form id="sell_save" method="POST" action="{{ route('store-invoice') }}">
        @csrf

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1> @lang('sales::lang.Create a sales invoice') @if ($transaction)
                                @lang('sales::lang.from quotation') {{ $transaction->ref_no }}
                            @endif
                        </h1>

                    </div>
                </div>
                @include('general::invoice-setting.setting')
            </div>
        </div>
        <div class="separator d-flex flex-center my-3">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>

        <div class="">
            <div class="row">
                <div class="col-sm">

                    {{-- invoice information --}}
                    @include('sales::sell.create.invoice-info')

                </div>
                <div class="col-6">

                    @include('sales::sell.create.client-info')

                </div>

                <div class="separator d-flex flex-center my-6">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>

                @include('sales::sell.create.line-items')

            </div>

            @include('sales::sell.create.Tab-nav')


            @include('general::invoice-setting.general-invoice-setting.terms-conditions-notes')
            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div class="row">
                <div class="col-sm-4
                ">

                    <div class="btn-group">
                        @if ($Latest_event->action == 'save')
                            <a class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px dropdown-item"
                                type="submit" href="#" data-action="save" data-status="final">@lang('messages.save')
                            </a>
                        @elseif ($Latest_event->action == 'save_add')
                            <a class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px dropdown-item"
                                type="submit" href="#" data-action="save_add" data-status="final">@lang('messages.save&add')
                            </a>
                        @else
                            <a class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px dropdown-item"
                                type="submit" href="#" data-action="save_print"
                                data-status="final">@lang('messages.save&print')
                            </a>
                        @endif


                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split "
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>

                        <ul class="dropdown-menu p-5">
                            <li>
                                <a class="dropdown-item" type="submit" href="#" data-action="save"
                                    data-status="final">@lang('messages.save')
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" type="submit" href="#" data-action="save_add"
                                    data-status="final">@lang('messages.save&add')
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#" data-action="save_print"
                                    data-status="final">@lang('messages.save&print')</a>

                            </li>
                        </ul>
                    </div>





                    <input type="hidden" name="status" value="draft" />
                    <button type="submit" style="border-radius: 6px;" class="btn btn-bg-dark text-white ">
                        @lang('messages.savedraft')
                    </button>
                </div>
                <div class="col-sm " style="justify-items: end">
                    <div class="card-body p-0 d-flex flex-column">

                        <div class="card-p p-0 bg-body flex-grow-1" style="padding: 0px !important">

                            <div class="d-flex flex-column flex-grow-1 ">

                                <div class="d-flex flex-wrap">



                                    <div class="border border-gray-300 border-dashed rounded min-w-125px  px-4 me-6 "
                                        style="    height: max-content;padding: 6px;">
                                        <div class="d-flex align-items-center">

                                            <span class="fw-semibold mx-2 text-muted fs-7 px-2">@lang('sales::lang.remaining_balance')</span>
                                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                                                data-kt-countup-value="4500" data-kt-countup-prefix="$"
                                                data-kt-initialized="1" id="remaining_balance" style="color: red;">
                                                0.00</div><span
                                                class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                                        </div>
                                    </div>

                                    {{-- <div class="border border-gray-300 border-dashed rounded min-w-125px  px-4 me-6 "
                                        style="    height: max-content;padding: 6px;">
                                        <div class="d-flex align-items-center">

                                            <span class="fw-semibold mx-2 text-muted fs-7 px-2">@lang('sales::lang.balance')</span>
                                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                                                data-kt-countup-value="4500" data-kt-countup-prefix="$"
                                                data-kt-initialized="1" id="balance" style="color: #2cd32c;">
                                                0.00</div><span
                                                class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                                        </div>
                                    </div> --}}





                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>


    </form>

    @include('sales::sell.create.add-client')




@stop

@section('script')
    <script src="{{ url('/modules/Sales/js/clients.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/settings.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/invoice-calculations.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/product-management.js') }}"></script>


    <script>
        let salesRowIndex = 1;

      $("#addSalesRow").on("click", function() {
    salesRowIndex++;

    const newSalesRow = `
        <tr>
              <td>
                <select id="products-${salesRowIndex}" required
                        class="form-select form-select-solid select-2 product-select"
                        name="products[${salesRowIndex}][products_id]">
                    <option value="">@lang('sales::lang.select_products')</option>
                </select>
            </td>
            <td class="product-description" style="display:none">
                <textarea class="form-control form-control-solid" rows="1" name="products[${salesRowIndex}][description]"></textarea>
            </td>
         <td>
    <div class="d-flex align-items-center gap-2">
        <input type="number"
               step="any"
               class="form-control qty-field flex-shrink-0"
               name="products[${salesRowIndex}][qty]"
               placeholder="0"
               min="1"
               style="width: 80px;">

        <select class="form-select form-select-solid select-2 unit flex-grow-1"
                name="products[${salesRowIndex}][unit]"
                style="min-width: 100px;">
            <option value="">@lang('sales::lang.unit')</option>
        </select>
    </div>
</td>  <td><input type="number" step="any" class="form-control unit_price-field no-spin" name="products[${salesRowIndex}][unit_price]" placeholder="0.0" style="width: 100px;"></td>
            <td style="white-space: nowrap;">
                <input type="number" step="any" class="form-control discount-field no-spin d-inline-block discount" name="products[${salesRowIndex}][discount]" placeholder="0.0" style="width: 70px; display: inline-block;">
                <select id="discount_type" required class="form-select form-select-solid select-2 d-inline-block discount_type" name="products[${salesRowIndex}][discount_type]" style="width: 100px; display: inline-block;">
                    <option value="fixed">@get_format_currency()</option>
                    <option value="percent">%</option>
                </select>
            </td>
            <td><input type="number" step="any" readonly class="form-control total_before_vat-field" name="products[${salesRowIndex}][total_before_vat]" placeholder="0.00" style="width: 107px;"></td>
            <td class="d-flex justify-content-center">
                <div class="form-check">
                    <input type="checkbox" style="border: 1px solid #9f9f9f;" id="inclusive" name="products[${salesRowIndex}][inclusive]" class="form-check-input my-2">
                </div>
            </td>
               <td>
            <select id="tax_vat" required class="form-select form-select-solid select-2"
                name="products[${salesRowIndex}][tax_vat]" style="width: 200px;">
                @foreach ($taxes as $tax)
                <option value="{{ $tax->amount }}"
                    data-is-tax-group="{{ $tax->is_tax_group }}"
                    data-sub-taxes="{{ json_encode($tax->sub_taxes ?? []) }}"
                    data-minimum-limits="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}"
                    @if ($tax->default == 1) selected @endif>
                    @if (app()->getLocale() == 'en')
                        {{ $tax->name_en }}
                    @else
                        {{ $tax->name }}
                    @endif
                </option>
                @endforeach
            </select>
            <input type="hidden" class="is-tax-group" name="products[${salesRowIndex}][is_tax_group]">
            <input type="hidden" class="sub-taxes" name="products[${salesRowIndex}][sub_taxes]">
            <input type="hidden" class="minimum-limits" name="products[${salesRowIndex}][minimum_limits]">
        </td>                       <td><input type="number" step="any" readonly class="form-control vat_value-field" name="products[${salesRowIndex}][vat_value]" placeholder="0.00" style="width: 80px;"></td>
            <td><input type="number" step="any" readonly class="form-control total_after_vat-field" name="products[${salesRowIndex}][total_after_vat]" placeholder="0.00" style="width: 107px;"></td>
            <td>
                <button type="button" class="btn btn-icon btn-danger delete-sales-row">
                    <i class="ki-outline ki-trash fs-2"></i>
                </button>
            </td>
        </tr>
    `;

    $("#salesTable tbody").append(newSalesRow);

    $(`#products-${salesRowIndex}`).select2({
        ajax: {
            url: '/products-for-sale',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term,
                    page: params.page || 1
                };
            },
            processResults: function(response, params) {
                 const lang = localStorage.getItem('lang') || 'ar';
                return {
                    results: response.data.map(product => ({
                        id: product.id,
                         text: lang === 'ar'
                ? `${product.SKU} - ${product.name_ar}`
                : `${product.SKU} - ${product.name_en}`,
                 price: product.price,
                        units: product.unit_transfers,
                    })),
                    pagination: {
                        more: response.meta?.next_page_url ? true : false
                    }
                };
            },
            cache: true
        }
    }).on('select2:select', function(e) {
    const selectedData = e.params.data;
    const $row = $(this).closest('tr');

    $row.find('.unit_price-field').val(selectedData.price);

    const $unitSelect = $row.find('.unit');
    $unitSelect.empty().append('<option value="">@lang('sales::lang.unit')</option>');

    if (selectedData.units && Array.isArray(selectedData.units)) {
        console.log('Units Data:', selectedData.units);

        selectedData.units.forEach((unit, index)  => {
            const unitId = unit.id || unit.unit_id || unit.unit1;
            const unitName = unit.name || unit.unit_name || unit.unit1 || 'وحدة غير معروفة';
            const unitValue = unit.transfer || unit.unit1_value || 1;

            if (!unitId || !unitName) {
                console.warn('Invalid unit data:', unit);
                return;
            }

            const $option = $('<option></option>')
                .val(unitId)
                .text(unitName)
                .attr('data-value', unitValue);

                 if (index === 0) {
        $option.attr('selected', 'selected');
    }
            $unitSelect.append($option);
            updateSalesTotals();
        });

        $unitSelect.select2('destroy').select2({
            width: '100%',
            dropdownParent: $row.closest('.modal').length ? $row.closest('.modal') : document.body
        });
    updateSalesTotals();

    } else {
        console.error('No units data found for product:', selectedData.id);
        $unitSelect.append('<option value="piece" data-value="1">Piece</option>');
    }
});

    $("#salesTable tbody tr:last").find(".select-2:not(.product-select)").select2();

    updateSalesTotals();
    console.log("salesRowIndex " + salesRowIndex);
});

        $(document).on("click", ".delete-sales-row", function() {
            $(this).closest("tr").remove();
            updateSalesTotals();
        });

        $('#addNewAccountBtn').on('click', function() {
            $('#addClientModal').modal('show');
        });


        $(document).on('change', '[name*="[tax_vat]"]', function() {
    var selectedOption = $(this).find('option:selected');
    var row = $(this).closest('tr');
    var index = row.index();
  const subTaxes = selectedOption.data('sub-taxes') || [];
    const minimumLimits = selectedOption.data('minimum-limits') || [];

    if (row.find('.is-tax-group').length === 0) {
        row.append('<input type="hidden" class="is-tax-group" name="products['+index+'][is_tax_group]">');
    }
    if (row.find('.sub-taxes').length === 0) {
        row.append('<input type="hidden" class="sub-taxes" name="products['+index+'][sub_taxes]">');
    }
    if (row.find('.minimum-limits').length === 0) {
        row.append('<input type="hidden" class="minimum-limits" name="products['+index+'][minimum_limits]">');
    }

    row.find('.is-tax-group').val(selectedOption.data('is-tax-group') || 0);
    row.find('.sub-taxes').val(typeof subTaxes === 'string' ? subTaxes : JSON.stringify(subTaxes));
    row.find('.minimum-limits').val(typeof minimumLimits === 'string' ? minimumLimits : JSON.stringify(minimumLimits));
    updateSalesTotals();
});
        $(document).on('click', '.dropdown-item', function(e) {
            e.preventDefault();
            let action = $(this).data('action');
            let isValid = true;
            let requiredFields = $('#sell_save').find('[required]');
            console.log(requiredFields);

            requiredFields.each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            if (!isValid) {
                const message = "@lang('messages.required_fields_warning')";
                toastr.warning(message);
                return;
            }
            if (action === 'save_add') {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'action',
                    value: 'save_add'
                }).appendTo('#sell_save');
            } else if (action === 'save_print') {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'action',
                    value: 'save_print'
                }).appendTo('#sell_save');
            } else if (action === 'save') {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'action',
                    value: 'save'
                }).appendTo('#sell_save');
            }
            $('<input>').attr({
                type: 'hidden',
                name: 'status',
                value: 'approved'
            }).appendTo('#sell_save');
            $('#sell_save').submit();
        });


        $(document).ready(function() {


             $('.select-2-products-id').select2({
        placeholder: "Select a product",
        allowClear: true,
        language: {
            noResults: function() {
                return `<a href="#" class="add-new-product" data-bs-toggle="modal" data-bs-target="#addProductModal">@lang('sales::lang.add_new_product')</a>`;
            }
        },
        escapeMarkup: function(markup) {
            return markup;
        }
    });

    $(document).on('click', '.add-new-product', function(e) {
        e.preventDefault();
        $('#addProductModal').modal('show');
    });


//     function fetchProducts() {
//     $.ajax({
//         url: '/products-for-sale',
//         method: 'GET',
//         dataType: 'json',
//         success: function(response) {
//             updateSelect2WithProducts(response.data);
//         },
//         error: function(xhr, status, error) {
//             console.error('Error fetching products:', error);
//         }
//     });
// }

// function updateSelect2WithProducts(products) {
//     $('.select-2-products-id').empty().append('<option value="">@lang('sales::lang.select_products')</option>');

//     products.forEach(function(product) {
//         var optionText = (appLocale === 'ar') ?
//             `${product.name_ar} - <span class="fw-semibold mx-2 text-muted fs-5">${product.SKU}</span>` :
//             `${product.name_en} - <span class="fw-semibold mx-2 text-muted fs-7">${product.SKU}</span>`;

//         var option = new Option(optionText, product.id, false, false);
//         option.dataset.price = product.price;
//         option.dataset.units = JSON.stringify(product.unit_transfers);

//         $('.select-2-products-id').append(option);
//     });

//     $('.select-2-products-id').trigger('change');
// }


$.ajax({
            url: "{{ route('categoryList') }}",
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#category_id').empty();
                $('#category_id').append('<option value="">@lang('sales::lang.select_category')</option>');

                const validCategories = response.filter(item => item.data && item.data.id && !item
                    .data.empty);

                $.each(validCategories, function(index, category) {
                    $('#category_id').append(
                        `<option value="${category.data.id}">
                        ${category.data.name_ar} - ${category.data.name_en}
                    </option>`
                    );
                });
            },
            error: function(xhr) {
                console.error('Error loading categories:', xhr.responseText);
            }
        });

        $('#category_id').change(function() {
            var categoryId = $(this).val();
            if (categoryId) {
                $('#subcategory_id').prop('disabled', false);

                $.ajax({
                    url: "{{ route('categoryList') }}",
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const selectedCategory = response.find(cat =>
                            cat.data && cat.data.id == categoryId && !cat.data.empty
                        );

                        $('#subcategory_id').empty();
                        $('#subcategory_id').append(
                            '<option value="">@lang('sales::lang.select_subcategory')</option>');

                        if (selectedCategory && selectedCategory.children) {
                            const validSubcategories = selectedCategory.children.filter(
                                child => child.data && child.data.id && !child.data
                                .empty
                            );

                            $.each(validSubcategories, function(index, subcategory) {
                                $('#subcategory_id').append(
                                    `<option value="${subcategory.data.id}">
                                    ${subcategory.data.name_ar} - ${subcategory.data.name_en}
                                </option>`
                                );
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading subcategories:', xhr.responseText);
                    }
                });
            } else {
                $('#subcategory_id').prop('disabled', true);
                $('#subcategory_id').empty();
                $('#subcategory_id').append('<option value="">@lang('sales::lang.select_subcategory')</option>');
            }
        });

        $('#saveProductBtn').click(function(e) {
            e.preventDefault();
            $('#saveProductBtn').prop('disabled', true);

            let formData = {
                name_ar: $('#name_ar').val(),
                name_en: $('#name_en').val(),
                category_id: $('#category_id').val(),
                subcategory_id: $('#subcategory_id').val(),
                price: $('#price').val(),
                cost: $('#cost').val(),
                order: $('#order').val(),
                unit1: $('#unit1').val()
            };

            $.ajax({
                url: "{{ route('productFastSave') }}",
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success('تم حفظ المنتج بنجاح');
                    $('#addProductModal').modal('hide');
                    // fetchProducts();
                      addNewRow(response.product);

                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        toastr.error(value[0]);
                    });
                },
                complete: function() {
                    $('#saveProductBtn').prop('disabled', false);
                }
            });
        });



            updateSalesTotals();
            // $('#products').select2({
            //     tags: true,
            //     language: {
            //         noResults: function() {
            //             return `
            //         <button id="add-new-product" class="btn btn-link">
            //             @lang('sales::lang.new_product')
            //         </button>
            //     `;
            //         },
            //     },
            //     escapeMarkup: function(markup) {
            //         return markup;
            //     },
            // });


            $(document).on('change', '[name*="[inclusive]"]', function() {
                updateSalesTotals();
            });


            $('#salesTable').on('change', '[name$="[products_id]"]', function() {
                const selectedOption = $(this).find('option:selected');
                const selectedProductId = selectedOption.val();
                const price = parseFloat(selectedOption.data('price')) || 0;
                const units = selectedOption.data('units') || [];
                const currentRow = $(this).closest('tr');
                const rowIndex = currentRow.index();
                console.log('rowIndex  ' + rowIndex);

                let productFound = false;

                const unitSelect = currentRow.find(`[name="products[${rowIndex}][unit]"]`);
                unitSelect.empty();
                unitSelect.append(`<option value="">@lang('sales::lang.unit')</option>`);
                units.forEach(unit => {

                    unitSelect.append(
                        `<option value="${unit.id}" ${unit.primary ? "selected" : ""}>
                        ${unit.name_ar || unit.unit1}
                        </option>`
                    );


                });

                $('#salesTable tbody tr').each(function() {
                    const productId = $(this).find('[name$="[products_id]"]').val();

                    if (productId === selectedProductId && this !== currentRow[0]) {
                        productFound = true;

                        const qtyField = $(this).find('[name*="[qty]"]');
                        const currentQty = parseFloat(qtyField.val()) || 0;
                        qtyField.val(currentQty + 1);

                        currentRow.remove();
                        resetRowIndexes();
                        updateSalesTotals();
                    }
                });

                console.log('productFound  ' + productFound);

                if (!productFound) {
                    resetRowIndexes();

                    console.log('price  ' + price);

                    currentRow.find(`[name="products[${rowIndex}][unit_price]"]`).val(price.toFixed(2));
                    currentRow.find(`[name="products[${rowIndex}][qty]"]`).val(1);

                    updateSalesTotals();
                }
            });

            function resetRowIndexes() {
                $('#salesTable tbody tr').each(function(index) {
                    $(this).find('input, select, textarea').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            const newName = name.replace(/\[\d+\]/, `[${index}]`);
                            $(this).attr('name', newName);
                        }
                    });
                });
            }

            $('#invoice_discount, #invoiced_discount_type').on('input change', function() {
                updateSalesTotals();
            });

            $(document).on('input change', '[name="paid_amount"]', function() {
                const totalAfterVat = parseFloat($('#totalAfterVat').text());
                const paidAmount = parseFloat($(this).val());

                if (!isNaN(totalAfterVat) && !isNaN(paidAmount)) {
                    const remainingBalance = paidAmount - totalAfterVat;
                    const balance = Math.abs(totalAfterVat - paidAmount);

                    if (paidAmount === totalAfterVat) {
                        $('#balance').text('0.00');
                        $('#remaining_balance').text('0.00');
                    } else if (paidAmount > totalAfterVat) {
                        $('#remaining_balance').text(remainingBalance.toFixed(2));
                        $('#balance').text('0.00');
                    } else if (paidAmount < totalAfterVat) {
                        $('#balance').text(balance.toFixed(2));
                        $('#remaining_balance').text('0.00');
                    }
                } else {
                    $('#remaining_balance').text('0.00');
                    $('#balance').text('0.00');
                }
            });

            $(document).on('input change', '#salesTable tbody [name^="products"]', function() {
                updateSalesTotals();
            });

            $("#payment_type").change(function() {
                if ($(this).val() === "card") {
                    $("#card").show();
                } else {
                    $("#card").hide();
                }
                if ($(this).val() === "bank_check") {
                    $("#bank_check").show();
                } else {
                    $("#bank_check").hide();
                }
                if ($(this).val() === "bank_transfer") {
                    $("#bank_transfer").show();
                } else {
                    $("#bank_transfer").hide();
                }

            });

            $('#cash_account').attr('required', 'required');

            $("#invoice_type").change(function() {
                if ($(this).val() === "due") {

                    $("#li-payment_info").show();
                    $("#tab-content-payment_info").show();
                    $("#paid_amount").val(0);


                    $("#div-cash_account").hide();

                    $("#card").hide();
                    $("#bank_check").hide();
                    $("#bank_transfer").hide();
                    $('#lable-account_id').addClass('required');
                    $('#account_id').attr('required', 'required');

                    $('#cash_account').removeAttr('required');

                } else {

                    $("#li-payment_info").hide();
                    $("#tab-content-payment_info").hide();
                    $("#div-cash_account").show();
                    $('#cash_account').attr('required', 'required');
                    $('#account_id').removeAttr('required');

                }

            });


            document.getElementById('kt_modal_upload_attachments').addEventListener('click', function() {
                document.getElementById('fileInput').click();
            });


            document.getElementById('fileInput').addEventListener('change', function(event) {
                const files = event.target.files;
                const uploadInstructions = document.getElementById('uploadInstructions');


                if (files.length > 0) {
                    let fileNames = [];
                    for (let i = 0; i < files.length; i++) {
                        fileNames.push(files[i].name);
                    }

                    uploadInstructions.textContent = fileNames.join(', ');
                } else {

                    uploadInstructions.textContent = 'Upload file';
                }
            });

        });
    </script>
@stop
