<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ app()->getLocale() === 'ar' ? 'تقرير لوحة المشتريات' : 'Purchases Dashboard Report' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h2 { margin: 0 0 10px 0; }
        .meta { margin-bottom: 12px; font-size: 11px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 14px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        th { background: #f2f4f8; }
        .section-title { margin-top: 14px; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $translatePaymentMethod = function ($method) {
            $method = (string) $method;
            if (app()->getLocale() !== 'ar') return $method ?: '--';
            $map = ['cash' => 'نقدي', 'card' => 'بطاقة', 'bank_transfer' => 'تحويل بنكي', 'bank' => 'بنك', 'cheque' => 'شيك', 'check' => 'شيك', 'credit' => 'آجل', 'due' => 'آجل', 'wallet' => 'محفظة'];
            return $map[strtolower(trim($method))] ?? ($method ?: '--');
        };
        $paymentStatusLabel = function ($status) {
            $key = strtolower(trim((string) $status));
            if ($key === 'paid') return app()->getLocale() === 'ar' ? 'مدفوع' : 'Paid';
            if (in_array($key, ['partial', 'partial_paid', 'partially_paid'], true)) return app()->getLocale() === 'ar' ? 'جزئي' : 'Partial';
            return app()->getLocale() === 'ar' ? 'غير مدفوع' : 'Unpaid';
        };
        $approvalStatusLabel = function ($status) {
            $key = strtolower(trim((string) $status));
            if (in_array($key, ['approved', 'final'], true)) return app()->getLocale() === 'ar' ? 'معتمد' : 'Approved';
            return app()->getLocale() === 'ar' ? 'مسودة' : 'Draft';
        };
    @endphp
    <h2>{{ app()->getLocale() === 'ar' ? 'تقرير لوحة المشتريات التنفيذية' : 'Executive Purchases Dashboard Report' }}</h2>
    <div class="meta">{{ app()->getLocale() === 'ar' ? 'الفترة' : 'Period' }}: {{ $startDate->toDateString() }} - {{ $endDate->toDateString() }}</div>

    <table>
        <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'المؤشر' : 'KPI' }}</th><th>{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Value' }}</th></tr></thead>
        <tbody>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'إجمالي المشتريات' : 'Period Purchases' }}</td><td>{{ number_format((float)$periodPurchases,2) }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'نمو المشتريات %' : 'Purchases Growth %' }}</td><td>{{ number_format((float)$purchasesGrowth,2) }}%</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'عدد الفواتير' : 'Invoices Count' }}</td><td>{{ $periodInvoices }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'متوسط الفاتورة' : 'Average Invoice' }}</td><td>{{ number_format((float)$avgInvoice,2) }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'الموردون النشطون' : 'Active Suppliers' }}</td><td>{{ $activeSuppliers }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'المتبقي للموردين' : 'Total Due' }}</td><td>{{ number_format((float)$dueAmount,2) }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'المبالغ المتأخرة' : 'Overdue Amount' }}</td><td>{{ number_format((float)$overdueAmount,2) }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'إجمالي المدفوع' : 'Total Paid' }}</td><td>{{ number_format((float)($paymentsStats->total_paid ?? 0),2) }}</td></tr>
            <tr><td>{{ app()->getLocale() === 'ar' ? 'عدد سندات الصرف' : 'Payment Vouchers' }}</td><td>{{ (int)($paymentsStats->total_payments ?? 0) }}</td></tr>
        </tbody>
    </table>

    <div class="section-title">{{ app()->getLocale() === 'ar' ? 'أفضل 10 أصناف شراءً' : 'Top 10 Purchased Products' }}</div>
    <table>
        <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'الصنف' : 'Product' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الكمية' : 'Qty' }}</th><th>{{ app()->getLocale() === 'ar' ? 'القيمة' : 'Amount' }}</th></tr></thead>
        <tbody>
        @foreach($topProducts as $p)
            <tr><td>{{ $p->name_ar ?: $p->name_en ?: '--' }}</td><td>{{ number_format((float)$p->total_qty,2) }}</td><td>{{ number_format((float)$p->total_amount,2) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">{{ app()->getLocale() === 'ar' ? 'آخر العمليات' : 'Recent Transactions' }}</div>
    <table>
        <thead><tr><th>#</th><th>{{ app()->getLocale() === 'ar' ? 'المورد' : 'Supplier' }}</th><th>{{ app()->getLocale() === 'ar' ? 'حالة الدفع' : 'Payment Status' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الاعتماد' : 'Approval' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th><th>{{ app()->getLocale() === 'ar' ? 'المدفوع' : 'Paid' }}</th><th>{{ app()->getLocale() === 'ar' ? 'المتبقي' : 'Remaining' }}</th></tr></thead>
        <tbody>
        @foreach($transactions as $t)
            <tr><td>{{ $t->ref_no }}</td><td>{{ $t->supplier_name ?: '--' }}</td><td>{{ $paymentStatusLabel($t->payment_status ?? '') }}</td><td>{{ $approvalStatusLabel($t->status ?? '') }}</td><td>{{ number_format((float)$t->final_total,2) }}</td><td>{{ number_format((float)$t->paid_amount,2) }}</td><td>{{ number_format((float)$t->remaining_amount,2) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title">{{ app()->getLocale() === 'ar' ? 'طرق الدفع' : 'Payment Methods' }}</div>
    <table>
        <thead><tr><th>{{ app()->getLocale() === 'ar' ? 'الطريقة' : 'Method' }}</th><th>{{ app()->getLocale() === 'ar' ? 'الإجمالي' : 'Total' }}</th></tr></thead>
        <tbody>
        @foreach($paymentMethods as $m)
            <tr><td>{{ $translatePaymentMethod($m->method ?? '') }}</td><td>{{ number_format((float)($m->total ?? 0),2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
