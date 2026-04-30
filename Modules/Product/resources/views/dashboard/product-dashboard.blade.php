@extends('layouts.app')
@section('title', __('menuItemLang.product_dashboard'))

@section('css')
    <style>
        .pd-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62,57,107,.08); }
        .pd-kpi { border-radius: 14px; padding: 18px; border: 1px solid #edf1f6; height: 100%; }
        .pd-kpi-title { color: #7e8299; font-size: 12px; margin-bottom: 8px; }
        .pd-kpi-value { font-size: 28px; font-weight: 700; line-height: 1; color: #181c32; }
        .pd-soft-blue { background: #f0f7ff; border-color: #d9e9fb; }
        .pd-soft-purple { background: #f8f5ff; border-color: #e8dcff; }
        .pd-soft-green { background: #f1faf6; border-color: #d7f3e8; }
        .pd-soft-orange { background: #fff8f5; border-color: #ffe1d7; }
        .pd-action {
            display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px;
            min-height: 92px; border: 1px solid #eceff5; border-radius: 12px; text-decoration: none; color: #181c32 !important;
            background: #fff; transition: all .2s ease;
        }
        .pd-action:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26,39,89,.1); }
        .pd-table-wrap { max-height: 360px; overflow-y: auto; }
        .pd-alert { border-radius: 12px; border: 1px solid #ffe5b4; background: #fffaf1; padding: 14px 16px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <div class="card pd-card mb-6">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="mb-1">{{ app()->getLocale() === 'ar' ? 'لوحة المنتجات والخدمات' : 'Products & Services Dashboard' }}</h3>
                    <div class="text-muted">{{ app()->getLocale() === 'ar' ? 'متابعة احترافية لحجم البيانات والنمو الشهري وآخر العناصر المضافة.' : 'Professional overview of entities volume, monthly growth, and recent additions.' }}</div>
                </div>
                <a href="{{ route('product.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> {{ __('product::dashboard.add_product') }}
                </a>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="pd-kpi pd-soft-blue"><div class="pd-kpi-title">@lang('product::dashboard.total_products')</div><div class="pd-kpi-value">{{ $productsCount }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="pd-kpi pd-soft-purple"><div class="pd-kpi-title">@lang('product::dashboard.total_ingredients')</div><div class="pd-kpi-value">{{ $ingredintsCount }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="pd-kpi pd-soft-green"><div class="pd-kpi-title">@lang('product::dashboard.total_custom_menus')</div><div class="pd-kpi-value">{{ $servicesCount }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="pd-kpi pd-soft-orange"><div class="pd-kpi-title">@lang('product::dashboard.total_discounts')</div><div class="pd-kpi-value">{{ $discountsCount }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-3"><div class="pd-kpi"><div class="pd-kpi-title">@lang('product::dashboard.total_modifiers')</div><div class="pd-kpi-value fs-2">{{ $modifiersCount }}</div></div></div>
            <div class="col-md-3"><div class="pd-kpi"><div class="pd-kpi-title">@lang('product::dashboard.total_variants')</div><div class="pd-kpi-value fs-2">{{ $variantsCount }}</div></div></div>
            <div class="col-md-3"><div class="pd-kpi"><div class="pd-kpi-title">@lang('product::dashboard.total_service_fees')</div><div class="pd-kpi-value fs-2">{{ $serviceFeesCount }}</div></div></div>
            <div class="col-md-3"><div class="pd-kpi"><div class="pd-kpi-title">@lang('product::dashboard.total_pricings')</div><div class="pd-kpi-value fs-2">{{ $pricingsCount }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-7">
                <div class="pd-alert d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold">{{ app()->getLocale() === 'ar' ? 'تنبيه جودة البيانات' : 'Data Quality Alert' }}</div>
                        <div class="text-muted">
                            {{ app()->getLocale() === 'ar'
                                ? "يوجد {$zeroPriceProductsCount} منتجات بدون سعر أو بسعر يساوي صفر."
                                : "{$zeroPriceProductsCount} products have missing or zero price." }}
                        </div>
                    </div>
                    <span class="badge {{ $zeroPriceProductsCount > 0 ? 'badge-light-warning text-warning' : 'badge-light-success text-success' }}">
                        {{ $zeroPriceProductsCount > 0 ? (app()->getLocale() === 'ar' ? 'يتطلب مراجعة' : 'Needs Review') : (app()->getLocale() === 'ar' ? 'سليم' : 'Healthy') }}
                    </span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="pd-kpi d-flex flex-column justify-content-center h-100">
                    <div class="pd-kpi-title">{{ app()->getLocale() === 'ar' ? 'معدل النمو الشهري للمنتجات (%)' : 'Monthly Product Growth (%)' }}</div>
                    <div class="pd-kpi-value {{ $productsGrowthPercent >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $productsGrowthPercent >= 0 ? '+' : '' }}{{ number_format($productsGrowthPercent, 2) }}%
                    </div>
                    <div class="text-muted mt-2">
                        {{ app()->getLocale() === 'ar'
                            ? "الحالي: {$currentMonthProducts} | السابق: {$previousMonthProducts}"
                            : "Current: {$currentMonthProducts} | Previous: {$previousMonthProducts}" }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-12">
                <div class="pd-alert d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color:#ffd8d8;background:#fff6f6;">
                    <div>
                        <div class="fw-bold">{{ app()->getLocale() === 'ar' ? 'تنبيه ربحية المنتجات' : 'Product Margin Alert' }}</div>
                        <div class="text-muted">
                            {{ app()->getLocale() === 'ar'
                                ? "يوجد {$negativeMarginProductsCount} منتجات تكلفتها أعلى من سعر البيع (خسارة مباشرة)."
                                : "{$negativeMarginProductsCount} products have cost higher than selling price (negative margin)." }}
                        </div>
                    </div>
                    <span class="badge {{ $negativeMarginProductsCount > 0 ? 'badge-light-danger text-danger' : 'badge-light-success text-success' }}">
                        {{ $negativeMarginProductsCount > 0 ? (app()->getLocale() === 'ar' ? 'خطر تسعير' : 'Pricing Risk') : (app()->getLocale() === 'ar' ? 'لا يوجد خطر' : 'No Risk') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card pd-card mb-6">
            <div class="card-header border-0 pt-5"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'إجراءات سريعة' : 'Quick Actions' }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('product.create') }}" class="pd-action"><i class="fas fa-box-open text-primary"></i><span>@lang('product::dashboard.add_product')</span></a></div>
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('ingredient.create') }}" class="pd-action"><i class="fas fa-seedling text-success"></i><span>@lang('product::dashboard.add_ingredient')</span></a></div>
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('modifier.create') }}" class="pd-action"><i class="fas fa-plus-circle text-warning"></i><span>@lang('product::dashboard.add_modifier')</span></a></div>
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('attribute.index') }}" class="pd-action"><i class="fas fa-copy text-info"></i><span>@lang('product::dashboard.add_variant')</span></a></div>
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('customMenu.create') }}" class="pd-action"><i class="fas fa-book-open text-primary"></i><span>@lang('product::dashboard.add_custom_menu')</span></a></div>
                    <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('priceTier.index') }}" class="pd-action"><i class="fas fa-tags text-danger"></i><span>@lang('product::dashboard.add_pricing')</span></a></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-6">
                <div class="card pd-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">@lang('product::dashboard.latest_products')</h5>
                        <a href="{{ route('product.dashboard.export.latest-products-csv') }}" class="btn btn-sm btn-light-primary">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                    </div>
                    <div class="card-body pt-0 pd-table-wrap">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead>
                                <tr>
                                    <th>@lang('product::dashboard.product_name_ar')</th>
                                    <th>@lang('product::dashboard.product_name_en')</th>
                                    <th class="text-center">@lang('product::dashboard.price')</th>
                                    <th class="text-center">@lang('product::dashboard.date_added')</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($latestProducts as $product)
                                    <tr>
                                        <td>{{ $product->name_ar ?? '--' }}</td>
                                        <td>{{ $product->name_en ?? '--' }}</td>
                                        <td class="text-center">{{ number_format((float) ($product->price_with_tax ?? 0), 2) }}</td>
                                        <td class="text-center">{{ optional($product->created_at)->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">@lang('product::dashboard.no_products')</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card pd-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أحدث القوائم المخصصة' : 'Latest Custom Menus' }}</h5>
                        <a href="{{ route('product.dashboard.export.latest-menus-csv') }}" class="btn btn-sm btn-light-primary">
                            <i class="fas fa-file-csv"></i> CSV
                        </a>
                    </div>
                    <div class="card-body pt-0 pd-table-wrap">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'الاسم' : 'Name' }}</th><th class="text-center">@lang('product::dashboard.date_added')</th></tr></thead>
                                <tbody>
                                @forelse($latestServices as $service)
                                    <tr><td>{{ $service->name_ar ?? $service->name_en ?? '--' }}</td><td class="text-center">{{ optional($service->created_at)->format('Y-m-d') }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد قوائم حديثة' : 'No recent menus' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card pd-card mb-6">
            <div class="card-header border-0">
                <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أعلى 10 منتجات مضافة (آخر 30 يوم)' : 'Top 10 Recently Added Products (Last 30 Days)' }}</h5>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('product::dashboard.product_name_ar')</th>
                            <th>@lang('product::dashboard.product_name_en')</th>
                            <th class="text-center">@lang('product::dashboard.price')</th>
                            <th class="text-center">@lang('product::dashboard.date_added')</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topProductsLastPeriod as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->name_ar ?? '--' }}</td>
                                <td>{{ $product->name_en ?? '--' }}</td>
                                <td class="text-center">{{ number_format((float) ($product->price_with_tax ?? 0), 2) }}</td>
                                <td class="text-center">{{ optional($product->created_at)->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد إضافات ضمن آخر 30 يوم' : 'No products added in last 30 days' }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($zeroPriceProductsCount > 0)
            <div class="card pd-card mb-6">
                <div class="card-header border-0">
                    <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'منتجات تحتاج تصحيح سعر' : 'Products Requiring Price Fix' }}</h5>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead><tr><th>@lang('product::dashboard.product_name_ar')</th><th>@lang('product::dashboard.product_name_en')</th><th class="text-center">@lang('product::dashboard.price')</th></tr></thead>
                            <tbody>
                            @foreach($zeroPriceProducts as $product)
                                <tr>
                                    <td>{{ $product->name_ar ?? '--' }}</td>
                                    <td>{{ $product->name_en ?? '--' }}</td>
                                    <td class="text-center text-danger fw-bold">{{ number_format((float) ($product->price_with_tax ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($negativeMarginProductsCount > 0)
            <div class="card pd-card mb-6">
                <div class="card-header border-0">
                    <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'منتجات بخسارة (التكلفة > سعر البيع)' : 'Loss-Making Products (Cost > Sell Price)' }}</h5>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle">
                            <thead>
                            <tr>
                                <th>@lang('product::dashboard.product_name_ar')</th>
                                <th>@lang('product::dashboard.product_name_en')</th>
                                <th class="text-center">{{ app()->getLocale() === 'ar' ? 'التكلفة' : 'Cost' }}</th>
                                <th class="text-center">@lang('product::dashboard.price')</th>
                                <th class="text-center">{{ app()->getLocale() === 'ar' ? 'فارق الربحية' : 'Margin Gap' }}</th>
                                <th class="text-center">{{ app()->getLocale() === 'ar' ? 'إجراء' : 'Action' }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($negativeMarginProducts as $product)
                                <tr>
                                    <td>{{ $product->name_ar ?? '--' }}</td>
                                    <td>{{ $product->name_en ?? '--' }}</td>
                                    <td class="text-center">{{ number_format((float) ($product->cost ?? 0), 2) }}</td>
                                    <td class="text-center">{{ number_format((float) ($product->price_with_tax ?? 0), 2) }}</td>
                                    <td class="text-center text-danger fw-bold">{{ number_format((float) (($product->price_with_tax ?? 0) - ($product->cost ?? 0)), 2) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('product.edit', $product->id) }}" class="btn btn-sm btn-light-primary">
                                            <i class="fas fa-pen"></i>
                                            {{ app()->getLocale() === 'ar' ? 'تعديل السعر/التكلفة' : 'Edit Price/Cost' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="card pd-card">
            <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'نمو المنتجات والقوائم (آخر 6 أشهر)' : 'Products & Menus Growth (Last 6 Months)' }}</h5></div>
            <div class="card-body"><div id="products-growth-chart" style="height: 330px;"></div></div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const growthChart = new ApexCharts(document.querySelector("#products-growth-chart"), {
            series: [
                { name: "{{ app()->getLocale() === 'ar' ? 'المنتجات' : 'Products' }}", data: @json($productsMonthlyData) },
                { name: "{{ app()->getLocale() === 'ar' ? 'القوائم المخصصة' : 'Custom Menus' }}", data: @json($servicesMonthlyData) }
            ],
            chart: { type: 'line', height: 330, toolbar: { show: false }, fontFamily: 'Tajawal, sans-serif' },
            stroke: { width: [3, 3], curve: 'smooth' },
            markers: { size: 4 },
            xaxis: { categories: @json($monthLabels) },
            yaxis: { labels: { formatter: function(v){ return Math.round(v); } } },
            tooltip: { y: { formatter: function(v){ return Math.round(v); } } },
            colors: ['#3699FF', '#50CD89'],
            legend: { position: 'top', horizontalAlign: 'right' }
        });
        growthChart.render();
    </script>
@endsection
