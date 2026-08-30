@extends('layouts.app')

@section('title', __('purchases::lang.Create a sales invoice'))
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
    <form id="sell_save" method="POST"
        action="{{ ($isEditDraft ?? false) ? route('update-purchases-invoice', $transaction->id) : route('store-purchases-invoice') }}">
        @csrf
        @if ($isEditDraft ?? false)
            @method('PUT')
        @endif
        <input type="hidden" name="po_id" id="po_id" value="{{ old('po_id', ($isEditDraft ?? false) ? ($transaction->parent_id ?? '') : '') }}">
        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1>
                            @if ($isEditDraft ?? false)
                                @lang('purchases::lang.edit_draft_invoice')
                                <span class="text-gray-600 fs-5"> — {{ $transaction->ref_no }}</span>
                            @else
                                @lang('purchases::lang.Create a sales invoice')
                            @endif
                            @if ($isDuplicate ?? false)
                                <span class="text-gray-600 fs-5"> — @lang('purchases::lang.duplicate_from_ref', ['ref' => $transaction->ref_no])</span>
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

        @if (!empty($invoicePrecheckConfig['missingAccounts']))
            <div class="container mb-4">
                <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-start gap-3">
                    <div class="flex-grow-1">
                        <div class="fw-bold mb-2">
                            {{ $invoicePrecheckConfig['messages']['missingAccountsHeader'] ?? __('messages.something_went_wrong') }}
                        </div>
                        <ul class="mb-0 ps-5">
                            @foreach ($invoicePrecheckConfig['missingAccounts'] as $missing)
                                <li>{{ $missing }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mt-2 mt-sm-0">
                        <a href="{{ route('accounts-routing') }}" class="btn btn-sm btn-dark">
                            @lang('menuItemLang.accounts-routing')
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="">
            <div class="row">
                <div class="col-sm">

                    {{-- invoice information --}}
                    @include('purchases::purchases.create.invoice-info')

                </div>
                <div class="col-6">

                    @include('purchases::purchases.create.client-info')

                </div>

                <div class="separator d-flex flex-center my-6">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>

                @include('sales::sell.create.line-items')


            </div>

            @include('purchases::purchases.create.Tab-nav')

            @include('general::invoice-setting.general-invoice-setting.terms-conditions-notes')

            <div class="separator d-flex flex-center my-6">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="btn-group">
                        @if ($Latest_event->action == 'save')
                            <button type="button" class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px"
                                data-action="save" data-status="final">@lang('messages.save')
                            </button>
                        @elseif ($Latest_event->action == 'save_add')
                            <button type="button" class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px"
                                data-action="save_add" data-status="final">@lang('messages.save&add')
                            </button>
                        @else
                            <button type="button" class="btn btn-primary fv-row flex-md-root text-center min-w-150px mw-250px"
                                data-action="save_print" data-status="final">@lang('messages.save&print')
                            </button>
                        @endif


                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split "
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>

                        <ul class="dropdown-menu p-5">
                            <li>
                                <button type="button" class="dropdown-item" data-action="save"
                                    data-status="final">@lang('messages.save')
                                </button>
                            </li>

                            <li>
                                <button type="button" class="dropdown-item" data-action="save_add"
                                    data-status="final">@lang('messages.save&add')
                                </button>
                            </li>

                            <li>
                                <button type="button" class="dropdown-item" data-action="save_print"
                                    data-status="final">@lang('messages.save&print')</button>

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

                                            <span class="fw-semibold mx-2 text-muted fs-7 px-2">@lang('purchases::lang.remaining_balance')</span>
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

                                            <span class="fw-semibold mx-2 text-muted fs-7 px-2">@lang('purchases::lang.balance')</span>
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

    @include('purchases::purchases.create.add-client')




@stop

@section('script')
    <script>
        window.invoicePrecheckConfig = @json($invoicePrecheckConfig ?? []);
    </script>
    <script src="{{ url('/modules/Sales/js/clients.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/invoice-type-account-toggle.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/line-items-select2.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/settings.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/invoice-calculations.js') }}"></script>
    <script src="{{ url('/modules/Sales/js/sell-invoice-submit.js') }}?v={{ @filemtime(public_path('modules/Sales/js/sell-invoice-submit.js')) ?: time() }}"></script>
    <script>
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
                                text: lang === 'ar' ?
                                    `${product.SKU} - ${product.name_ar}` :
                                    `${product.SKU} - ${product.name_en}`,
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

                    selectedData.units.forEach((unit, index) => {
                        const unitId = unit.id || unit.unit_id || unit.unit1;
                        const unitName = unit.name || unit.unit_name || unit.unit1 ||
                            'وحدة غير معروفة';
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
                        dropdownParent: $row.closest('.modal').length ? $row.closest('.modal') :
                            document.body
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
            console.log("salesRowIndex " + salesRowIndex);
        });
        $('#addNewAccountBtn').on('click', function() {
            $('#addClientModal').modal('show');
        });

        $(document).ready(function() {
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
            const urlParams = new URLSearchParams(window.location.search);

            const poId = urlParams.get('po_id');

            if (poId) {
                document.getElementById('po_id').value = poId;
            }
            if (urlParams.get('duplicate_from')) {
                document.getElementById('po_id').value = '';
            }
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
                let units = selectedOption.data('units') || [];
                if (typeof units === 'string') {
                    try {
                        units = JSON.parse(units);
                    } catch (e) {
                        units = [];
                    }
                }
                const currentRow = $(this).closest('tr');

                let productFound = false;

                const unitSelect = currentRow.find('.unit');
                unitSelect.empty();
                unitSelect.append(`<option value="">@lang('sales::lang.unit')</option>`);
                units.forEach(unit => {
                    unitSelect.append(
                        `<option value="${unit.id}" ${unit.primary ? "selected" : ""}>
                        ${unit.name_ar || unit.name_en || unit.unit1}
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
                    currentRow.find('.unit_price-field').val(price.toFixed(2));
                    currentRow.find('.qty-field').val(1);

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
