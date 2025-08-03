@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">@lang('accounting::lang.new_periodic_inventory')</h1>

        <form method="POST" action="{{ route('periodic-inventory.store') }}">
            @csrf

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>@lang('accounting::lang.establishment')</label>
                                <select class="form-control establishment select-2" name="establishment">
                                    @foreach ($establishments as $establishment)
                                        <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>@lang('accounting::lang.start_date')</label>
                                <input type="date" class="form-control" value="{{ $start_date }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>@lang('accounting::lang.end_date')</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ $end_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pt-7 fs-4">@lang('accounting::lang.inventory_items')</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('accounting::lang.product')</th>
                                <th>@lang('accounting::lang.system_quantity')</th>
                                <th>@lang('accounting::lang.physical_quantity')</th>
                                <th>@lang('accounting::lang.cost_price')</th>
                                <th>@lang('accounting::lang.difference')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (count($products)==0)
                                <tr><td colspan="5" class="text-center">@lang('accounting::lang.no_products')</td></tr>
                            @endif
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->SKU . ' - ' . $product->name_ar . ' - ' . $product->name_en }}</td>
                                    <td>{{ $product->qty }}
                                        <input type="hidden" name="items[{{ $product->id }}][system_quantity]"
                                            value="{{ $product->qty }}">

                                    </td>
                                    <td>
                                        <input type="hidden" name="items[{{ $product->id }}][product_id]"
                                            value="{{ $product->id }}">
                                        <input type="number" step="0.01"
                                            name="items[{{ $product->id }}][physical_quantity]"
                                            class="form-control physical-qty" value="{{ $product->qty_available }}"
                                            required>
                                    </td>
                                    <td>{{ number_format($product->cost, 2) }}
                                        <input type="hidden" name="items[{{ $product->id }}][unit_cost]"
                                            value="{{ $product->cost }}">

                                    </td>
                                    <td class="variance-cell">0</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    @lang('accounting::lang.save_inventory')
                </button>
            </div>
        </form>
    </div>
    <script>
        $(document).ready(function() {
            $('.physical-qty').on('input', function() {
                const row = $(this).closest('tr');
                const systemQty = parseFloat(row.find('td:eq(1)').text());
                const physicalQty = parseFloat($(this).val()) || 0;
                const variance = physicalQty - systemQty;

                row.find('.variance-cell').text(variance.toFixed(2));
                row.find('.variance-cell').toggleClass('text-danger', variance < 0);
            });
        });


        $(document).ready(function() {
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
                    tbody.append(
                        '<tr><td colspan="5" class="text-center">@lang('accounting::lang.no_products')</td></tr>');
                    return;
                }

                products.forEach(product => {
                    const row = `
                <tr>
                    <td>${product.SKU} - ${product.name_ar} - ${product.name_en}</td>
                    <td>${product.qty}
                        <input type="hidden" name="items[${product.id}][system_quantity]" value="${product.qty}">

                        </td>
                    <td>
                        <input type="hidden" name="items[${product.id}][product_id]" value="${product.id}">
                        <input type="number" step="0.01" name="items[${product.id}][physical_quantity]"
                               class="form-control physical-qty" value="${product.qty_available}" required>
                    </td>


                    <td>${parseFloat(product.cost).toFixed(2)}
                        <input type="hidden" name="items[${product.id}][unit_cost]" value="${product.cost}">

                        </td>
                    <td class="variance-cell">0</td>
                </tr>
            `;
                    tbody.append(row);
                });

                bindPhysicalQtyEvents();
            }

            function bindPhysicalQtyEvents() {
                $('.physical-qty').off('input').on('input', function() {
                    const row = $(this).closest('tr');
                    const systemQty = parseFloat(row.find('td:eq(1)').text());
                    const physicalQty = parseFloat($(this).val()) || 0;
                    const variance = physicalQty - systemQty;

                    row.find('.variance-cell').text(variance.toFixed(2));
                    row.find('.variance-cell').toggleClass('text-danger', variance < 0);
                });
            }

            $('.establishment').change(function() {
                $('table tbody').html(
                    '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i>@lang('accounting::lang.loading') </td></tr>'
                );
            });
        });
    </script>
@endsection
