@extends('layouts.app')

@section('title', __('sales::lang.Create a sales quotation'))
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

        #salesTable tbody tr.sales-line-row {
            cursor: grab;
        }

        #salesTable tbody tr.sales-line-row.dragging-row {
            opacity: 0.92;
            cursor: grabbing;
            background: #fafbfc;
        }

        #salesTable tbody tr.sales-line-row.drop-target {
            outline: 1px dashed #c5c8d0;
            outline-offset: -1px;
        }

        #salesTable .sales-line-reorder .btn {
            min-width: 1.75rem;
            min-height: 1.5rem;
            padding: 0.1rem 0.25rem;
            border: 0;
            box-shadow: none;
        }

        #salesTable .sales-line-reorder .btn:hover {
            background-color: #eff0f3;
        }

        #salesTable .sales-line-reorder .btn:focus-visible {
            box-shadow: 0 0 0 1px #b5b8c0;
        }
    </style>


@stop
@section('content')
    <form id="sell_save" method="POST" action="{{ route('store-quotation') }}">
        @csrf

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1>
                            @lang('sales::lang.Create a sales quotation')
                            @if ($isDuplicate ?? false)
                                <span class="text-gray-600 fs-5"> — @lang('sales::lang.duplicate_from_ref', ['ref' => $transaction->ref_no])</span>
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

            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div class="row">
                <div class="col-sm-3">
                    <div class="btn-group dropend">
                        <button type="button" style="border-radius: 6px;" class="btn btn-primary dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            @lang('messages.save')
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu"
                            style="width: max-content; padding: 10px;">
                            <li class="" style="text-align: justify;">
                                <a class="dropdown-item" type="submit" href="#" data-action="save_add"
                                    data-status="final">@lang('messages.save&add')</a>
                            </li>
                            <li class="" style="text-align: justify;">
                                <a class="dropdown-item" href="#" data-action="save_print"
                                    data-status="final">@lang('messages.save&print')</a>
                            </li>
                        </ul>
                    </div>


                    <input type="hidden" name="status" value="draft" />
                    {{-- <button type="submit" style="border-radius: 6px;" class="btn btn-bg-dark text-white ">
                        @lang('messages.savedraft')
                    </button> --}}
                </div>
                <div class="col-sm " style="justify-items: end">
                    <div class="card-body p-0 d-flex flex-column">

                        <div class="card-p p-0 bg-body flex-grow-1" style="padding: 0px !important">

                            <div class="d-flex flex-column flex-grow-1 ">

                                <div class="d-flex flex-wrap">



                                    {{-- <div class="border border-gray-300 border-dashed rounded min-w-125px  px-4 me-6 "
                                        style="    height: max-content;padding: 6px;">
                                        <div class="d-flex align-items-center">

                                            <span class="fw-semibold mx-2 text-muted fs-7 px-2">@lang('sales::lang.remaining_balance')</span>
                                            <div class="fs-2 fw-bold counted" data-kt-countup="true"
                                                data-kt-countup-value="4500" data-kt-countup-prefix="$"
                                                data-kt-initialized="1" id="remaining_balance" style="color: red;">
                                                0.00</div><span
                                                class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                                        </div>
                                    </div> --}}

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
    <script src="{{ url('/modules/Sales/js/line-items-select2.js') }}"></script>
    {{-- <script src="{{ asset('Modules/Sales/js/clients.js') }}"></script> --}}
    <script>
        $("#addClientForm").on("submit", function(e) {
            e.preventDefault();

            let formData = $(this).serialize();

            $.ajax({
                url: "/client-save",
                method: "POST",
                data: formData,
                success: function(response) {
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
                error: function(xhr) {
                    // alert("@lang('sales::fields.client_add_error')");
                    console.error(xhr.responseText);
                },
            });
        });


        $("#client_id").on("change", function() {
            var selectedOption = $(this).find(":selected");

            var clientName = selectedOption.data("name") || '--';
            var mobileNumber = selectedOption.data("mobile_number") || '-';
            var email = selectedOption.data("email") || "-";
            var taxNumber = selectedOption.data("tax_number") || "-";
            var billing_address = selectedOption.data("billing_address") || "-";
            var billing_street_name = selectedOption.data("billing_street_name") || "-";
            var billing_city = selectedOption.data("billing_city") || "-";


            // console.log(billing_address);

            $("#client_name").text(clientName);
            if (billing_street_name != '-' || billing_city != '-') {
                $("#billing_address").text(billing_address);
                $("#dev-billing_address").show();
            } else {
                $("#dev-billing_address").hide();
            }


            if (mobileNumber != '-') {
                $("#mobile_number").text(mobileNumber);
                $("#dev-mobile_number").show();
            } else {
                $("#dev-mobile_number").hide();
            }
            if (email != '-') {
                $("#email").text(email);
                $("#dev-email").show();
            } else {
                $("#dev-email").hide();
            }
            if (taxNumber != '-') {
                $("#tax_number").text(taxNumber);
                $("#dev-tax_number").show();
            } else {
                $("#dev-tax_number").hide();
            }
            // $("#tax_number").text(taxNumber);
        });


        $('#billing_country').select2();
        $('#shipping_status').select2();
        $('#cost_center').select2();
        $('#Delegates').select2({
            width: 'resolve'
        });
        $('#invoice_type').select2({
            width: 'resolve'
        });
        $('#account_id').select2();

        $('#payment_type').select2({
            width: 'resolve'
        });
        $('#cash_account').select2({
            width: 'resolve'
        });

        $('#client_id').select2({
            width: 'resolve'
        });
        $('#storehouse').select2({
            width: 'resolve'
        });
        // Line-item unit / tax selects: use initPrefilledSalesLineSelect2 (per-row ids in line-items partial).
        // $('#products').select2();
        $('#payment_terms').select2({
            width: 'resolve'
        });

        $('#cost_center').select2({
            width: 'resolve'
        });



        $('#toggledescrption').on('change', function() {
            if ($(this).is(':checked')) {
                $('.product-description').show();
            } else {
                $('.product-description').hide();
            }
        });

        $('#toggleCost_center').on('change', function() {
            if ($(this).is(':checked')) {
                $('#dev-costCenter').show();
            } else {
                $('#dev-costCenter').hide();
            }
        });



        $('#toggleStorehouse').on('change', function() {
            if ($(this).is(':checked')) {
                $('#div-storehouse').show();
            } else {
                $('#div-storehouse').hide();
            }
        });


        $('#toggleDelegates').on('change', function() {
            if ($(this).is(':checked')) {
                $('#div-Delegates').show();
            } else {
                $('#div-Delegates').hide();
            }
        });


        $('#addNewAccountBtn').on('click', function() {
            $('#addClientModal').modal('show');
        });


        $(document).on('click', '.dropdown-item', function(e) {
            e.preventDefault();
            let action = $(this).data('action');

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
            }
            $('<input>').attr({
                type: 'hidden',
                name: 'status',
                value: 'approved'
            }).appendTo('#sell_save');
            $('#sell_save').submit();
        });


        $(document).ready(function() {
            $("#dev-mobile_number").hide();
            $("#dev-billing_address").hide();
            $("#dev-email").hide();
            $("#dev-tax_number").hide();

            let draggedRow = null;
            let isRowDragging = false;

            function bindSalesRowsDragAndDrop() {
                $('#salesTable tbody tr.sales-line-row').attr('draggable', true);
            }

            $('#salesTable tbody').on('click', '.sales-line-move-up', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $row = $(this).closest('tr.sales-line-row');
                const $prev = $row.prev('.sales-line-row');
                if ($prev.length) {
                    $row.insertBefore($prev);
                    resetRowIndexes();
                    updateSalesTotals();
                }
            });

            $('#salesTable tbody').on('click', '.sales-line-move-down', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const $row = $(this).closest('tr.sales-line-row');
                const $next = $row.next('.sales-line-row');
                if ($next.length) {
                    $row.insertAfter($next);
                    resetRowIndexes();
                    updateSalesTotals();
                }
            });

            $('#salesTable tbody').on('mousedown', 'tr.sales-line-row', function(e) {
                if (e.which !== 1) {
                    return;
                }
                if ($(e.target).closest('input, select, textarea, button, a, .select2-container, .select2-selection').length) {
                    return;
                }
                e.preventDefault();
                draggedRow = this;
                isRowDragging = true;
                $('body').css('user-select', 'none');
                $(draggedRow).addClass('dragging-row');
            });

            $(document).on('mouseup', function() {
                if (!isRowDragging) return;
                isRowDragging = false;
                $('body').css('user-select', '');
                $('#salesTable tbody tr.sales-line-row').removeClass('dragging-row drop-target');
                draggedRow = null;
            });

            $(document).on('mousemove', function(e) {
                if (!isRowDragging || !draggedRow) return;
                const hoveredRow = $(e.target).closest('#salesTable tbody tr.sales-line-row')[0];
                if (!hoveredRow || draggedRow === hoveredRow) return;

                $('#salesTable tbody tr.sales-line-row').removeClass('drop-target');
                $(hoveredRow).addClass('drop-target');
                $(draggedRow).insertBefore(hoveredRow);
                resetRowIndexes();
                updateSalesTotals();
            });

            bindSalesRowsDragAndDrop();

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


    function fetchProducts() {
    $.ajax({
        url: '/products-for-sale',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            updateSelect2WithProducts(response.data);
        },
        error: function(xhr, status, error) {
            console.error('Error fetching products:', error);
        }
    });
}

function updateSelect2WithProducts(products) {
    $('.select-2-products-id').empty().append('<option value="">@lang('sales::lang.select_products')</option>');

    products.forEach(function(product) {
        var optionText = (appLocale === 'ar') ?
            `${product.name_ar} - <span class="fw-semibold mx-2 text-muted fs-5">${product.SKU}</span>` :
            `${product.name_en} - <span class="fw-semibold mx-2 text-muted fs-7">${product.SKU}</span>`;

        var option = new Option(optionText, product.id, false, false);
        option.dataset.price = product.price;
        option.dataset.units = JSON.stringify(product.unit_transfers);

        $('.select-2-products-id').append(option);
    });

    $('.select-2-products-id').trigger('change');
}


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
                    fetchProducts();

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


            // $('[name$="[products_id]"]').on('change', function() {
            //     var selectedOption = $(this).val();

            //     if (selectedOption == 'new-product') {
            //         $('#addProductModal').modal('show');
            //     }
            // });

               let salesRowIndex = 1;

      $("#addSalesRow").on("click", function() {
    salesRowIndex++;

    const newSalesRow = `
        <tr class="sales-line-row" draggable="true">
            <td class="sales-line-reorder-cell align-middle p-1 text-center">
                <div class="d-flex flex-column gap-0 align-items-center sales-line-reorder">
                    <button type="button" class="btn btn-sm btn-icon btn-light btn-color-gray-600 sales-line-move-up" title="@lang('sales::lang.move_line_up')">
                        <i class="ki-outline ki-arrow-up fs-6"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light btn-color-gray-600 sales-line-move-down" title="@lang('sales::lang.move_line_down')">
                        <i class="ki-outline ki-arrow-down fs-6"></i>
                    </button>
                </div>
            </td>
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
                                    <td style="white-space: nowrap;">
                                        <input type="number" step="any"
                                            class="form-control qty-field"
                                            name="products[${salesRowIndex}][qty]"
                                            placeholder="0" min="1"
                                            style="width: 80px; display: inline-block; vertical-align: middle;">
                                        <select class="form-select form-select-solid select-2 d-inline-block unit"
                                            name="products[${salesRowIndex}][unit]"
                                            style="width: 110px; display: inline-block; vertical-align: middle;">
                                            <option value="">@lang('sales::lang.unit')</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="any" class="form-control unit_price-field no-spin" name="products[${salesRowIndex}][unit_price]" placeholder="0.0" style="width: 100px;"></td>
            <td style="white-space: nowrap;">
                <input type="number" step="any" class="form-control discount-field no-spin d-inline-block discount" name="products[${salesRowIndex}][discount]" placeholder="0.0" style="width: 70px; display: inline-block;">
                <select id="discount_type-${salesRowIndex}" required class="form-select form-select-solid select-2 d-inline-block discount_type" name="products[${salesRowIndex}][discount_type]" style="width: 100px; display: inline-block;">
                    <option value="fixed">@get_format_currency()</option>
                    <option value="percent">%</option>
                </select>
            </td>
            <td><input type="number" step="any" readonly class="form-control total_before_vat-field" name="products[${salesRowIndex}][total_before_vat]" placeholder="0.00" style="width: 107px;"></td>
            <td class="d-flex justify-content-center">
                <div class="form-check">
                    <input type="checkbox" style="border: 1px solid #9f9f9f;" id="inclusive-${salesRowIndex}" name="products[${salesRowIndex}][inclusive]" class="form-check-input my-2">
                </div>
            </td>
               <td>
            <select id="tax_vat-${salesRowIndex}" required class="form-select form-select-solid select-2"
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
            url: '/products-for-quotation',
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
        // console.log('Units Data:', selectedData.units);

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

        if( selectedData.units.length > 0){
    $unitSelect.select2('destroy').select2({
            width: 'resolve',
            dropdownParent: $row.closest('.modal').length ? $row.closest('.modal') : document.body
        });

        }
    updateSalesTotals();

    } else {
        console.error('No units data found for product:', selectedData.id);
        $unitSelect.append('<option value="piece" data-value="1">Piece</option>');
  updateSalesTotals();  }
});

    if (typeof window.initNewSalesLineNonProductSelect2 === 'function') {
        window.initNewSalesLineNonProductSelect2();
    }

    updateSalesTotals();
    // console.log("salesRowIndex " + salesRowIndex);
});


            function updateSalesTotals() {
    let totalBeforeVat = 0;
    let totalVat = 0;
    let totalAfterVat = 0;
    let totalBeforeDiscountForVat = 0;

    $('#salesTable tbody tr').each(function(index) {
        const qty = parseFloat($(this).find(`[name="products[${index}][qty]"]`).val()) || 0;
        const unitPriceOriginal = parseFloat($(this).find(`[name="products[${index}][unit_price]"]`).val()) || 0;
        const discountValue = parseFloat($(this).find(`[name="products[${index}][discount]"]`).val()) || 0;
        const discountType = $(this).find(`[name="products[${index}][discount_type]"]`).val();
        const taxType = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
        const isInclusive = $(this).find(`[name="products[${index}][inclusive]"]`).is(':checked');

        let unitPrice = unitPriceOriginal;

        if (isInclusive && taxType > 0) {
            unitPrice = unitPriceOriginal / (1 + taxType / 100);
        }

        let discountAmount = 0;
        if (discountType === 'percent') {
            discountAmount = (qty * unitPrice) * (discountValue / 100);
        } else {
            discountAmount = discountValue;
        }

        const totalBeforeDiscount = (qty * unitPrice) - discountAmount;

        let vatAmount = 0;
        if (taxType > 0) {
            vatAmount = totalBeforeDiscount * (taxType / 100);
            totalBeforeDiscountForVat += totalBeforeDiscount;
        }

        const totalRow = totalBeforeDiscount + vatAmount;

        $(this).find('.total_before_vat-field').val(totalBeforeDiscount.toFixed(2));
        $(this).find('.vat_value-field').val(vatAmount.toFixed(2));
        $(this).find('.total_after_vat-field').val(totalRow.toFixed(2));

        totalBeforeVat += totalBeforeDiscount;
        totalVat += vatAmount;
        totalAfterVat += totalRow;
    });

    const invoiceDiscount = parseFloat($('#invoice_discount').val()) || 0;
    const discountType = $('#invoiced_discount_type').val();

    let totalDiscountAmount = 0;
    if (discountType === 'percent') {
        totalDiscountAmount = totalBeforeVat * (invoiceDiscount / 100);
    } else {
        totalDiscountAmount = invoiceDiscount;
    }

    const adjustedTotalForVat = totalBeforeDiscountForVat - totalDiscountAmount;

    let adjustedVat = 0;
    $('#salesTable tbody tr').each(function(index) {
        const taxType = parseFloat($(this).find(`[name="products[${index}][tax_vat]"]`).val()) || 0;
        const rowTotalBeforeDiscount = parseFloat($(this).find('.total_before_vat-field').val()) || 0;

        if (taxType > 0) {
            const rowDiscountShare = (rowTotalBeforeDiscount / totalBeforeVat) * totalDiscountAmount;
            const rowAdjustedTotal = rowTotalBeforeDiscount - rowDiscountShare;
            adjustedVat += rowAdjustedTotal * (taxType / 100);
        }
    });

    const totalAfterDiscount = totalBeforeVat - totalDiscountAmount;
    const finalTotalAfterVat = totalAfterDiscount + adjustedVat;

    adjustedVat = adjustedVat > 0 ? adjustedVat : 0;

    $('#totalBeforeVat').text(totalBeforeVat.toFixed(2));
    $('#input-totalBeforeVat').val(totalBeforeVat.toFixed(2));
    $('#_invoiced_discount').text(totalDiscountAmount.toFixed(2));
    $('#input-invoiced_discount').val(totalDiscountAmount.toFixed(2));
    $('#totalAfterDiscount').text(totalAfterDiscount.toFixed(2));
    $('#input-totalAfterDiscount').val(totalAfterDiscount.toFixed(2));
    $('#totalVat').text(adjustedVat.toFixed(2));
    $('#input-totalVat').val(adjustedVat.toFixed(2));
    $('#totalAfterVat').text(finalTotalAfterVat.toFixed(2));
    $('#input-totalAfterVat').val(finalTotalAfterVat.toFixed(2));

    if ($('#invoice_type').val() === "due") {
        $("#paid_amount").val(0);
    } else {
        $('#paid_amount').val(finalTotalAfterVat.toFixed(2));
    }
}



            $(document).on('change', '[name*="[inclusive]"]', function() {
                updateSalesTotals();
            });


            // $('#salesTable').on('change', '[name$="[products_id]"]', function() {
            //     const selectedOption = $(this).find('option:selected');
            //     const selectedProductId = selectedOption.val();
            //     const price = parseFloat(selectedOption.data('price')) || 0;
            //     const currentRow = $(this).closest('tr');
            //     const rowIndex = currentRow.index();
            //     console.log('rowIndex  ' + rowIndex);

            //     let productFound = false;

            //     $('#salesTable tbody tr').each(function() {
            //         const productId = $(this).find('[name$="[products_id]"]').val();

            //         if (productId === selectedProductId && this !== currentRow[0]) {
            //             productFound = true;

            //             const qtyField = $(this).find('[name*="[qty]"]');
            //             const currentQty = parseFloat(qtyField.val()) || 0;
            //             qtyField.val(currentQty + 1);

            //             // updateSalesTotals();

            //             currentRow.remove();
            //             resetRowIndexes();
            //             updateSalesTotals();
            //         }
            //     });
            //     console.log('productFound  ' + productFound);

            //     if (!productFound) {
            //         resetRowIndexes();

            //         console.log('price  ' + price);

            //         currentRow.find(`[name="products[${rowIndex}][unit_price]"]`).val(price.toFixed(
            //             2));
            //         currentRow.find(`[name="products[${rowIndex}][qty]"]`).val(
            //             1);




            //         updateSalesTotals();
            //     }
            // });

            $('#salesTable').on('change', '[name$="[products_id]"]', function() {
                const selectedOption = $(this).find('option:selected');
                const selectedProductId = selectedOption.val();
                const price = parseFloat(selectedOption.data('price')) || 0;
                const units = selectedOption.data('units') || [];
                const currentRow = $(this).closest('tr');
                const rowIndex = currentRow.index();
                // console.log('rowIndex  ' + rowIndex);

                let productFound = false;

                const unitSelect = currentRow.find(`[name="products[${rowIndex}][unit]"]`);
                unitSelect.empty();
                unitSelect.append(`<option value="">@lang('sales::lang.unit')</option>`);
                units.forEach(unit => {
                    unitSelect.append(
                        `<option value="${unit.id}">${unit.name_ar || unit.unit1}</option>`
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

                // console.log('productFound  ' + productFound);

                if (!productFound) {
                    resetRowIndexes();

                    // console.log('price  ' + price);

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
                bindSalesRowsDragAndDrop();
            }

            function ensureAtLeastOneSalesLineRow() {
                if ($('#salesTable tbody tr.sales-line-row').length === 0 && $('#addSalesRow').length) {
                    $('#addSalesRow').trigger('click');
                    resetRowIndexes();
                    bindSalesRowsDragAndDrop();
                }
            }

            $(document).on('click', '.delete-sales-row', function() {
                $(this).closest('tr').remove();
                resetRowIndexes();
                updateSalesTotals();
                ensureAtLeastOneSalesLineRow();
            });



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


            $("#invoice_type").change(function() {
                if ($(this).val() === "due") {
                    // $(".pay-pament_on").hide();
                    // $(".pay-payment_type").hide();
                    // $(".pay-paid_amount").hide();
                    // $(".pay-additionalNotes").hide();
                    $('#nots_tab').tab('show');

                    $("#li-payment_info").show();
                    $("#paid_amount").val(0);

                    $("#div-cash_account").hide();

                    $("#card").hide();
                    $("#bank_check").hide();
                    $("#bank_transfer").hide();

                } else {
                    // $(".pay-pament_on").show();
                    // $(".pay-payment_type").show();
                    // $(".pay-paid_amount").show();
                    // $(".pay-additionalNotes").show();
                    $('#nots_tab').tab('show');

                    $("#li-payment_info").hide();
                    $("#div-cash_account").show();

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

            if (typeof window.initPrefilledSalesLineSelect2 === 'function') {
                window.initPrefilledSalesLineSelect2();
            }
            ensureAtLeastOneSalesLineRow();

        });
    </script>
@stop
