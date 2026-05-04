@extends('layouts.app')
@section('title', __('menuItemLang.dashboard'))

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css" rel="stylesheet" type="text/css">
    <style>
        .dash-card { border: 0; border-radius: 14px; box-shadow: 0 6px 22px rgba(62, 57, 107, 0.08); }
        .kpi-card { border-radius: 14px; border: 1px solid #eef1f7; background: #fff; padding: 18px; height: 100%; }
        .kpi-soft-sales { background: #f0f7ff; border-color: #d9e9fb; }
        .kpi-soft-purchases { background: #f8f5ff; border-color: #e8dcff; }
        .kpi-soft-expenses { background: #fff8f5; border-color: #ffe1d7; }
        .kpi-soft-net { background: #f1faf6; border-color: #d7f3e8; }
        .kpi-title { font-size: 12px; color: #7e8299; margin-bottom: 8px; }
        .kpi-value { font-size: 22px; font-weight: 700; color: #181c32; line-height: 1.2; }
        .kpi-meta { margin-top: 7px; font-size: 12px; }
        .quick-action-btn {
            border-radius: 12px; border: 1px solid #edf0f6; background: #fff; text-decoration: none;
            padding: 14px 10px; text-align: center; min-height: 106px; display: flex; align-items: center;
            justify-content: center; flex-direction: column; gap: 8px; color: #181c32 !important; transition: all .2s ease;
        }
        .quick-action-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(26, 39, 89, 0.10); }
        .filter-box { background: #f8f9fc; border: 1px solid #eceff5; border-radius: 12px; padding: 12px; }
        .table-wrap { max-height: 380px; overflow-y: auto; }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4">
        <div class="card dash-card mb-6">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div class="flex-grow-1" style="max-width: 920px;">
                        <h3 class="mb-2">@lang('employee::main.dashboard')</h3>
                        <div class="text-muted fs-6 lh-lg">@lang('employee::main.dashboard_page_subtitle')</div>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="filter-box d-flex flex-wrap align-items-end gap-3">
                        <div>
                            <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From Date' }}</label>
                            <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate->toDateString() }}">
                        </div>
                        <div>
                            <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To Date' }}</label>
                            <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate->toDateString() }}">
                        </div>
                        <button class="btn btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-light">{{ app()->getLocale() === 'ar' ? 'إعادة ضبط' : 'Reset' }}</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-6 col-xl-3">
                <div class="kpi-card kpi-soft-sales">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المبيعات للفترة' : 'Period Sales' }}</div>
                    <div class="kpi-value">{{ number_format($periodSales, 2) }} @get_format_currency()</div>
                    <div class="kpi-meta {{ $periodSalesChange >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $periodSalesChange >= 0 ? '+' : '' }}{{ $periodSalesChange }}%
                        {{ app()->getLocale() === 'ar' ? 'مقارنة بالفترة السابقة' : 'vs previous period' }}
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="kpi-card kpi-soft-purchases">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'إجمالي المشتريات للفترة' : 'Period Purchases' }}</div>
                    <div class="kpi-value">{{ number_format($periodPurchases, 2) }} @get_format_currency()</div>
                    <div class="kpi-meta {{ $periodPurchasesChange <= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $periodPurchasesChange >= 0 ? '+' : '' }}{{ $periodPurchasesChange }}%
                        {{ app()->getLocale() === 'ar' ? 'مقارنة بالفترة السابقة' : 'vs previous period' }}
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="kpi-card kpi-soft-expenses">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'المصروفات للفترة' : 'Period Expenses' }}</div>
                    <div class="kpi-value">{{ number_format($periodExpenses, 2) }} @get_format_currency()</div>
                    <div class="kpi-meta {{ $periodExpensesChange <= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $periodExpensesChange >= 0 ? '+' : '' }}{{ $periodExpensesChange }}%
                        {{ app()->getLocale() === 'ar' ? 'مقارنة بالفترة السابقة' : 'vs previous period' }}
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="kpi-card kpi-soft-net">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'صافي الأداء للفترة' : 'Period Net Performance' }}</div>
                    <div class="kpi-value {{ $periodNet >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($periodNet, 2) }} @get_format_currency()
                    </div>
                    <div class="kpi-meta text-muted">
                        {{ app()->getLocale() === 'ar' ? 'المدة:' : 'Range:' }} {{ $periodDays }} {{ app()->getLocale() === 'ar' ? 'يوم' : 'days' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'ذمم العملاء المستحقة' : 'Customer Receivables Due' }}</div>
                    <div class="kpi-value">{{ number_format($total_due, 2) }} @get_format_currency()</div>
                    <div class="kpi-meta text-danger">{{ $total_unpaid_invoices }} {{ app()->getLocale() === 'ar' ? 'فاتورة غير مسددة' : 'unpaid invoices' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'ذمم الموردين المستحقة' : 'Supplier Payables Due' }}</div>
                    <div class="kpi-value">{{ number_format($total_due_supplier, 2) }} @get_format_currency()</div>
                    <div class="kpi-meta text-danger">{{ $total_unpaid_purchases_invoices }} {{ app()->getLocale() === 'ar' ? 'فاتورة غير مسددة' : 'unpaid invoices' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'إجراءات سريعة' : 'Quick Actions' }}</div>
                    <div class="row g-2">
                        <div class="col-6"><a href="create-invoice" class="quick-action-btn"><i class="fas fa-file-invoice text-primary"></i><span>@lang('employee::main.new_sales_invoice')</span></a></div>
                        <div class="col-6"><a href="create-purchases-invoice" class="quick-action-btn"><i class="fas fa-file-invoice text-success"></i><span>@lang('employee::main.new_purchase_invoice')</span></a></div>
                        <div class="col-6"><a href="client-create" class="quick-action-btn"><i class="fas fa-user-plus text-info"></i><span>@lang('employee::main.new_client')</span></a></div>
                        <div class="col-6"><a href="supplier-create" class="quick-action-btn"><i class="fas fa-truck text-warning"></i><span>@lang('employee::main.new_supplier')</span></a></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-6">
            <div class="col-lg-6">
                <div class="card dash-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">@lang('employee::main.customers_balances_list')</h5></div>
                    <div class="card-body pt-0 table-wrap">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>@lang('employee::main.customer_name')</th><th>@lang('employee::main.phone')</th><th class="text-end">@lang('employee::main.balance')</th></tr></thead>
                                <tbody>
                                    @forelse ($customersBalances as $customer)
                                        <tr><td>{{ $customer->name }}</td><td>{{ $customer->phone_number ?? '--' }}</td><td class="text-end fw-bold">{{ number_format($customer->balance, 2) }} @get_format_currency()</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data found' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card dash-card">
                    <div class="card-header border-0"><h5 class="card-title mb-0">@lang('employee::main.supplier_balances_list')</h5></div>
                    <div class="card-body pt-0 table-wrap">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle">
                                <thead><tr><th>@lang('employee::main.customer_name')</th><th>@lang('employee::main.phone')</th><th class="text-end">@lang('employee::main.balance')</th></tr></thead>
                                <tbody>
                                    @forelse ($supplierBalances as $supplier)
                                        <tr><td>{{ $supplier->name }}</td><td>{{ $supplier->phone_number ?? '--' }}</td><td class="text-end fw-bold">{{ number_format($supplier->balance, 2) }} @get_format_currency()</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted">{{ app()->getLocale() === 'ar' ? 'لا توجد بيانات' : 'No data found' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dash-card">
            <div class="card-header border-0"><h5 class="card-title mb-0">@lang('employee::main.Sales vs Expenses - Last 6 Months')</h5></div>
            <div class="card-body"><div id="sales-expenses-chart" style="height: 340px;"></div></div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
    <script>
        var lang = '{{ app()->getLocale() }}';
        var monthLabels = lang === 'ar' ? @json($monthLabelsAr) : @json($monthLabelsEn);
        function fmtn(v){ return Number(v || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

        var salesExpensesChart = new ApexCharts(document.querySelector("#sales-expenses-chart"), {
            series: [
                { name: lang === 'ar' ? 'المبيعات' : 'Sales', data: @json($salesArray) },
                { name: lang === 'ar' ? 'المصروفات' : 'Expenses', data: @json($expensesArray) }
            ],
            chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Tajawal, sans-serif' },
            colors: ['#3699FF', '#F1416C'],
            plotOptions: { bar: { horizontal: false, columnWidth: '48%', borderRadius: 6 } },
            dataLabels: { enabled: false },
            stroke: { show: false },
            xaxis: { categories: monthLabels, labels: { style: { fontSize: '12px' } } },
            yaxis: {
                title: { text: lang === 'ar' ? 'المبلغ (ريال)' : 'Amount (SAR)', offsetX: lang === 'ar' ? -30 : 0 },
                labels: { formatter: function (val){ return fmtn(val); } }
            },
            tooltip: { y: { formatter: function (val){ return fmtn(val) + ' {{ app()->getLocale() === "ar" ? "ريال" : "SAR" }}'; } } },
            legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' }
        });
        salesExpensesChart.render();
    </script>
@endsection
