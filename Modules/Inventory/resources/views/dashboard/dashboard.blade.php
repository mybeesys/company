@extends('layouts.app')
@section('title', __('menuItemLang.inventory_dashboard'))

@section('css')
    <style>
        .inv-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62,57,107,.08); }
        .inv-kpi { border-radius: 14px; padding: 18px; border: 1px solid #edf1f6; height: 100%; background: #fff; }
        .inv-kpi-title { color: #7e8299; font-size: 12px; margin-bottom: 8px; }
        .inv-kpi-value { font-size: 28px; font-weight: 700; line-height: 1; color: #181c32; }
        .inv-soft-blue { background: #f0f7ff; border-color: #d9e9fb; }
        .inv-soft-purple { background: #f8f5ff; border-color: #e8dcff; }
        .inv-soft-green { background: #f1faf6; border-color: #d7f3e8; }
        .inv-soft-orange { background: #fff8f5; border-color: #ffe1d7; }
        .inv-action {
            display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px;
            min-height:95px; border:1px solid #eceff5; border-radius:12px; text-decoration:none; color:#181c32 !important;
            background:#fff; transition:all .2s ease;
        }
        .inv-action:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26,39,89,.1); }
        .inv-alert { border-radius: 12px; border: 1px solid #ffd8d8; background: #fff6f6; padding: 14px 16px; }
        .inv-filter { background: #f8f9fc; border: 1px solid #eceff5; border-radius: 12px; padding: 12px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <div class="card inv-card mb-6">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="mb-1">{{ app()->getLocale() === 'ar' ? 'لوحة تحكم المخزون' : 'Inventory Control Dashboard' }}</h3>
                    <div class="text-muted">{{ app()->getLocale() === 'ar' ? 'مؤشرات رقابية للمخزون وحركات المستودعات مع تنبيهات المخاطر.' : 'Operational inventory KPIs with warehouse movement and risk alerts.' }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('productInventory.index') }}" class="btn btn-primary">
                        <i class="fas fa-box me-1"></i> {{ app()->getLocale() === 'ar' ? 'فتح شاشة المخزون' : 'Open Inventory Screen' }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card inv-card mb-6">
            <div class="card-body">
                <form method="GET" action="{{ route('inventory.dashboard') }}" class="inv-filter d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'المستودع' : 'Warehouse' }}</label>
                        <select name="warehouse_id" class="form-select form-select-solid">
                            <option value="">{{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ (int) $selectedWarehouseId === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From Date' }}</label>
                        <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To Date' }}</label>
                        <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'نوع الحركة بالمخطط' : 'Chart Movement Type' }}</label>
                        <select name="movement_type" class="form-select form-select-solid">
                            <option value="all" {{ $movementType === 'all' ? 'selected' : '' }}>{{ app()->getLocale() === 'ar' ? 'الكل' : 'All' }}</option>
                            <option value="prep" {{ $movementType === 'prep' ? 'selected' : '' }}>PREP</option>
                            <option value="transfer" {{ $movementType === 'transfer' ? 'selected' : '' }}>TRANSFER</option>
                            <option value="waste" {{ $movementType === 'waste' ? 'selected' : '' }}>WASTE</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('inventory.dashboard') }}" class="btn btn-light">@lang('general.clear_filters')</a>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="inv-kpi inv-soft-blue"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد المستودعات' : 'Warehouses Count' }}</div><div class="inv-kpi-value">{{ $warehousesCount }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="inv-kpi inv-soft-purple"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي كمية المخزون' : 'Total Stock Quantity' }}</div><div class="inv-kpi-value">{{ number_format($totalStockQuantity, 2) }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="inv-kpi inv-soft-green"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'تحويلات معتمدة' : 'Approved Transfers' }}</div><div class="inv-kpi-value">{{ $transferCount }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="inv-kpi inv-soft-orange"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'عمليات تجهيز معتمدة' : 'Approved Prep Ops' }}</div><div class="inv-kpi-value">{{ $prepCount }}</div></div></div>
        </div>

        <div class="card inv-card mb-6">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'اتجاه الحركة الشهرية (Inbound / Outbound)' : 'Monthly Movement Trend (Inbound / Outbound)' }}</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('inventory.dashboard.export.movement-csv', ['warehouse_id' => $selectedWarehouseId, 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'movement_type' => $movementType]) }}" class="btn btn-sm btn-light-primary">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="{{ route('inventory.dashboard.export.movement-pdf', ['warehouse_id' => $selectedWarehouseId, 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString(), 'movement_type' => $movementType]) }}" class="btn btn-sm btn-export-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="inventory-movement-chart" style="height: 320px;"></div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-4"><div class="inv-kpi"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'عمليات إتلاف معتمدة' : 'Approved Waste Ops' }}</div><div class="inv-kpi-value fs-2">{{ $wasteCount }}</div></div></div>
            <div class="col-md-4"><div class="inv-kpi"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'منتجات برصيد سالب' : 'Negative Stock Items' }}</div><div class="inv-kpi-value fs-2 text-danger">{{ $negativeStockItemsCount }}</div></div></div>
            <div class="col-md-4"><div class="inv-kpi"><div class="inv-kpi-title">{{ app()->getLocale() === 'ar' ? 'منتجات برصيد صفر' : 'Zero Stock Items' }}</div><div class="inv-kpi-value fs-2 text-warning">{{ $zeroStockItemsCount }}</div></div></div>
        </div>

        <div class="inv-alert mb-6 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-bold">{{ app()->getLocale() === 'ar' ? 'تنبيه مخزون منخفض' : 'Low Stock Alert' }}</div>
                <div class="text-muted">
                    {{ app()->getLocale() === 'ar'
                        ? "يوجد {$lowStockCount} عنصر أقل من حد التنبيه (Threshold)."
                        : "{$lowStockCount} items are currently below threshold level." }}
                </div>
            </div>
            <span class="badge {{ $lowStockCount > 0 ? 'badge-light-danger text-danger' : 'badge-light-success text-success' }}">
                {{ $lowStockCount > 0 ? (app()->getLocale() === 'ar' ? 'يتطلب إجراء' : 'Action Required') : (app()->getLocale() === 'ar' ? 'سليم' : 'Healthy') }}
            </span>
        </div>

        <div class="card inv-card mb-6">
            <div class="card-header border-0 pt-5"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'إجراءات سريعة' : 'Quick Actions' }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3"><a href="{{ route('productInventory.index') }}" class="inv-action"><i class="fas fa-box text-primary"></i><span>{{ app()->getLocale() === 'ar' ? 'عرض المخزون' : 'View Inventory' }}</span></a></div>
                    <div class="col-6 col-md-3"><a href="{{ route('waste.index') }}" class="inv-action"><i class="fas fa-dumpster text-danger"></i><span>{{ app()->getLocale() === 'ar' ? 'إدارة الإتلاف' : 'Manage Waste' }}</span></a></div>
                    <div class="col-6 col-md-3"><a href="{{ route('transfer.index') }}" class="inv-action"><i class="fas fa-exchange-alt text-info"></i><span>{{ app()->getLocale() === 'ar' ? 'إدارة التحويلات' : 'Manage Transfers' }}</span></a></div>
                    <div class="col-6 col-md-3"><a href="{{ route('prep.index') }}" class="inv-action"><i class="fas fa-mortar-pestle text-success"></i><span>{{ app()->getLocale() === 'ar' ? 'عمليات التجهيز' : 'Prep Operations' }}</span></a></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-6">
                <div class="card inv-card h-100">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أعلى/أقل رصيد لكل مستودع' : 'Top/Bottom Stock per Warehouse' }}</h5></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                <tr>
                                    <th>{{ app()->getLocale() === 'ar' ? 'المستودع' : 'Warehouse' }}</th>
                                    <th>{{ app()->getLocale() === 'ar' ? 'أعلى رصيد' : 'Highest' }}</th>
                                    <th>{{ app()->getLocale() === 'ar' ? 'أقل رصيد' : 'Lowest' }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($warehouses as $warehouse)
                                    <tr>
                                        <td class="fw-bold">{{ $warehouse->name }}</td>
                                        <td>
                                            @if($warehouse->mostStockedProductName)
                                                <div>{{ $warehouse->mostStockedProductName }}</div>
                                                <small class="text-success">{{ number_format($warehouse->mostStockedQuantity, 2) }}</small>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($warehouse->leastStockedProductName)
                                                <div>{{ $warehouse->leastStockedProductName }}</div>
                                                <small class="{{ $warehouse->leastStockedQuantity < 0 ? 'text-danger' : 'text-warning' }}">{{ number_format($warehouse->leastStockedQuantity, 2) }}</small>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد مستودعات' : 'No warehouses found' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card inv-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أهم العناصر الحرجة (رصيد <= 0)' : 'Top Critical Items (Qty <= 0)' }}</h5>
                        <a href="{{ route('inventory.dashboard.export.critical-items-csv', ['warehouse_id' => $selectedWarehouseId, 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-sm btn-light-primary">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                <tr>
                                    <th>{{ app()->getLocale() === 'ar' ? 'الصنف' : 'Item' }}</th>
                                    <th>{{ app()->getLocale() === 'ar' ? 'المستودع' : 'Warehouse' }}</th>
                                    <th class="text-center">{{ app()->getLocale() === 'ar' ? 'الرصيد' : 'Qty' }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($topCriticalItems as $item)
                                    <tr>
                                        <td>{{ $item->name_ar ?: $item->name_en ?: '--' }}</td>
                                        <td>{{ $item->warehouse_name }}</td>
                                        <td class="text-center {{ $item->qty < 0 ? 'text-danger fw-bold' : 'text-warning fw-bold' }}">{{ number_format((float) $item->qty, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد عناصر حرجة حاليًا' : 'No critical items currently' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const selectedMovementType = @json($movementType);
        const monthLabels = @json($monthLabels);
        const monthlyInbound = @json($monthlyInbound);
        const monthlyOutbound = @json($monthlyOutbound);
        const monthlyPrep = @json($monthlyPrep);
        const monthlyTransfer = @json($monthlyTransfer);
        const monthlyWaste = @json($monthlyWaste);

        let chartSeries = [];
        let chartColors = [];
        if (selectedMovementType === 'prep') {
            chartSeries = [{ name: "PREP", data: monthlyPrep }];
            chartColors = ['#50CD89'];
        } else if (selectedMovementType === 'transfer') {
            chartSeries = [{ name: "TRANSFER", data: monthlyTransfer }];
            chartColors = ['#009EF7'];
        } else if (selectedMovementType === 'waste') {
            chartSeries = [{ name: "WASTE", data: monthlyWaste }];
            chartColors = ['#F1416C'];
        } else {
            chartSeries = [
                { name: "{{ app()->getLocale() === 'ar' ? 'Inbound (PREP)' : 'Inbound (PREP)' }}", data: monthlyInbound },
                { name: "{{ app()->getLocale() === 'ar' ? 'Outbound (TRANSFER + WASTE)' : 'Outbound (TRANSFER + WASTE)' }}", data: monthlyOutbound }
            ];
            chartColors = ['#50CD89', '#F1416C'];
        }

        const movementChart = new ApexCharts(document.querySelector('#inventory-movement-chart'), {
            series: chartSeries,
            chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'Tajawal, sans-serif' },
            stroke: { curve: 'smooth', width: [3, 3] },
            markers: { size: 4 },
            xaxis: { categories: monthLabels },
            yaxis: { labels: { formatter: function(v){ return Math.round(v); } } },
            tooltip: { y: { formatter: function(v){ return Math.round(v); } } },
            colors: chartColors,
            legend: { position: 'top', horizontalAlign: 'right' }
        });
        movementChart.render();
    </script>
@endsection
