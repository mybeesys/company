<div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

    <div class="card-header border-0 p-0">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">@lang('sales::fields.Line Items')</span>

        </h3>
        <div class="card-toolbar">
            <div class="btn-group dropend">

                <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                    class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu" style=" width: max-content;padding: 10px;"
                    style="padding: 8px 15px;">
                    <li class="mb-5" style="text-align: justify;">
                        <span class="card-label fw-bold fs-6 mb-1">@lang('messages.settings')</span>
                    </li>
                    <li>
                        <div class="form-check form-switch my-3"
                            style="    display: flex; justify-content: space-between; gap: 37px;">
                            <input class="form-check-input" type="checkbox" id="toggledescrption">
                            <label class="form-check-label ml-4" for="toggledescrption">@lang('sales::lang.Enable Descrption')</label>
                        </div>
                    </li>




                </ul>
            </div>
        </div>
    </div>



    @include('sales::sell.create.New-Product')


    {{-- Products --}}
    <div class="card-body p-0">
        <div class="table-responsive">

            <table class="table align-middle gs-0 gy-4 text-center" id="salesTable">
                <thead>
                    <tr class="fw-bold  text-muted bg-light">
                        <th class="min-w-280px ">@lang('sales::lang.product')</th>
                        <th class="min-w-150px product-description" style="display:none">@lang('sales::lang.description')
                        </th>
                        <th class="min-w-80px">@lang('sales::lang.qty') / @lang('sales::lang.unit')</th>
                        {{-- <th class="min-w-80px">@lang('sales::lang.unit_transfers')</th> --}}
                        <th class="min-w-190px">@lang('sales::lang.unit_price')</th>
                        <th class="min-w-200px">@lang('sales::lang.discount')</th>
                        <th class="min-w-125px">@lang('sales::lang.total_before_vat')</th>
                        <th class="min-w-10px">@lang('sales::lang.inclusive')</th>

                        <th class="min-w-200px">@lang('sales::lang.vat_percentage')</th>
                        <th class="min-w-50px">@lang('sales::lang.vat_value')</th>
                        <th class="min-w-125px">@lang('sales::lang.amount')</th>
                        <th class="min-w-25px"></th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @if ($transaction)
                        @php
                            $lines =
                                $transaction->type == 'purchases' || $transaction->type == 'purchases-order'
                                    ? $transaction->purchases_lines
                                    : $transaction->sell_lines;
                        @endphp

                        @foreach ($lines as $index => $line)
                            <tr>
                                <td>
                                    <select id="products" required class="form-select form-select-solid select-2"
                                        name="products[{{ $index }}][products_id]" style="padding: 7px">
                                        <option value="">@lang('sales::lang.select_products')</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                @if ($line->product_id == $product->id) selected @endif
                                                data-price="{{ $product->price }}"
                                                data-units="{{ json_encode($product->unitTransfers) }}">
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $product->name_ar }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-5">{{ $product->SKU }}</span>
                                                @else
                                                    {{ $product->name_en }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-7">{{ $product->SKU }}</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="product-description" style="display:none">
                                    <textarea class="form-control form-control-solid" rows="1" name="products[0][description]"></textarea>
                                </td>
                                <td style="white-space: nowrap;"><input type="number" step="any"
                                        class="form-control qty-field" name="products[{{ $index }}][qty]"
                                        placeholder="0" min="1" style="width: 80px;display: inline-block;"
                                        value="{{ $line->qyt }}">


                                    <select id="unit"
                                        class="form-select form-select-solid select-2 d-inline-block unit"
                                        name="products[{{ $index }}][unit]"
                                        style="width: 100px; display: inline-block;">
                                        <option value="">@lang('sales::lang.unit')</option>
                                    </select>
                                </td>

                                <td><input type="number" step="any" class="form-control unit_price-field no-spin"
                                        name="products[{{ $index }}][unit_price]" placeholder="0.0"
                                        value="{{ $line->unit_price }}"
                                        style="width: 100px;-moz-appearance: textfield !important">
                                </td>
                                <td style="white-space: nowrap;">
                                    <input type="number" step="any"
                                        class="form-control discount-field no-spin d-inline-block discount"
                                        name="products[{{ $index }}][discount]" id="discount" placeholder="0.0"
                                        value="{{ $line->discount_amount }}"
                                        style="width: 70px; display: inline-block;">

                                    <select id="discount_type" required
                                        class="form-select form-select-solid select-2 d-inline-block discount_type"
                                        name="products[{{ $index }}][discount_type]"
                                        style="width: 100px; display: inline-block;"
                                        value="{{ $line->discount_type }}">
                                        <option value="fixed">@get_format_currency()</option>
                                        <option value="percent">%</option>
                                    </select>
                                </td>

                                <td><input type="number" step="any" readonly
                                        class="form-control total_before_vat-field"
                                        name="products[{{ $index }}][total_before_vat]" placeholder="0.00"
                                        style="width: 107px;"></td>


                                <td class="d-flex justify-content-center">
                                    <div class="form-check">
                                        <input type="checkbox" style="border: 1px solid #9f9f9f;" id="inclusive"
                                            name="products[{{ $index }}][inclusive]"
                                            class="form-check-input  my-2">
                                    </div>

                                </td>
                                <td>
                                    <select id="tax_vat" required
                                        class="form-select form-select-solid select-2 tax-select"
                                        name="products[{{ $index }}][tax_vat]" style="width: 200px;"
                                        data-is-tax-group="{{ $tax->is_tax_group }}"
                                        data-sub-taxes="{{ json_encode($tax->sub_taxes ?? []) }}"
                                        data-minimum-limits="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}">
                                        @foreach ($taxes as $tax)
                                            <option value="{{ $tax->amount }}"
                                                data-is-tax-group="{{ $tax->is_tax_group }}"
                                                data-sub-taxes="{{ json_encode($tax->sub_taxes ?? []) }}"
                                                data-minimum-limits="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}"
                                                @if ($tax->default == 1 || $line->tax_id == $tax->amount) selected @endif>
                                                @if (app()->getLocale() == 'en')
                                                    {{ $tax->name_en }}
                                                @else
                                                    {{ $tax->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="products[{{ $index }}][is_tax_group]"
                                        value="{{ $tax->is_tax_group ?? 0 }}" class="is-tax-group">
                                    <input type="hidden" name="products[{{ $index }}][sub_taxes]"
                                        value="{{ json_encode($tax->sub_taxes ?? []) }}" class="sub-taxes">
                                    <input type="hidden" name="products[{{ $index }}][minimum_limits]"
                                        value="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}"
                                        class="minimum-limits">
                                </td>
                                <td><input type="number" step="any" readonly
                                        class="form-control vat_value-field"
                                        name="products[{{ $index }}][vat_value]" placeholder="0.00"
                                        style="width: 80px;"></td>
                                <td><input type="number" step="any" readonly
                                        class="form-control total_after_vat-field"
                                        name="products[{{ $index }}][total_after_vat]" placeholder="0.00"
                                        style="width: 107px;"></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>
                                <select id="products" required class="form-select form-select-solid select-2"
                                    name="products[0][products_id]" style="padding: 7px">
                                    <option value="">@lang('sales::lang.select_products')</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                            data-units="{{ json_encode($product->unitTransfers) }}">
                                            @if (app()->getLocale() == 'ar')
                                                {{ $product->name_ar }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-5">{{ $product->SKU }}</span>
                                            @else
                                                {{ $product->name_en }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-7">{{ $product->SKU }}</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="product-description" style="display:none">
                                <textarea class="form-control form-control-solid" rows="1" name="products[0][description]"></textarea>
                            </td>
                            <td style="white-space: nowrap;"><input type="number" step="any"
                                    class="form-control qty-field" name="products[0][qty]" placeholder="0"
                                    min="1" style="width: 80px;display: inline-block;">


                                <select id="unit" required
                                    class="form-select form-select-solid select-2 d-inline-block unit"
                                    name="products[0][unit]" style="width: 100px; display: inline-block;">
                                    <option value="">@lang('sales::lang.unit')</option>
                                </select>
                            </td>

                            <td><input type="number" step="any" class="form-control unit_price-field no-spin"
                                    name="products[0][unit_price]" placeholder="0.0"
                                    style="width: 100px;-moz-appearance: textfield !important">
                            </td>
                            <td style="white-space: nowrap;">
                                <input type="number" step="any"
                                    class="form-control discount-field no-spin d-inline-block discount"
                                    name="products[0][discount]" id="discount" placeholder="0.0"
                                    style="width: 70px; display: inline-block;">

                                <select id="discount_type" required
                                    class="form-select form-select-solid select-2 d-inline-block discount_type"
                                    name="products[0][discount_type]" style="width: 100px; display: inline-block;">
                                    <option value="fixed">@get_format_currency()</option>
                                    <option value="percent">%</option>
                                </select>
                            </td>

                            <td><input type="number" step="any" readonly
                                    class="form-control total_before_vat-field" name="products[0][total_before_vat]"
                                    placeholder="0.00" style="width: 107px;"></td>


                            <td class="d-flex justify-content-center">
                                <div class="form-check">
                                    <input type="checkbox" style="border: 1px solid #9f9f9f;" id="inclusive"
                                        name="products[0][inclusive]" class="form-check-input  my-2">
                                </div>

                            </td>
                            <td>
                                <select id="tax_vat" required
                                    class="form-select form-select-solid select-2 tax-select"
                                    name="products[0][tax_vat]" style="width: 200px;">
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
                                <input type="hidden" name="products[0][is_tax_group]" value="0"
                                    class="is-tax-group">
                                <input type="hidden" name="products[0][sub_taxes]" value="[]"
                                    class="sub-taxes">
                                <input type="hidden" name="products[0][minimum_limits]" value="[]"
                                    class="minimum-limits">
                            </td>
                            <td><input type="number" step="any" readonly class="form-control vat_value-field"
                                    name="products[0][vat_value]" placeholder="0.00" style="width: 80px;"></td>
                            <td><input type="number" step="any" readonly
                                    class="form-control total_after_vat-field" name="products[0][total_after_vat]"
                                    placeholder="0.00" style="width: 107px;"></td>
                        </tr>
                    @endif

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="1" class="total">
                            <a class="btn btn-xs btn-default text-primary" id="addSalesRow">
                                <i class="ki-outline ki-plus fs-2"></i>
                                @lang('accounting::lang.new_row')
                            </a>
                        </td>
                    </tr>
                </tfoot>


            </table>

        </div>

    </div>

    {{-- invoice ditales --}}

    <div class="card-body p-0 mt-5 d-flex flex-column">
        <div class="d-flex align-items-center mb-5 mx-10">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 100px;">@lang('sales::lang.invoice_discount')</label>
            <input class="form-control form-control-solid  no-spin" style="width: 150px;" name="invoice_discount"
                value="" placeholder="0.00" id="invoice_discount" type="number">
            <select id="invoiced_discount_type" required
                class="form-select form-select-solid select-2 d-inline-block invoiced_discount_type mx-3"
                name="invoiced_discount_type" style="width: 100px; display: inline-block;">
                <option value="fixed">@get_format_currency()</option>
                <option value="percent">%</option>
            </select>
        </div>
        <div class="card-p pt-0 bg-body flex-grow-1">

            <div class="d-flex flex-column flex-grow-1 ">
                <div class="d-flex flex-wrap">



                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                        <div class="d-flex align-items-center">
                            <input type="hidden" id="input-totalBeforeVat" name="totalBeforeVat" />
                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500"
                                data-kt-countup-prefix="$" data-kt-initialized="1" id="totalBeforeVat">
                                0.00</div><span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.total_before_vat')</div>

                    </div>

                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                        <div class="d-flex align-items-center">
                            <input type="hidden" id="input-invoiced_discount" name="invoiced_discount" />

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500"
                                data-kt-countup-prefix="$" data-kt-initialized="1" id="_invoiced_discount">
                                0.00</div><span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.discount')</div>

                    </div>


                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                        <div class="d-flex align-items-center">
                            <input type="hidden" id="input-totalAfterDiscount" name="totalAfterDiscount" />

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500"
                                data-kt-countup-prefix="$" data-kt-initialized="1" id="totalAfterDiscount">
                                0.00</div><span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.totalAfterDiscount')</div>

                    </div>


                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                        <div class="d-flex align-items-center">
                            <input type="hidden" id="input-totalVat" name="totalVat" />

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500"
                                data-kt-countup-prefix="$" data-kt-initialized="1" id="totalVat">
                                0.00</div>
                            <span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.vat_value')</div>

                    </div>


                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                        <div class="d-flex align-items-center">
                            <input type="hidden" id="input-totalAfterVat" name="totalAfterVat" />

                            <div class="fs-2 fw-bold counted" data-kt-countup="true" data-kt-countup-value="4500"
                                data-kt-countup-prefix="$" data-kt-initialized="1" id="totalAfterVat">
                                0.00</div><span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>

                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.amount')</div>

                    </div>



                </div>

            </div>

        </div>
    </div>
</div>
