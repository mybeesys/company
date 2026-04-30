@extends('layouts.app')

@section('title', __('accounting::lang.accounting_dashboard'))
@section('css')
<style>
    .fin-kpi { border: 1px solid #eef0f4; border-radius: 10px; padding: 12px 14px; background:#fcfcfd; min-height: 90px; }
    .kpi-help { cursor: help; color:#7a8599; font-size: 12px; }
</style>
@stop
@section('content')
@php
    $months = [];
    $debit_trend = [];
    $credit_trend = [];
    for ($i = 1; $i <= 12; $i++) {
        $months[] = __(date('M', mktime(0, 0, 0, $i, 1)));
        $monthData = $monthlyData->firstWhere('month', $i);
        $debit_trend[] = (float) ($monthData->debit ?? 0);
        $credit_trend[] = (float) ($monthData->credit ?? 0);
    }
    $accountTypeLabels = [];
    $accountTypeValues = [];
    $accountTypeColors = [];
    foreach ($account_types as $k => $v) {
        $bal = 0;
        foreach ($tree_of_account_overview as $overview) {
            if ($overview->account_primary_type == $k && !empty($overview->balance)) {
                $bal = (float) $overview->balance;
            }
        }
        $accountTypeLabels[] = $v['label'];
        $accountTypeValues[] = abs($bal);
        $accountTypeColors[] = $v['color'] ?? '#6c757d';
    }
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="mb-0">@lang('accounting::lang.accounting_dashboard')</h2>
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1">@lang('accounting::lang.from_date')</label>
                <input type="date" class="form-control" name="start_date" value="{{ $start_date }}">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">@lang('accounting::lang.to_date')</label>
                <input type="date" class="form-control" name="end_date" value="{{ $end_date }}">
            </div>
            <div class="col-auto" style="min-width: 260px;">
                <label class="form-label mb-1">@lang('accounting::lang.cost_center')</label>
                <select class="form-select" name="choose_cost_center_select[]" id="choose_cost_center_select" multiple>
                    @foreach(\Modules\Accounting\Models\AccountingCostCenter::where('is_main',0)->get() as $cc)
                        <option value="{{ $cc->id }}" @if(in_array($cc->id, $choose_cost_center_select ?? [])) selected @endif>
                            {{ app()->getLocale() == 'ar' ? $cc->name_ar : $cc->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">@lang('report::general.filter')</button>
            </div>
        </form>
    </div>

    @if(($unbalanced_journal_entries ?? 0) > 0)
        <div class="alert alert-warning">
            {{ $unbalanced_journal_entries }} @lang('accounting::lang.journal_entry') غير متوازن ضمن الفترة الحالية.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي كل الحركات المدينة ضمن الفترة المحددة."><div class="text-muted">@lang('accounting::lang.total_debit')</div><div class="fw-bold fs-4">@format_currency($totals->total_debit ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي كل الحركات الدائنة ضمن الفترة المحددة."><div class="text-muted">@lang('accounting::lang.total_credit')</div><div class="fw-bold fs-4">@format_currency($totals->total_credit ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="صافي الرصيد الدفتري للحركات بعد تطبيق الفلاتر."><div class="text-muted">@lang('accounting::lang.total_balance')</div><div class="fw-bold fs-4">@format_currency($total_balance ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="صافي الحركة = الدائن - المدين؛ يعطي اتجاه السيولة الدفترية."><div class="text-muted">Net Movement</div><div class="fw-bold fs-4">@format_currency(($totals->total_credit ?? 0)-($totals->total_debit ?? 0))</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي الأصول حسب دليل الحسابات خلال الفترة."><div class="text-muted">@lang('accounting::lang.assets')</div><div class="fw-bold fs-5">@format_currency($assets_total ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي الخصوم ضمن الفترة المحددة."><div class="text-muted">@lang('accounting::lang.liabilities')</div><div class="fw-bold fs-5">@format_currency($liabilities_total ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي حقوق الملكية المحسوبة من القيود الحالية."><div class="text-muted">@lang('accounting::lang.equity')</div><div class="fw-bold fs-5">@format_currency($equity_total ?? 0)</div></div></div>
        <div class="col-md-3"><div class="fin-kpi" data-bs-toggle="tooltip" title="فرق معادلة الميزانية: يجب أن يكون قريباً من الصفر."><div class="text-muted">A=L+E Diff</div><div class="fw-bold fs-5">@format_currency(abs(($assets_total ?? 0) - (($liabilities_total ?? 0)+($equity_total ?? 0))))</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي الذمم المدينة المستحقة حسب تقرير الأعمار."><div class="text-muted">Receivables Due</div><div class="fw-bold fs-5">@format_currency($receivables_due ?? 0)</div></div></div>
        <div class="col-md-4"><div class="fin-kpi" data-bs-toggle="tooltip" title="إجمالي الذمم الدائنة المستحقة حسب تقرير الأعمار."><div class="text-muted">Payables Due</div><div class="fw-bold fs-5">@format_currency($payables_due ?? 0)</div></div></div>
        <div class="col-md-4"><div class="fin-kpi" data-bs-toggle="tooltip" title="عدد القيود التي لا يتساوى فيها المدين مع الدائن."><div class="text-muted">Unbalanced Journals</div><div class="fw-bold fs-5">{{ $unbalanced_journal_entries ?? 0 }}</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card"><div class="card-header"><h5 class="mb-0">@lang('accounting::lang.transactions_trend')</h5></div><div class="card-body"><div id="transactionsTrendChart" style="height:320px;"></div></div></div>
        </div>
        <div class="col-lg-5">
            <div class="card"><div class="card-header"><h5 class="mb-0">@lang('accounting::lang.chart_of_accounts')</h5></div><div class="card-body"><div id="transactionsTypeChart" style="height:320px;"></div></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">@lang('accounting::lang.recent_transactions')</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>@lang('accounting::lang.ref_no')</th><th>@lang('accounting::lang.account')</th><th>@lang('accounting::lang.type')</th><th>@lang('accounting::lang.amount')</th><th>@lang('accounting::lang.date')</th><th>@lang('accounting::lang.cost_center')</th></tr></thead>
                    <tbody>
                        @forelse($recent_transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->refNo ?? $transaction->RefNo ?? '--' }}</td>
                                <td>{{ $transaction->gl_code }} - {{ app()->getLocale()=='ar' ? $transaction->account_name : $transaction->account_name_en }}</td>
                                <td><span class="badge badge-light-{{ $transaction->type == 'debit' ? 'danger' : 'success' }}">{{ __("accounting::lang.{$transaction->type}") }}</span></td>
                                <td>@format_currency($transaction->amount)</td>
                                <td>{{ \Carbon\Carbon::parse($transaction->operation_date)->format('Y-m-d H:i') }}</td>
                                <td>{{ app()->getLocale()=='ar' ? ($transaction->cost_center_name ?? 'N/A') : ($transaction->cost_center_name_en ?? 'N/A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">@lang('messages.no_data_found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>
<script>
    $(document).ready(function() {
        $('#choose_cost_center_select').select2();
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
    function fmtn(v){ return Number(v || 0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }
    new ApexCharts(document.querySelector('#transactionsTrendChart'), {
        series: [{ name: '@lang('accounting::lang.debit')', data: @json($debit_trend) }, { name: '@lang('accounting::lang.credit')', data: @json($credit_trend) }],
        chart: { type: 'line', height: 320, toolbar: { show: false } },
        colors: ['#F44336', '#4CAF50'],
        stroke: { width: 3, curve: 'smooth' },
        xaxis: { categories: @json($months) },
        yaxis: { labels: { formatter: function(v){ return fmtn(v); } } },
        tooltip: { y: { formatter: function(v){ return fmtn(v); } } }
    }).render();

    new ApexCharts(document.querySelector('#transactionsTypeChart'), {
        series: @json($accountTypeValues),
        labels: @json($accountTypeLabels),
        chart: { type: 'donut', height: 320 },
        colors: @json($accountTypeColors),
        legend: { position: 'bottom' },
        dataLabels: { formatter: function(v){ return v.toFixed(1) + '%'; } },
        tooltip: { y: { formatter: function(v){ return fmtn(v); } } }
    }).render();
</script>
@stop
