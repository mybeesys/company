@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="mb-1">@lang('accounting::lang.new_periodic_inventory')</h1>
                    <div class="text-muted">@lang('accounting::lang.periodic_inventory_workflow_subtitle')</div>
                </div>
                <div class="badge badge-light-info fs-7">@lang('accounting::lang.count_sheet')</div>
            </div>
        </div>

        <form method="POST" action="{{ route('periodic-inventory.store') }}">
            @csrf

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.establishment')</label>
                            <select class="form-control establishment select-2" name="establishment">
                                @foreach ($establishments as $establishment)
                                    <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.periodic_inventory_count_date')</label>
                            <input type="date" name="count_date" class="form-control" value="{{ $count_date_default ?? now()->format('Y-m-d') }}" required>
                            <div class="form-text">@lang('accounting::lang.periodic_inventory_count_date_help')</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.periodic_inventory_last_count_hint')</label>
                            <input type="text" class="form-control" value="{{ $start_date }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">@lang('accounting::lang.estimated_closing_value')</div>
                            <div class="fw-bold fs-2" id="estimated-closing-value">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">@lang('accounting::lang.estimated_variance_value')</div>
                            <div class="fw-bold fs-2" id="estimated-variance-value">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">@lang('accounting::lang.finance_impact_preview')</div>
                            <div class="d-flex gap-3 mt-2">
                                <span class="badge badge-light-success" id="positive-variance">@lang('accounting::lang.positive_variance'): 0.00</span>
                                <span class="badge badge-light-danger" id="negative-variance">@lang('accounting::lang.negative_variance'): 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning">@lang('accounting::lang.save_and_posting_note')</div>

            <div class="card">
                <div class="card-header pt-7 fs-4">@lang('accounting::lang.inventory_items')</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('accounting::lang.product')</th>
                                    <th>@lang('accounting::lang.unit')</th>
                                    <th>@lang('accounting::lang.system_quantity')</th>
                                    <th>@lang('accounting::lang.physical_quantity')</th>
                                    <th>@lang('accounting::lang.cost_price')</th>
                                    <th>@lang('accounting::lang.difference')</th>
                                    <th>@lang('accounting::lang.total_variance_value')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($products) == 0)
                                    <tr>
                                        <td colspan="7" class="text-center">@lang('accounting::lang.no_products')</td>
                                    </tr>
                                @endif
                                @foreach ($products as $product)
                                    @php
                                        $systemQty = (float) ($product->qty ?? 0);
                                        $cost = (float) ($product->cost ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $product->SKU . ' - ' . $product->name_ar . ' - ' . $product->name_en }}</td>
                                        <td class="text-muted small">{{ $product->inventory_unit_label ?? '—' }}</td>
                                        <td class="system-qty">{{ number_format($systemQty, 2) }}
                                            <input type="hidden" name="items[{{ $product->id }}][system_quantity]" value="{{ $systemQty }}">
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                            <input
                                                type="number"
                                                step="0.01"
                                                name="items[{{ $product->id }}][physical_quantity]"
                                                class="form-control physical-qty"
                                                value="{{ $systemQty }}"
                                                data-system-qty="{{ $systemQty }}"
                                                data-unit-cost="{{ $cost }}"
                                                required>
                                        </td>
                                        <td>{{ number_format($cost, 2) }}
                                            <input type="hidden" name="items[{{ $product->id }}][unit_cost]" value="{{ $cost }}">
                                        </td>
                                        <td class="variance-cell">0.00</td>
                                        <td class="variance-value-cell">0.00</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">@lang('accounting::lang.save_inventory')</button>
            </div>
        </form>
    </div>
    <script>
        $(document).ready(function() {
            function recalcSummary() {
                let estimatedClosingValue = 0;
                let estimatedVarianceValue = 0;
                let positive = 0;
                let negative = 0;

                $('tbody tr').each(function() {
                    const $row = $(this);
                    const $input = $row.find('.physical-qty');
                    if (!$input.length) {
                        return;
                    }
                    const systemQty = parseFloat($input.data('system-qty')) || 0;
                    const physicalQty = parseFloat($input.val()) || 0;
                    const unitCost = parseFloat($input.data('unit-cost')) || 0;
                    const variance = physicalQty - systemQty;
                    const varianceValue = variance * unitCost;
                    const closingValue = physicalQty * unitCost;

                    estimatedClosingValue += closingValue;
                    estimatedVarianceValue += varianceValue;
                    if (varianceValue >= 0) {
                        positive += varianceValue;
                    } else {
                        negative += Math.abs(varianceValue);
                    }

                    $row.find('.variance-cell')
                        .text(variance.toFixed(2))
                        .toggleClass('text-danger', variance < 0)
                        .toggleClass('text-success', variance > 0);

                    $row.find('.variance-value-cell')
                        .text(varianceValue.toFixed(2))
                        .toggleClass('text-danger', varianceValue < 0)
                        .toggleClass('text-success', varianceValue > 0);
                });

                $('#estimated-closing-value').text(estimatedClosingValue.toFixed(2));
                $('#estimated-variance-value')
                    .text(estimatedVarianceValue.toFixed(2))
                    .toggleClass('text-danger', estimatedVarianceValue < 0)
                    .toggleClass('text-success', estimatedVarianceValue > 0);
                $('#positive-variance').text(`@lang('accounting::lang.positive_variance'): ${positive.toFixed(2)}`);
                $('#negative-variance').text(`@lang('accounting::lang.negative_variance'): ${negative.toFixed(2)}`);
            }

            function bindPhysicalQtyEvents() {
                $('.physical-qty').off('input').on('input', recalcSummary);
            }

            bindPhysicalQtyEvents();
            recalcSummary();

            $('.establishment').change(function() {
                const establishmentId = $(this).val();

                $.ajax({
                    url: '/get-products-by-establishment/' + establishmentId,
                    type: 'GET',
                    success: function(response) {
                        updateProductsTable(response);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            function updateProductsTable(products) {
                const tbody = $('table tbody');
                tbody.empty();

                if (products.length === 0) {
                    tbody.append('<tr><td colspan="7" class="text-center">@lang('accounting::lang.no_products')</td></tr>');
                    recalcSummary();
                    return;
                }

                products.forEach(product => {
                    const systemQty = parseFloat(product.qty || 0);
                    const unitCost = parseFloat(product.cost || 0);
                    const unitLabel = product.inventory_unit_label || '—';
                    const row = `
                <tr>
                    <td>${product.SKU} - ${product.name_ar} - ${product.name_en}</td>
                    <td class="text-muted small">${unitLabel}</td>
                    <td class="system-qty">${systemQty.toFixed(2)}
                        <input type="hidden" name="items[${product.id}][system_quantity]" value="${systemQty}">
                    </td>
                    <td>
                        <input type="hidden" name="items[${product.id}][product_id]" value="${product.id}">
                        <input type="number" step="0.01" name="items[${product.id}][physical_quantity]"
                               class="form-control physical-qty"
                               value="${systemQty}"
                               data-system-qty="${systemQty}"
                               data-unit-cost="${unitCost}" required>
                    </td>
                    <td>${unitCost.toFixed(2)}
                        <input type="hidden" name="items[${product.id}][unit_cost]" value="${unitCost}">
                    </td>
                    <td class="variance-cell">0.00</td>
                    <td class="variance-value-cell">0.00</td>
                </tr>
            `;
                    tbody.append(row);
                });

                bindPhysicalQtyEvents();
                recalcSummary();
            }

            $('.establishment').change(function() {
                $('table tbody').html(
                    '<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i>@lang('accounting::lang.loading')</td></tr>'
                );
            });
        });
    </script>
@endsection
