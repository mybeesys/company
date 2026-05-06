@php
    $itemsByProduct = $itemsByProduct ?? collect();
@endphp

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

<div class="alert alert-info">@lang('accounting::lang.periodic_inventory_review_note')</div>

<div class="card">
    <div class="card-header pt-7 fs-4">@lang('accounting::lang.inventory_items')</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 periodic-items-table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.product')</th>
                        <th class="min-w-175px">@lang('accounting::lang.unit')</th>
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
                            $systemQtyBase = (float) ($product->qty ?? 0);
                            $cost = (float) ($product->cost ?? 0);
                            $existing = $itemsByProduct instanceof \Illuminate\Support\Collection ? $itemsByProduct->get($product->id) : null;
                            $selectedUtId = $existing?->unit_transfer_id ?? null;
                            $physicalInput = $existing?->physical_quantity_input ?? $systemQtyBase;
                        @endphp
                        <tr>
                            <td>{{ $product->SKU . ' - ' . $product->name_ar . ' - ' . $product->name_en }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                <select name="items[{{ $product->id }}][unit_transfer_id]" class="form-select unit-select">
                                    <option value="">—</option>
                                    @foreach (($product->unitTransfers ?? []) as $ut)
                                        <option
                                            value="{{ $ut->id }}"
                                            data-factor="{{ (float) ($ut->transfer ?? 0) > 0 ? (float) $ut->transfer : 1 }}"
                                            @if ($selectedUtId && (int) $selectedUtId === (int) $ut->id) selected @endif
                                        >
                                            {{ $ut->unit1 ?? '—' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="system-qty">
                                {{ number_format($systemQtyBase, 2) }}
                                <input type="hidden" name="items[{{ $product->id }}][system_quantity]" value="{{ $systemQtyBase }}">
                            </td>
                            <td>
                                <input
                                    type="number"
                                    step="any"
                                    name="items[{{ $product->id }}][physical_quantity]"
                                    class="form-control physical-qty"
                                    value="{{ $physicalInput }}"
                                    data-system-qty="{{ $systemQtyBase }}"
                                    data-unit-cost="{{ $cost }}"
                                    required>
                            </td>
                            <td>
                                {{ number_format($cost, 2) }}
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

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            function currentFactor($row) {
                const opt = $row.find('.unit-select option:selected');
                const f = parseFloat(opt.data('factor'));
                return (isFinite(f) && f > 0) ? f : 1;
            }

            function recalcSummary() {
                let estimatedClosingValue = 0;
                let estimatedVarianceValue = 0;
                let positive = 0;
                let negative = 0;

                $('.periodic-items-table tbody tr').each(function() {
                    const $row = $(this);
                    const $input = $row.find('.physical-qty');
                    if (!$input.length) return;

                    const systemQtyBase = parseFloat($input.data('system-qty')) || 0;
                    const physicalInput = parseFloat($input.val()) || 0;
                    const unitCost = parseFloat($input.data('unit-cost')) || 0;
                    const factor = currentFactor($row);

                    const physicalBase = physicalInput * factor;
                    const varianceBase = physicalBase - systemQtyBase;
                    const varianceValue = varianceBase * unitCost;
                    const closingValue = physicalBase * unitCost;

                    estimatedClosingValue += closingValue;
                    estimatedVarianceValue += varianceValue;
                    if (varianceValue > 0) positive += varianceValue;
                    if (varianceValue < 0) negative += Math.abs(varianceValue);

                    $row.find('.variance-cell').text(varianceBase.toFixed(2));
                    $row.find('.variance-value-cell').text(varianceValue.toFixed(2));
                });

                $('#estimated-closing-value').text(estimatedClosingValue.toFixed(2));
                $('#estimated-variance-value').text(estimatedVarianceValue.toFixed(2));
                $('#positive-variance').text(`@lang('accounting::lang.positive_variance'): ${positive.toFixed(2)}`);
                $('#negative-variance').text(`@lang('accounting::lang.negative_variance'): ${negative.toFixed(2)}`);
            }

            $(document).on('input change', '.physical-qty, .unit-select', recalcSummary);
            recalcSummary();

            function rebuildTable(products) {
                const tbody = $('.periodic-items-table tbody');
                tbody.empty();

                if (!products || products.length === 0) {
                    tbody.append('<tr><td colspan="7" class="text-center">@lang('accounting::lang.no_products')</td></tr>');
                    recalcSummary();
                    return;
                }

                products.forEach(product => {
                    const systemQtyBase = parseFloat(product.qty || 0);
                    const unitCost = parseFloat(product.cost || 0);
                    const unitTransfers = Array.isArray(product.unit_transfers) ? product.unit_transfers : (Array.isArray(product.unitTransfers) ? product.unitTransfers : []);
                    const unitOptions = unitTransfers.map(ut => {
                        const t = parseFloat(ut.transfer || 0);
                        const factor = (isFinite(t) && t > 0) ? t : 1;
                        const label = ut.unit1 || '—';
                        return `<option value="${ut.id}" data-factor="${factor}">${label}</option>`;
                    }).join('');

                    const row = `
                        <tr>
                            <td>${product.SKU} - ${product.name_ar} - ${product.name_en}</td>
                            <td>
                                <input type="hidden" name="items[${product.id}][product_id]" value="${product.id}">
                                <select name="items[${product.id}][unit_transfer_id]" class="form-select unit-select">
                                    <option value="">—</option>
                                    ${unitOptions}
                                </select>
                            </td>
                            <td class="system-qty">
                                ${systemQtyBase.toFixed(2)}
                                <input type="hidden" name="items[${product.id}][system_quantity]" value="${systemQtyBase}">
                            </td>
                            <td>
                                <input type="number" step="any" name="items[${product.id}][physical_quantity]"
                                    class="form-control physical-qty"
                                    value="${systemQtyBase}"
                                    data-system-qty="${systemQtyBase}"
                                    data-unit-cost="${unitCost}" required>
                            </td>
                            <td>
                                ${unitCost.toFixed(2)}
                                <input type="hidden" name="items[${product.id}][unit_cost]" value="${unitCost}">
                            </td>
                            <td class="variance-cell">0.00</td>
                            <td class="variance-value-cell">0.00</td>
                        </tr>
                    `;
                    tbody.append(row);
                });

                recalcSummary();
            }

            $(document).on('change', '.establishment', function() {
                const establishmentId = $(this).val();
                const tbody = $('.periodic-items-table tbody');
                tbody.html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> @lang('accounting::lang.loading')</td></tr>');

                $.ajax({
                    url: '/get-products-by-establishment/' + establishmentId,
                    type: 'GET',
                    success: function(response) {
                        rebuildTable(response);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        rebuildTable([]);
                    }
                });
            });
        });
    </script>
@endsection

