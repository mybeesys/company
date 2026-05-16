@extends('layouts.app')
@section('title', __('menuItemLang.accounting_reports'))

@section('css')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
@stop

@section('content')
    <section class="content-header py-3 ">

        <h2 class="mb-4">@lang('menuItemLang.accounting_reports')</h2>

        <section class="d-flex flex-wrap">
            @php
                $allReports = [
                    [
                        'name' => 'trial-balance',
                        'icon' => 'fas fa-scale-balanced',
                        'translation' => 'menuItemLang.trial-balance',
                        'url' => 'trial-balance',
                        'permission' => 'accountingReports.Trial balance.show',
                    ],
                    [
                        'name' => 'income-statement',
                        'icon' => 'fas fa-chart-line',
                        'translation' => 'menuItemLang.income-statement',
                        'url' => 'income-statement',
                        'permission' => 'accountingReports.Income statement.show',
                    ],
                    [
                        'name' => 'ledger',
                        'icon' => 'fas fa-book',
                        'translation' => 'menuItemLang.ledger',
                        'url' => 'ledger',
                        'permission' => 'accountingReports.Journal ledger.show',
                    ],
                    [
                        'name' => 'balance_sheet',
                        'icon' => 'fas fa-balance-scale-right',
                        'translation' => 'menuItemLang.balance_sheet',
                        'url' => 'balance-sheet',
                        'permission' => 'accountingReports.Balance sheet.show',
                    ],
                    [
                        'name' => 'journal-report',
                        'icon' => 'fas fa-file-invoice',
                        'translation' => 'menuItemLang.journal-report',
                        'url' => 'journal-report',
                        'permission' => 'accountingReports.Journal ledger.show',
                    ],
                    [
                        'name' => 'expense-report',
                        'icon' => 'fas fa-receipt',
                        'translation' => 'menuItemLang.expense-report',
                        'url' => 'expense-report',
                        'permission' => 'accounting.Payment vouchers.show',
                    ],
                    [
                        'name' => 'cash-flow',
                        'icon' => 'fas fa-money-bill-trend-up',
                        'translation' => 'menuItemLang.cash-flow',
                        'url' => 'cash-flow',
                        'permission' => 'accountingReports.Cash flow.show',
                    ],
                    [
                        'name' => 'customers-suppliers-statement',
                        'icon' => 'fas fa-users-between-lines',
                        'translation' => 'menuItemLang.customers-suppliers-statement',
                        'url' => 'customers-suppliers-statement',
                        'permission' => 'accountingReports.Customers suppliers statement.show',
                    ],
                    [
                        'name' => 'account-receivable-ageing-report',
                        'icon' => 'fas fa-user-clock',
                        'translation' => 'menuItemLang.account-receivable-ageing-report',
                        'url' => 'account-receivable-ageing-report',
                        'permission' => 'accountingReports.Receivables aging.show',
                    ],
                    [
                        'name' => 'account-receivable-ageing-details',
                        'icon' => 'fas fa-user-tag',
                        'translation' => 'menuItemLang.account-receivable-ageing-details',
                        'url' => 'account-receivable-ageing-details',
                        'permission' => 'accountingReports.Payables aging.show',
                    ],
                    [
                        'name' => 'account-payable-ageing-report',
                        'icon' => 'fas fa-business-time',
                        'translation' => 'menuItemLang.account-payable-ageing-report',
                        'url' => 'account-payable-ageing-report',
                        'permission' => 'accountingReports.Payables age report.show',
                    ],
                    [
                        'name' => 'account-payable-ageing-details',
                        'icon' => 'fas fa-file-invoice-dollar',
                        'translation' => 'menuItemLang.account-payable-ageing-details',
                        'url' => 'account-payable-ageing-details',
                        'permission' => 'accountingReports.Payables age report.show',
                    ],
                ];

                $userId = auth()->id();
                $reportsWithLastUsed = [];

                foreach ($allReports as $report) {
                    if (auth()->user()->hasDashboardPermission($report['permission'])) {
                        $lastAction = DB::table('actions')
                            ->where('user_id', $userId)
                            ->where('type', 'report')
                            ->where('action', 'view')
                            ->where('name', $report['name'])
                            ->orderBy('created_at', 'desc')
                            ->first();

                        $report['last_used_at'] = $lastAction ? $lastAction->created_at : null;
                        $reportsWithLastUsed[] = $report;
                    }
                }

                usort($reportsWithLastUsed, function ($a, $b) {
                    if ($a['last_used_at'] == $b['last_used_at']) {
                        return 0;
                    }
                    if ($a['last_used_at'] == null) {
                        return 1;
                    }
                    if ($b['last_used_at'] == null) {
                        return -1;
                    }
                    return $a['last_used_at'] > $b['last_used_at'] ? -1 : 1;
                });
            @endphp

            @foreach ($reportsWithLastUsed as $item)
                <a href="{{ url($item['url']) }}"
                    class="border border-gray-300 border-dashed rounded min-w-250px flex-grow-1 flex-shrink-0 p-5 m-3 d-block text-decoration-none hover-elevate-up"
                    style="flex-basis: calc(25% - 24px); max-width: calc(25% - 24px);"
                    onclick="trackReportView('{{ $item['name'] }}')">
                    <div class="d-flex flex-column align-items-center text-center">
                        <div class="mb-3">
                            <i class="{{ $item['icon'] }} fs-2x text-primary"></i>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-800 text-wrap">
                            @lang($item['translation'])
                        </div>
                        @if ($item['last_used_at'])
                            <div class="text-muted fs-8 mt-2">
                                @lang('accounting::lang.Last used'): {{ \Carbon\Carbon::parse($item['last_used_at'])->diffForHumans() }}
                            </div>
                            @else
                              <div class="text-muted fs-8 mt-5">
                               </div>

                        @endif
                    </div>
                </a>
            @endforeach
        </section>
    </section>

@endsection

@section('script')
    <script>
        function trackReportView(reportName) {
            fetch('{{ route('track.action') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    type: 'report',
                    action: 'view',
                    name: reportName
                })
            });
        }
    </script>
@stop
