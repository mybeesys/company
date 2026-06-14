@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')
@section('title', __('menuItemLang.sales-dashbord'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        .s-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62,57,107,.08); }
        .s-kpi { border-radius: 14px; padding: 18px; border: 1px solid #edf1f6; background: #fff; height: 100%; }
        .s-kpi-title { color: #7e8299; font-size: 12px; margin-bottom: 8px; }
        .s-kpi-value { font-size: 24px; font-weight: 700; line-height: 1.2; color: #181c32; }
        .s-soft-blue { background: #f0f7ff; border-color: #d9e9fb; }
        .s-soft-purple { background: #f8f5ff; border-color: #e8dcff; }
        .s-soft-green { background: #f1faf6; border-color: #d7f3e8; }
        .s-soft-orange { background: #fff8f5; border-color: #ffe1d7; }
        .s-filter { background: #f8f9fc; border: 1px solid #eceff5; border-radius: 12px; padding: 12px; }
    </style>
    @include('employee::dashboard.partials.tabs-styles')
@endsection

@section('content')
    <div class="container-fluid py-3">
        @include('employee::dashboard.partials.tabs-nav')
        @php
            $translatePaymentMethod = function ($method) {
                $method = (string) $method;
                if (app()->getLocale() !== 'ar') return $method ?: '--';
                $map = ['cash' => 'نقدي', 'card' => 'بطاقة', 'bank_transfer' => 'تحويل بنكي', 'bank' => 'بنك', 'cheque' => 'شيك', 'check' => 'شيك', 'credit' => 'آجل', 'due' => 'آجل', 'wallet' => 'محفظة'];
                return $map[strtolower(trim($method))] ?? ($method ?: '--');
            };
            $paymentStatusBadge = function ($status) {
                $key = strtolower(trim((string) $status));
                $isAr = app()->getLocale() === 'ar';
                if ($key === 'paid') return ['class' => 'badge badge-light-success', 'label' => $isAr ? 'مدفوع' : 'Paid'];
                if (in_array($key, ['partial', 'partial_paid', 'partially_paid'], true)) return ['class' => 'badge badge-light-warning', 'label' => $isAr ? 'جزئي' : 'Partial'];
                return ['class' => 'badge badge-light-danger', 'label' => $isAr ? 'غير مدفوع' : 'Unpaid'];
            };
            $approvalStatusBadge = function ($status) {
                $key = strtolower(trim((string) $status));
                $isAr = app()->getLocale() === 'ar';
                if (in_array($key, ['approved', 'final'], true)) return ['class' => 'badge badge-light-success', 'label' => $isAr ? 'معتمد' : 'Approved'];
                return ['class' => 'badge badge-light-secondary', 'label' => $isAr ? 'مسودة' : 'Draft'];
            };
        @endphp
        <div class="card s-card mb-6">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="mb-1">{{ app()->getLocale() === 'ar' ? 'لوحة تحكم المبيعات والتحصيل والكوبونات' : 'Sales, Collections & Coupons Dashboard' }}</h3>
                    <div class="text-muted">{{ app()->getLocale() === 'ar' ? 'مؤشرات تنفيذية دقيقة من العميل حتى التحصيل والكوبون.' : 'Executive sales indicators from customer invoicing through collections and coupon impact.' }}</div>
                </div>
                <a href="{{ route('create-invoice') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>{{ app()->getLocale() === 'ar' ? 'فاتورة مبيعات جديدة' : 'New Sales Invoice' }}</a>
            </div>
        </div>

        <div class="card s-card mb-6">
            <div class="card-body">
                <form method="GET" action="{{ route('sales-dashbord') }}" class="s-filter d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From Date' }}</label>
                        <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To Date' }}</label>
                        <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate->toDateString() }}">
                    </div>
                    <button class="btn btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('sales-dashbord') }}" class="btn btn-light">@lang('general.clear_filters')</a>
                    <a href="{{ route('sales-dashbord-export-csv', ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-light-primary">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <a href="{{ route('sales-dashbord-export-pdf', ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-export-pdf">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="s-kpi s-soft-blue"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المبيعات للفترة' : 'Period Sales' }}</div><div class="s-kpi-value">{{ number_format($periodSales, 2) }} @get_format_currency()</div><small class="{{ $salesGrowth >= 0 ? 'text-success' : 'text-danger' }}">{{ $salesGrowth >= 0 ? '+' : '' }}{{ $salesGrowth }}%</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi s-soft-purple"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد الفواتير' : 'Invoices Count' }}</div><div class="s-kpi-value">{{ $periodInvoices }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi s-soft-green"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'متوسط الفاتورة' : 'Average Invoice' }}</div><div class="s-kpi-value">{{ number_format($avgInvoice, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi s-soft-orange"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'العملاء النشطون' : 'Active Customers' }}</div><div class="s-kpi-value">{{ $activeCustomers }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المتبقي' : 'Total Due' }}</div><div class="s-kpi-value text-warning">{{ number_format($dueAmount, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'المبالغ المتأخرة' : 'Overdue Amount' }}</div><div class="s-kpi-value text-danger">{{ number_format($overdueAmount, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي التحصيل' : 'Total Collected' }}</div><div class="s-kpi-value text-success">{{ number_format((float)($receiptsStats->total_collected ?? 0), 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد سندات التحصيل' : 'Receipt Vouchers' }}</div><div class="s-kpi-value">{{ (int)($receiptsStats->total_receipts ?? 0) }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-4"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'الكوبونات المستخدمة' : 'Coupon Usages' }}</div><div class="s-kpi-value">{{ (int)($couponStats->coupon_usages ?? 0) }}</div></div></div>
            <div class="col-md-4"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'الكوبونات النشطة بالفترة' : 'Active Coupons in Period' }}</div><div class="s-kpi-value">{{ (int)($couponStats->active_coupons ?? 0) }}</div></div></div>
            <div class="col-md-4"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي خصومات الكوبونات' : 'Coupon Discount Total' }}</div><div class="s-kpi-value">{{ number_format((float)($couponStats->total_coupon_discount ?? 0), 2) }} @get_format_currency()</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد مردود المبيعات' : 'Sales Returns Count' }}</div><div class="s-kpi-value">{{ (int)($salesReturnStats->total_count ?? 0) }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'قيمة مردود المبيعات' : 'Sales Returns Amount' }}</div><div class="s-kpi-value text-danger">{{ number_format((float)($salesReturnStats->total_amount ?? 0), 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'عروض الأسعار (العدد/القيمة)' : 'Quotations (Count/Amount)' }}</div><div class="s-kpi-value">{{ (int)($quotationStats->total_count ?? 0) }}</div><small class="text-muted">{{ number_format((float)($quotationStats->total_amount ?? 0), 2) }} @get_format_currency()</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="s-kpi"><div class="s-kpi-title">{{ app()->getLocale() === 'ar' ? 'المفضلة (العدد/القيمة)' : 'Favorites (Count/Amount)' }}</div><div class="s-kpi-value">{{ (int)($favoritesStats->total_count ?? 0) }}</div><small class="text-muted">{{ number_format((float)($favoritesStats->total_amount ?? 0), 2) }} @get_format_currency()</small></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-4">
                <div class="card s-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر مردود المبيعات' : 'Recent Sales Returns' }}</h5>
                        <a href="{{ route('sell-return') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'العميل' : 'Client' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentSalesReturns as $item)
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>{{ $item->client_name ?: '--' }}</td>
                                        <td class="text-center"><span class="{{ $approvalBadge['class'] }}">{{ $approvalBadge['label'] }}</span></td>
                                        <td class="text-center text-danger">{{ number_format((float)$item->final_total,2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card s-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر عروض الأسعار' : 'Recent Quotations' }}</h5>
                        <a href="{{ route('quotations') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'العميل' : 'Client' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentQuotations as $item)
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>{{ $item->client_name ?: '--' }}</td>
                                        <td class="text-center"><span class="{{ $approvalBadge['class'] }}">{{ $approvalBadge['label'] }}</span></td>
                                        <td class="text-center">{{ number_format((float)$item->final_total,2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card s-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر العناصر المفضلة' : 'Recent Favorite Items' }}</h5>
                        <a href="{{ route('sales-favorites') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'النوع' : 'Type' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'حالة الدفع' : 'Payment' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentFavoriteSales as $item)
                                    @php($paymentBadge = $paymentStatusBadge($item->payment_status ?? null))
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>
                                            @if($item->type === 'sell')
                                                {{ app()->getLocale() === 'ar' ? 'مبيعات' : 'Sales' }}
                                            @elseif($item->type === 'sell-return')
                                                {{ app()->getLocale() === 'ar' ? 'مردود مبيعات' : 'Sales Return' }}
                                            @else
                                                {{ app()->getLocale() === 'ar' ? 'عرض سعر' : 'Quotation' }}
                                            @endif
                                        </td>
                                        <td class="text-center"><span class="{{ $paymentBadge['class'] }}">{{ $paymentBadge['label'] }}</span></td>
                                        <td class="text-center"><span class="{{ $approvalBadge['class'] }}">{{ $approvalBadge['label'] }}</span></td>
                                        <td class="text-center">{{ number_format((float)$item->final_total,2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-8">
                <div class="card s-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'اتجاه المبيعات والتحصيل الشهري' : 'Monthly Sales vs Collections Trend' }}</h5></div>
                    <div class="card-body"><div id="salesTrendChart" style="height: 320px;"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card s-card h-100">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'طرق الدفع' : 'Payment Methods' }}</h5></div>
                    <div class="card-body">
                        @forelse($paymentMethods as $m)
                            <div class="d-flex justify-content-between mb-3"><span>{{ $translatePaymentMethod($m->method) }}</span><strong>{{ number_format((float)$m->total,2) }}</strong></div>
                        @empty
                            <div class="text-muted">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data' }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-6">
                <div class="card s-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أفضل 10 أصناف مبيعاً' : 'Top 10 Sold Products' }}</h5></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'الصنف' : 'Product' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Qty' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales' }}</th></tr></thead>
                                <tbody>
                                @forelse($topProducts as $p)
                                    <tr>
                                        <td>{{ $p->name_ar ?: $p->name_en ?: '--' }}</td>
                                        <td class="text-center">{{ number_format((float)$p->total_qty,2) }}</td>
                                        <td class="text-center">{{ number_format((float)$p->total_sales,2) }} @get_format_currency()</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card s-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر العمليات' : 'Recent Transactions' }}</h5></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'العميل' : 'Client' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المدفوع' : 'Paid' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المتبقي' : 'Remaining' }}</th></tr></thead>
                                <tbody>
                                @forelse($transactions as $t)
                                    <tr>
                                        <td>{{ $t->ref_no }}</td>
                                        <td>{{ $t->client_name ?: '--' }}</td>
                                        <td class="text-center">{{ number_format((float)$t->final_total,2) }}</td>
                                        <td class="text-center">{{ number_format((float)$t->paid_amount,2) }}</td>
                                        <td class="text-center {{ (float)$t->remaining_amount > 0 ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ number_format((float)$t->remaining_amount,2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد عمليات' : 'No transactions' }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card s-card">
            <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر سندات التحصيل' : 'Recent Receipts' }}</h5></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'رقم السند' : 'Ref' }}</th><th>{{ app()->getLocale() === 'ar' ? 'العميل' : 'Client' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الفاتورة' : 'Invoice' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المبلغ' : 'Amount' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th></tr></thead>
                        <tbody>
                        @forelse($recentReceipts as $r)
                            <tr>
                                <td>{{ $r->payment_ref_no }}</td>
                                <td>{{ $r->client->name ?? '--' }}</td>
                                <td>{{ $r->transaction->ref_no ?? '--' }}</td>
                                <td class="text-center">{{ number_format((float)$r->amount,2) }} @get_format_currency()</td>
                                <td class="text-center">{{ $r->paid_on }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد سندات' : 'No receipts' }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        const salesTrend = new ApexCharts(document.querySelector('#salesTrendChart'), {
            series: [
                { name: "{{ app()->getLocale() === 'ar' ? 'المبيعات' : 'Sales' }}", data: @json($salesData) },
                { name: "{{ app()->getLocale() === 'ar' ? 'التحصيل' : 'Collections' }}", data: @json($collectionData) }
            ],
            chart: { type: 'line', height: 320, toolbar: { show: false }, fontFamily: 'Tajawal, sans-serif' },
            stroke: { curve: 'smooth', width: [3, 3] },
            markers: { size: 4 },
            xaxis: { categories: @json($monthLabels) },
            yaxis: { labels: { formatter: function(v){ return Number(v || 0).toLocaleString(undefined, {maximumFractionDigits: 2}); } } },
            tooltip: { y: { formatter: function(v){ return Number(v || 0).toLocaleString(undefined, {maximumFractionDigits: 2}); } } },
            colors: ['#3699FF', '#50CD89'],
            legend: { position: 'top', horizontalAlign: 'right' }
        });
        salesTrend.render();
    </script>
@endsection
