<div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

    <div class="card-header border-0 p-0">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">@lang('sales::fields.Line Items')</span>

        </h3>
        <div class="card-toolbar">

            <a href="#" class="add-new-product mx-2" data-bs-toggle="modal"
                data-bs-target="#addProductModal">@lang('sales::lang.add_new_product')</a>
            {{-- <div class="btn-group dropend">

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
            </div> --}}
        </div>
    </div>



    @include('sales::sell.create.New-Product')


    {{-- Products --}}
    <div class="card-body p-0">
        <div class="table-responsive">

            <table class="table align-middle gs-0 gy-4 text-center" id="salesTable">
                <thead>
                    <tr class="fw-bold  text-muted bg-light">
                        <th class="min-w-35px w-35px p-2" aria-hidden="true"></th>
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
                            @if ($line->line_status != 'completed' || request('type') == 'duplication')
                                <tr class="sales-line-row" draggable="true">
                                    <td class="sales-line-reorder-cell align-middle p-1 text-center">
                                        <div class="d-flex flex-column gap-0 align-items-center sales-line-reorder">
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light btn-color-gray-600 sales-line-move-up"
                                                title="@lang('sales::lang.move_line_up')">
                                                <i class="ki-outline ki-arrow-up fs-6"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light btn-color-gray-600 sales-line-move-down"
                                                title="@lang('sales::lang.move_line_down')">
                                                <i class="ki-outline ki-arrow-down fs-6"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <select id="products-{{ $index }}" required
                                            class="form-select form-select-solid select-2 product-select"
                                            {{-- @if ($line->line_status == 'completed') disabled @endif --}} name="products[{{ $index }}][products_id]"
                                            style="padding: 7px">
                                            <option value="">@lang('sales::lang.select_products')</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    @if ($line->product_id == $product->id) selected @endif
                                                    data-price="{{ $product->price }}"
                                                    data-cost="{{ $product->cost ?? 0 }}"
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
                                        @if (!empty($sellWithModifiersCombos))
                                            @php
                                                $smcPrefill = \Modules\Sales\Services\WebSellModifiersCombosService::serializeChildrenForPrefill(
                                                    $line->childLines ?? collect()
                                                );
                                            @endphp
                                            <input type="hidden" class="smc-order-item-modifiers"
                                                name="products[{{ $index }}][order_item_modifiers]"
                                                value="{{ e(json_encode($smcPrefill['modifiers'])) }}">
                                            <input type="hidden" class="smc-order-item-combos"
                                                name="products[{{ $index }}][order_item_combos]"
                                                value="{{ e(json_encode($smcPrefill['combos'])) }}">
                                            <input type="hidden" class="smc-extras-before-vat"
                                                name="products[{{ $index }}][extras_before_vat]"
                                                value="{{ $smcPrefill['extras_before_vat'] }}">
                                            <input type="hidden" class="smc-extras-inc-tax"
                                                name="products[{{ $index }}][extras_inc_tax]"
                                                value="{{ $smcPrefill['extras_inc_tax'] }}">
                                            <div class="smc-extras-wrap"></div>
                                        @endif

                                    </td>
                                    <td class="product-description" style=" display:none ">
                                        <textarea class="form-control form-control-solid" rows="1"> @lang('general::lang.completed')</textarea>
                                    </td>
                                    <td style="white-space: nowrap;"><input type="number" step="any"
                                            class="form-control qty-field" name="products[{{ $index }}][qty]"
                                            @if ($line->line_status == 'partial') value="{{ $line->remaining_qty }}"
@else
                                            value="{{ $line->qyt }}" @endif
                                            placeholder="0" min="1" style="width: 80px;display: inline-block;">
                                        {{-- {{ - $line->remaining_qty}} --}}

                                        <select id="unit-{{ $index }}"
                                            class="form-select form-select-solid select-2 d-inline-block unit"
                                            name="products[{{ $index }}][unit]" {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            style="width: 110px; display: inline-block;">
                                            <option value="">@lang('sales::lang.unit')</option>
                                            @if (!empty($line->product) && $line->product->unitTransfers->count() > 0)
                                                @foreach ($line->product->unitTransfers as $unit)
                                                    <option value="{{ $unit->id }}" @selected($unit->id == optional($line->unitTransfer)->id)>
                                                        {{ $unit->unit1 }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </td>

                                    <td><input type="number" step="any"
                                            class="form-control unit_price-field no-spin"
                                            name="products[{{ $index }}][unit_price]" placeholder="0.0"
                                            value="{{ $line->unit_price }}" {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            style="width: 100px;-moz-appearance: textfield !important">
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <input type="number" step="any" {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-control discount-field no-spin d-inline-block discount"
                                            name="products[{{ $index }}][discount]"
                                            placeholder="0.0" value="{{ $line->discount_amount }}"
                                            style="width: 70px; display: inline-block;">

                                        <select id="discount_type-{{ $index }}" required {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-select form-select-solid select-2 d-inline-block discount_type"
                                            name="products[{{ $index }}][discount_type]"
                                            style="width: 100px; display: inline-block;">
                                            <option value="fixed" @selected(($line->discount_type ?? 'fixed') === 'fixed')>@get_format_currency()</option>
                                            <option value="percent" @selected(($line->discount_type ?? '') === 'percent')>%</option>
                                        </select>
                                    </td>

                                    <td><input type="number" step="any" readonly {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-control total_before_vat-field"
                                            name="products[{{ $index }}][total_before_vat]" placeholder="0.00"
                                            style="width: 107px;"></td>


                                    <td class="d-flex justify-content-center">
                                        <div class="form-check">
                                            <input type="checkbox" style="border: 1px solid #9f9f9f;" id="inclusive-{{ $index }}"
                                                {{-- @if ($line->line_status == 'completed') disabled @endif --}} name="products[{{ $index }}][inclusive]"
                                                class="form-check-input  my-2">
                                        </div>

                                    </td>
                                    <td>
                                        <select id="tax_vat-{{ $index }}" required {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-select form-select-solid select-2 tax-select"
                                            name="products[{{ $index }}][tax_vat]" style="width: 200px;"
                                            {{-- data-is-tax-group="{{ $tax->is_tax_group }}"
                                        data-sub-taxes="{{ json_encode($tax->sub_taxes ?? []) }}"
                                        data-minimum-limits="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}" --}}>
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
                                            {{-- @if ($line->line_status == 'completed') disabled @endif --}} value="{{ $tax->is_tax_group ?? 0 }}"
                                            class="is-tax-group">
                                        <input type="hidden" name="products[{{ $index }}][sub_taxes]"
                                            {{-- @if ($line->line_status == 'completed') disabled @endif --}} value="{{ json_encode($tax->sub_taxes ?? []) }}"
                                            class="sub-taxes">
                                        <input type="hidden" name="products[{{ $index }}][minimum_limits]"
                                            {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            value="{{ json_encode($tax->sub_taxes->pluck('minimum_limit')->toArray() ?? []) }}"
                                            class="minimum-limits">
                                    </td>
                                    <td><input type="number" step="any" readonly {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-control vat_value-field"
                                            name="products[{{ $index }}][vat_value]" placeholder="0.00"
                                            style="width: 80px;"></td>
                                    <td><input type="number" step="any" readonly {{-- @if ($line->line_status == 'completed') disabled @endif --}}
                                            class="form-control total_after_vat-field"
                                            name="products[{{ $index }}][total_after_vat]" placeholder="0.00"
                                            style="width: 107px;"></td>
                                    <td>
                                        <button type="button" class="btn btn-icon btn-danger delete-sales-row">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                    @endif

                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="12" class="total min-w-200px text-start">
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
        <div class="d-flex align-items-center mb-3 mx-10" id="div-coupon" style="display: none;">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 100px;">@lang('sales::lang.coupon_code')</label>
            <input class="form-control form-control-solid no-spin" style="width: 260px;" name="coupon_code"
                value="" placeholder="@lang('sales::lang.coupon_code')">
        </div>
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


        <div class="mx-10 mb-5 d-none" id="invoice-service-fees-wrap">
            <input type="hidden" name="service_fees_ready" id="input-service_fees_ready" value="0">
            <input type="hidden" name="service_fee_amount" id="input-service_fee_amount" value="0">
            <input type="hidden" name="service_fee_tax" id="input-service_fee_tax" value="0">
            <label class="fs-6 fw-semibold mb-2 d-block">@lang('sales::lang.service_fees')</label>
            <div id="invoice-service-fees" class="d-flex flex-column gap-2"></div>
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


                    <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3 d-none" id="service-fee-summary-card">

                        <div class="d-flex align-items-center">
                            <div class="fs-2 fw-bold counted" id="totalServiceFees">0.00</div>
                            <span class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>
                        </div>

                        <div class="fw-semibold fs-4 text-gray-900">@lang('sales::lang.service_fees')</div>

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
