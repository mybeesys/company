<div id="dashboard-hub-overview">
    <div class="card dash-card mb-6">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                <div class="flex-grow-1" style="max-width: 920px;">
                    <h3 class="mb-2">@lang('employee::main.dashboard')</h3>
                    <div class="text-muted fs-6 lh-lg">@lang('employee::main.dashboard_page_subtitle')</div>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="filter-box d-flex flex-wrap align-items-end gap-3">
                    <input type="hidden" name="tab" value="overview">
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'من تاريخ' : 'From Date' }}</label>
                        <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $startDate->toDateString() }}">
                    </div>
                    <div>
                        <label class="form-label mb-1">{{ app()->getLocale() === 'ar' ? 'إلى تاريخ' : 'To Date' }}</label>
                        <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $endDate->toDateString() }}">
                    </div>
                    <button class="btn btn-primary">{{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('dashboard', ['tab' => 'overview']) }}" class="btn btn-light">@lang('general.clear_filters')</a>
                </form>
            </div>
        </div>
    </div>

    @if (($linkedCompanies ?? collect())->isNotEmpty())
        <div class="card dash-card mb-6">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h4 class="mb-1">@lang('employee::my_companies.title')</h4>
                    <p class="text-muted mb-0">@lang('employee::my_companies.dashboard_hint', ['count' => $linkedCompanies->count()])</p>
                </div>
                <a href="{{ route('my-companies.index') }}" class="btn btn-light-primary">
                    <i class="ki-outline ki-abstract-26 fs-3 me-2"></i>
                    @lang('employee::my_companies.menu')
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-6">
        @if ($canSales ?? false)
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
        @endif
        @if ($canPurchases ?? false)
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
        @endif
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
            <div class="kpi-card">
                <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'الاستهلاك الداخلي للفترة' : 'Period Internal Consumption' }}</div>
                <div class="kpi-value">{{ number_format($periodInternalConsumption ?? 0, 2) }} @get_format_currency()</div>
                <div class="kpi-meta text-muted">
                    {{ app()->getLocale() === 'ar' ? 'من الكاشير (تكلفة)' : 'From cashier (at cost)' }}
                </div>
            </div>
        </div>
        @if (($canSales ?? false) || ($canPurchases ?? false))
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
        @endif
    </div>

    @if (($canSales ?? false) || ($canPurchases ?? false))
        <div class="row g-4 mb-6">
            @if ($canSales ?? false)
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'ذمم العملاء المستحقة' : 'Customer Receivables Due' }}</div>
                        <div class="kpi-value">{{ number_format($total_due, 2) }} @get_format_currency()</div>
                        <div class="kpi-meta text-danger">{{ $total_unpaid_invoices }} {{ app()->getLocale() === 'ar' ? 'فاتورة غير مسددة' : 'unpaid invoices' }}</div>
                    </div>
                </div>
            @endif
            @if ($canPurchases ?? false)
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'ذمم الموردين المستحقة' : 'Supplier Payables Due' }}</div>
                        <div class="kpi-value">{{ number_format($total_due_supplier, 2) }} @get_format_currency()</div>
                        <div class="kpi-meta text-danger">{{ $total_unpaid_purchases_invoices }} {{ app()->getLocale() === 'ar' ? 'فاتورة غير مسددة' : 'unpaid invoices' }}</div>
                    </div>
                </div>
            @endif
            <div class="col-md-4">
                <div class="kpi-card">
                    <div class="kpi-title">{{ app()->getLocale() === 'ar' ? 'إجراءات سريعة' : 'Quick Actions' }}</div>
                    <div class="row g-2">
                        @if ($canSales ?? false)
                            <div class="col-6"><a href="create-invoice" class="quick-action-btn"><i class="fas fa-file-invoice text-primary"></i><span>@lang('employee::main.new_sales_invoice')</span></a></div>
                            <div class="col-6"><a href="client-create" class="quick-action-btn"><i class="fas fa-user-plus text-info"></i><span>@lang('employee::main.new_client')</span></a></div>
                        @endif
                        @if ($canPurchases ?? false)
                            <div class="col-6"><a href="create-purchases-invoice" class="quick-action-btn"><i class="fas fa-file-invoice text-success"></i><span>@lang('employee::main.new_purchase_invoice')</span></a></div>
                            <div class="col-6"><a href="supplier-create" class="quick-action-btn"><i class="fas fa-truck text-warning"></i><span>@lang('employee::main.new_supplier')</span></a></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (($canSales ?? false) || ($canPurchases ?? false))
        <div class="row g-4 mb-6">
            @if ($canSales ?? false)
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
            @endif
            @if ($canPurchases ?? false)
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
            @endif
        </div>
    @endif

    @if ($canSales ?? false)
        <div class="card dash-card">
            <div class="card-header border-0"><h5 class="card-title mb-0">@lang('employee::main.Sales vs Expenses - Last 6 Months')</h5></div>
            <div class="card-body"><div id="sales-expenses-chart" style="height: 340px;"></div></div>
        </div>
    @endif
</div>
