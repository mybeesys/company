@extends('layouts.app')

@section('title', __('menuItemLang.product-inventory'))

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css" />
<style>
    body { background: #f5f7fa; }
    .report-header { margin-bottom: 18px; }
    .product-title { font-size: 20px; font-weight: 700; }
    .product-sub { color: #6c757d; font-size: 13px; }
    .stat-card { border-radius: 8px; background: #fff; padding: 14px; box-shadow: 0 2px 6px rgba(0,0,0,.03); }
    .stat-label { font-size: 12px; color: #6c757d; }
    .stat-value { font-size: 18px; font-weight: 700; margin-top: 6px; }
    .stat-value .unit { font-size: 12px; font-weight: 400; color:#6c757d; margin-left:6px; }
    .big-green { background: rgba(16,185,129,0.08); border-left:4px solid rgba(16,185,129,0.3); }
    .controls-row .form-select, .controls-row .form-control { min-height: 46px; }
    .nav-buttons { display:flex; gap:8px; }
    .nav-buttons .btn { min-width:110px; }
    table.dataTable tbody td { vertical-align: middle; }
    .text-muted-small { font-size: 13px; color:#6c757d; }
</style>
@stop

@section('content')
<div class="container-fluid">

    <!-- Controls: Branch + Product -->
    <div class="row align-items-center report-header">
        <div class="col-md-4">
            <label class="form-label">@lang('report::fields.branch')</label>
            <select id="branchFilter" class="form-select form-select-solid"></select>
        </div>

        <div class="col-md-4">
            <label class="form-label">@lang('report::fields.product')</label>
            <div class="d-flex">
                <select id="productFilter" class="form-select form-select-solid flex-grow-1"></select>
            </div>
            <div id="productCode" class="product-sub mt-1"></div>
        </div>
    </div>

    <!-- Product header -->
    <div class="row mb-12">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <div>
               <label class="fs-5 fw-semibold mb-2" id="productTitle">
                   {{$currentProduct->SKU}} | {{$currentProduct->name_ar}} - {{$currentProduct->name_en}}
               </label>
            </div>

            <div class="d-flex gap-3">
                <div class="text-end">
                    <div class="text-muted-small">@lang('report::fields.quantity_on_inventory')</div>
                    <div id="currentStock" class="fs-4 fw-bolder">{{$currentStock}}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted-small">@lang('report::fields.total_in')</div>
                    <div id="totalIn" class="fs-4 fw-bolder">{{$totalIn}}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted-small">@lang('report::fields.total_out')</div>
                    <div id="totalOut" class="fs-4 fw-bolder">{{$totalOut}}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.purchased_quantity')</div>
                <div id="purchasedQuantity" class="stat-value text-primary">{{$purchases}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.sales_quantity')</div>
                <div id="salesQuantity" class="stat-value text-danger">{{$sales}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.waste')</div>
                <div id="wasteQuantity" class="stat-value text-warning">{{$damaged}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.purchase_returns')</div>
                <div id="purchaseReturns" class="stat-value text-info">{{$purchaseReturn}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.salesReturn')</div>
                <div id="salesReturn" class="stat-value text-info">{{$salesReturn}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.transferred_quantity') (IN)</div>
                <div id="transferredIn" class="stat-value text-success">{{$transferIn}} <span class="unit"> </span></div>
            </div>
        </div>

        <div class="col-lg-2 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-label">@lang('report::fields.transferred_quantity') (OUT)</div>
                <div id="transferredOut" class="stat-value text-success">{{$transferOut}} <span class="unit"> </span></div>
            </div>
        </div>
    </div>

    <!-- Quantity on inventory -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="stat-card big-green d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted-small">@lang('report::fields.quantity_on_inventory')</div>
                    <div id="quantityOnInventory" class="fs-3 fw-bold">{{$currentStock}}  </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements table -->
    <div class="card card-flush">
        <div class="card-body">
            <table id="movementsTable" class="table table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th>@lang('report::fields.type')</th>
                        <th>@lang('report::fields.change_qty')</th>
                        <th>@lang('report::fields.new_qty')</th>
                        <th>@lang('report::fields.date')</th>
                        <th>@lang('report::fields.ref_no')</th>
                        <th>@lang('report::fields.entity')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td>{{ $m['type'] }}</td>
                            <td class="{{ $m['change_qty'] < 0 ? 'text-danger' : 'text-success' }}">{{ $m['change_qty'] }}</td>
                            <td>{{ $m['new_qty'] }}</td>
                            <td>{{ $m['transaction_date'] }}</td>
                            <td>{{ $m['ref_no'] }}</td>
                            <td>{{ $m['entity'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">@lang('report::fields.no_movements') ?? 'لا توجد حركات'</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@section('script')
@parent
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

<script>
(function() {
    const reportUrlBase = "{{ route('Product-Stock-Report') }}";
    let currentProductId = {{ $product_id ?? 'null' }};
    let currentEstablishmentId = {{ $establishment_id ?? 'null' }};

    function fetchReport(productId = null) {
        const params = new URLSearchParams();
        if (productId) params.append('product_id', productId);
        if (currentEstablishmentId) params.append('branch_id', currentEstablishmentId);

        const url = reportUrlBase + (params.toString() ? '?' + params.toString() : '');

        $.get(url, function(res) {
            if(res.success) {
                $('#currentStock').text(res.currentStock);
                $('#totalIn').text(res.totalIn);
                $('#totalOut').text(res.totalOut);
                $('#purchasedQuantity').text(res.purchases);
                $('#salesQuantity').text(res.sales);
                $('#wasteQuantity').text(res.damaged);
                $('#purchaseReturns').text(res.purchaseReturn);
                $('#salesReturn').text(res.salesReturn);
                $('#transferredQuantity').text(res.transferIn + ' / ' + res.transferOut);


                  $('#productTitle').text(`${res.currentProduct.SKU} | ${res.currentProduct.name_ar} - ${res.currentProduct.name_en}`);

                let tbody = '';
                if(res.movements && res.movements.length) {
                    res.movements.forEach(m => {
                        tbody += `<tr>
                            <td>${m.type}</td>
                            <td class="${m.change_qty < 0 ? 'text-danger' : 'text-success'}">${m.change_qty}</td>
                            <td>${m.new_qty}</td>
                            <td>${m.transaction_date}</td>
                            <td>${m.ref_no}</td>
                            <td>${m.entity}</td>
                        </tr>`;
                    });
                } else {
                    tbody = `<tr><td colspan="6" class="text-center text-muted">لا توجد حركات</td></tr>`;
                }
                $('#movementsTable tbody').html(tbody);
            }
        }).fail(() => {
            alert('خطأ أثناء جلب التقرير.');
        });
    }

    $(document).ready(function() {

        function populateBranches() {
            $.get("{{ route('branches') }}", function(resp) {
                if(resp.success) {
                    const sel = $('#branchFilter');
                    sel.empty().append(new Option('كل الفروع', '', false, false));
                    resp.data.forEach(b => sel.append(new Option(b.name, b.id, false, false)));
                    sel.val(currentEstablishmentId).trigger('change');
                    sel.on('change', function() {
                        currentEstablishmentId = $(this).val() || null;
                        fetchReport(currentProductId);
                    });
                }
            });
        }

        function populateProducts() {
            $.get("{{ route('retrieveProducts') }}", function(resp) {
                if(resp.success) {
                    const sel = $('#productFilter');
                    sel.empty();
                    resp.data.forEach(p => sel.append(new Option(p.name, p.id, false, false)));
                    sel.val(currentProductId).trigger('change');
                    sel.on('change', function() {
                        currentProductId = $(this).val() || null;
                        fetchReport(currentProductId);
                    });
                }
            });
        }

        $('.form-select').select2({ width: '100%' });

        populateBranches();
        populateProducts();

        fetchReport(currentProductId);
    });
})();
</script>

@endsection
