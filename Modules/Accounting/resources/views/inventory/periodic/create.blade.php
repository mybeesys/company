@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">جرد دوري جديد</h1>

        <form method="POST" action="{{ route('periodic-inventory.store') }}">
            @csrf

            <div class="card mb-4">
                {{-- <div class="card-header">معلومات الجرد</div> --}}
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>تاريخ البداية</label>
                                <input type="date" class="form-control" value="{{ $start_date->format('Y-m-d') }}"
                                    readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>تاريخ النهاية</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ $end_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pt-7 fs-4" >عناصر المخزون</div>
                <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>الصنف</th>
                                <th>الكمية النظامية</th>
                                <th>الكمية الفعلية</th>
                                <th>سعر التكلفة</th>
                                <th>الفرق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->SKU . ' - ' . $product->name_ar . ' - ' . $product->name_en }}</td>
                                    <td>{{ $product->qty }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $product->id }}][product_id]"
                                            value="{{ $product->id }}">
                                        <input type="number" step="0.01"
                                            name="items[{{ $product->id }}][physical_quantity]"
                                            class="form-control physical-qty" value="{{ $product->qty_available }}"
                                            required>
                                    </td>
                                    <td>{{ number_format($product->cost, 2) }}</td>
                                    <td class="variance-cell">0</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ الجرد
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
    </script>
@endsection
