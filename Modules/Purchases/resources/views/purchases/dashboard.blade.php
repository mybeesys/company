@extends('layouts.app')
@section('title', __('menuItemLang.purchase-dashbord'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        .p-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62,57,107,.08); }
        .p-kpi { border-radius: 14px; padding: 18px; border: 1px solid #edf1f6; background: #fff; height: 100%; }
        .p-kpi-title { color: #7e8299; font-size: 12px; margin-bottom: 8px; }
        .p-kpi-value { font-size: 24px; font-weight: 700; line-height: 1.2; color: #181c32; }
        .p-soft-blue { background: #f0f7ff; border-color: #d9e9fb; }
        .p-soft-purple { background: #f8f5ff; border-color: #e8dcff; }
        .p-soft-green { background: #f1faf6; border-color: #d7f3e8; }
        .p-soft-orange { background: #fff8f5; border-color: #ffe1d7; }
        .p-filter { background: #f8f9fc; border: 1px solid #eceff5; border-radius: 12px; padding: 12px; }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
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
        <div class="card p-card mb-6">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="mb-1">{{ app()->getLocale() === 'ar' ? 'لوحة تحكم المشتريات والمدفوعات' : 'Purchases & Payments Dashboard' }}</h3>
                    <div class="text-muted">{{ app()->getLocale() === 'ar' ? 'مؤشرات تشغيلية ومالية دقيقة من المورد حتى الدفع.' : 'Accurate operational and financial indicators from supplier invoicing to payment.' }}</div>
                </div>
                <a href="{{ route('create-purchases-invoice') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>{{ app()->getLocale() === 'ar' ? 'فاتورة مشتريات جديدة' : 'New Purchase Invoice' }}</a>
            </div>
        </div>

        <div class="card p-card mb-6">
            <div class="card-body">
                <form method="GET" action="{{ route('purchase-dashbord') }}" class="p-filter d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From Date' }}</label>
                        <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To Date' }}</label>
                        <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate->toDateString() }}">
                    </div>
                    <button class="btn btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('purchase-dashbord') }}" class="btn btn-light">{{ app()->getLocale() === 'ar' ? 'إعادة ضبط' : 'Reset' }}</a>
                    <a href="{{ route('purchase-dashbord-export-csv', ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-light-primary"><i class="fas fa-file-csv"></i> CSV</a>
                    <a href="{{ route('purchase-dashbord-export-pdf', ['start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-light-danger"><i class="fas fa-file-pdf"></i> PDF</a>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="p-kpi p-soft-blue"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المشتريات للفترة' : 'Period Purchases' }}</div><div class="p-kpi-value">{{ number_format($periodPurchases, 2) }} @get_format_currency()</div><small class="{{ $purchasesGrowth >= 0 ? 'text-danger' : 'text-success' }}">{{ $purchasesGrowth >= 0 ? '+' : '' }}{{ $purchasesGrowth }}%</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi p-soft-purple"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد الفواتير' : 'Invoices Count' }}</div><div class="p-kpi-value">{{ $periodInvoices }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi p-soft-green"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'متوسط الفاتورة' : 'Average Invoice' }}</div><div class="p-kpi-value">{{ number_format($avgInvoice, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi p-soft-orange"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'الموردون النشطون' : 'Active Suppliers' }}</div><div class="p-kpi-value">{{ $activeSuppliers }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المتبقي للموردين' : 'Total Due to Suppliers' }}</div><div class="p-kpi-value text-warning">{{ number_format($dueAmount, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'المبالغ المتأخرة' : 'Overdue Amount' }}</div><div class="p-kpi-value text-danger">{{ number_format($overdueAmount, 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المدفوع' : 'Total Paid' }}</div><div class="p-kpi-value text-success">{{ number_format((float)($paymentsStats->total_paid ?? 0), 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد سندات الصرف' : 'Payment Vouchers' }}</div><div class="p-kpi-value">{{ (int)($paymentsStats->total_payments ?? 0) }}</div></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'عدد مردود المشتريات' : 'Purchase Returns Count' }}</div><div class="p-kpi-value">{{ (int)($purchaseReturnStats->total_count ?? 0) }}</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'قيمة مردود المشتريات' : 'Purchase Returns Amount' }}</div><div class="p-kpi-value text-danger">{{ number_format((float)($purchaseReturnStats->total_amount ?? 0), 2) }} @get_format_currency()</div></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'طلبات الشراء (العدد/القيمة)' : 'Purchase Orders (Count/Amount)' }}</div><div class="p-kpi-value">{{ (int)($purchaseOrderStats->total_count ?? 0) }}</div><small class="text-muted">{{ number_format((float)($purchaseOrderStats->total_amount ?? 0), 2) }} @get_format_currency()</small></div></div>
            <div class="col-md-6 col-xl-3"><div class="p-kpi"><div class="p-kpi-title">{{ app()->getLocale() === 'ar' ? 'المفضلة (العدد/القيمة)' : 'Favorites (Count/Amount)' }}</div><div class="p-kpi-value">{{ (int)($favoritesStats->total_count ?? 0) }}</div><small class="text-muted">{{ number_format((float)($favoritesStats->total_amount ?? 0), 2) }} @get_format_currency()</small></div></div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-4">
                <div class="card p-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر مردود المشتريات' : 'Recent Purchase Returns' }}</h5>
                        <a href="{{ route('purchases-return') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'المورد' : 'Supplier' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentPurchaseReturns as $item)
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>{{ $item->supplier_name ?: '--' }}</td>
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
                <div class="card p-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر طلبات الشراء' : 'Recent Purchase Orders' }}</h5>
                        <a href="{{ route('purchases-order') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'المورد' : 'Supplier' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentPurchaseOrders as $item)
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>{{ $item->supplier_name ?: '--' }}</td>
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
                <div class="card p-card h-100">
                    <div class="card-header border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر العناصر المفضلة' : 'Recent Favorite Items' }}</h5>
                        <a href="{{ route('purchases-favorites') }}" class="btn btn-sm btn-light-primary">{{ app()->getLocale() === 'ar' ? 'عرض الكل' : 'View All' }}</a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'النوع' : 'Type' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'حالة الدفع' : 'Payment' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($recentFavoritePurchases as $item)
                                    @php($paymentBadge = $paymentStatusBadge($item->payment_status ?? null))
                                    @php($approvalBadge = $approvalStatusBadge($item->status ?? null))
                                    <tr>
                                        <td>{{ $item->ref_no }}</td>
                                        <td>
                                            @if($item->type === 'purchases')
                                                {{ app()->getLocale() === 'ar' ? 'مشتريات' : 'Purchases' }}
                                            @elseif($item->type === 'purchases-return')
                                                {{ app()->getLocale() === 'ar' ? 'مردود مشتريات' : 'Purchase Return' }}
                                            @else
                                                {{ app()->getLocale() === 'ar' ? 'طلب شراء' : 'Purchase Order' }}
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
                <div class="card p-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'اتجاه المشتريات والمدفوعات الشهري' : 'Monthly Purchases vs Payments Trend' }}</h5></div>
                    <div class="card-body"><div id="purchaseTrendChart" style="height: 320px;"></div></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card p-card h-100">
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
                <div class="card p-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'أفضل 10 أصناف شراءً' : 'Top 10 Purchased Products' }}</h5></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'الصنف' : 'Product' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Qty' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
                                <tbody>
                                @forelse($topProducts as $p)
                                    <tr>
                                        <td>{{ $p->name_ar ?: $p->name_en ?: '--' }}</td>
                                        <td class="text-center">{{ number_format((float)$p->total_qty,2) }}</td>
                                        <td class="text-center">{{ number_format((float)$p->total_amount,2) }} @get_format_currency()</td>
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
                <div class="card p-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر العمليات' : 'Recent Transactions' }}</h5></div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'المورد' : 'Supplier' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المدفوع' : 'Paid' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المتبقي' : 'Remaining' }}</th></tr></thead>
                                <tbody>
                                @forelse($transactions as $t)
                                    <tr>
                                        <td>{{ $t->ref_no }}</td>
                                        <td>{{ $t->supplier_name ?: '--' }}</td>
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

        <div class="card p-card">
            <div class="card-header border-0"><h5 class="card-title mb-0">{{ app()->getLocale() === 'ar' ? 'آخر سندات الصرف' : 'Recent Supplier Payments' }}</h5></div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle">
                        <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'رقم السند' : 'Ref' }}</th><th>{{ app()->getLocale() === 'ar' ? 'المورد' : 'Supplier' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الفاتورة' : 'Invoice' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'المبلغ' : 'Amount' }}</th><th class="text-center">{{ app()->getLocale() === 'ar' ? 'التاريخ' : 'Date' }}</th></tr></thead>
                        <tbody>
                        @forelse($recentPayments as $r)
                            <tr>
                                <td>{{ $r->payment_ref_no }}</td>
                                <td>{{ $r->client->name ?? '--' }}</td>
                                <td>{{ $r->transaction->ref_no ?? '--' }}</td>
                                <td class="text-center">{{ number_format((float)$r->amount,2) }} @get_format_currency()</td>
                                <td class="text-center">{{ $r->paid_on }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">{{ app()->getLocale() === 'ar' ? 'لا توجد سندات' : 'No payments' }}</td></tr>
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
        const purchaseTrend = new ApexCharts(document.querySelector('#purchaseTrendChart'), {
            series: [
                { name: "{{ app()->getLocale() === 'ar' ? 'المشتريات' : 'Purchases' }}", data: @json($purchaseData) },
                { name: "{{ app()->getLocale() === 'ar' ? 'المدفوعات' : 'Payments' }}", data: @json($paymentData) }
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
        purchaseTrend.render();
    </script>
@endsection
