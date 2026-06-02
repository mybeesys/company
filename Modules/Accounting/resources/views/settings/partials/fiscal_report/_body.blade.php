@php
    $summary = $report['summary'] ?? [];
    $breakdown = $report['breakdown'] ?? [];
    $monthly = $report['monthly'] ?? [];
    $linkedPeriods = $report['linked_periods'] ?? collect();
    $recent = $report['recent_entries'] ?? collect();
    $range = $report['range'] ?? [];
    $year = $report['year'];
    $focusPeriod = $report['focus_period'] ?? null;
    $maxBreakdown = max(1, (int) max($breakdown ?: [1]));
    $maxMonthly = max(1, (int) collect($monthly)->max('count') ?: 1);
    $locale = app()->getLocale();
@endphp

<div class="fy-report">
    <div class="fy-report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="fs-3 fw-bold text-gray-900 mb-1">{{ $title }}</h1>
                <p class="text-muted mb-0">
                    {{ $range['start'] ?? '' }} — {{ $range['end'] ?? '' }}
                    @if ($focusPeriod)
                        · {{ $focusPeriod->name }}
                    @else
                        · {{ $year->name }}
                    @endif
                </p>
            </div>
            <div class="text-muted fs-7">
                @lang('accounting::financial_year.report_generated_at'): {{ now()->format('Y-m-d H:i') }}
            </div>
        </div>
    </div>

    <div class="fy-kpi-row">
        <div class="fy-kpi">
            <div class="fy-kpi-label">@lang('accounting::financial_year.report_journal_count')</div>
            <div class="fy-kpi-value">{{ number_format($summary['journal_count'] ?? 0) }}</div>
        </div>
        <div class="fy-kpi">
            <div class="fy-kpi-label">@lang('accounting::financial_year.report_total_debit')</div>
            <div class="fy-kpi-value">{{ number_format($summary['total_debit'] ?? 0, 2) }}</div>
        </div>
        <div class="fy-kpi">
            <div class="fy-kpi-label">@lang('accounting::financial_year.report_total_credit')</div>
            <div class="fy-kpi-value">{{ number_format($summary['total_credit'] ?? 0, 2) }}</div>
        </div>
        <div class="fy-kpi">
            <div class="fy-kpi-label">@lang('accounting::financial_year.report_operations_count')</div>
            <div class="fy-kpi-value">{{ number_format($summary['operations_count'] ?? 0) }}</div>
        </div>
        @if (! $focusPeriod)
            <div class="fy-kpi">
                <div class="fy-kpi-label">@lang('accounting::financial_year.report_periods_count')</div>
                <div class="fy-kpi-value">{{ number_format($summary['periods_count'] ?? 0) }}</div>
            </div>
        @endif
        <div class="fy-kpi">
            <div class="fy-kpi-label">@lang('accounting::financial_year.status')</div>
            <div class="fy-kpi-value fs-6">
                @if ($focusPeriod)
                    @include('accounting::settings.partials.period_status_badge', ['period' => $focusPeriod])
                @else
                    @php
                        $yearStatus = $summary['status'] ?? 'open';
                    @endphp
                    <span class="badge fy-period-badge fy-period-badge-{{ $yearStatus === 'open' ? 'open' : 'closed' }} fw-semibold px-3 py-2">
                        @lang('accounting::financial_year.status_'.$yearStatus)
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="fy-section-card h-100">
                <div class="fy-section-head">@lang('accounting::financial_year.report_transaction_breakdown')</div>
                <div class="fy-section-body">
                    @foreach (\Modules\Accounting\Services\FiscalPeriod\FinancialYearAnalyticsService::BREAKDOWN_KEYS as $key)
                        @if ($key === 'other' && ($breakdown[$key] ?? 0) === 0)
                            @continue
                        @endif
                        @php $cnt = (int) ($breakdown[$key] ?? 0); @endphp
                        <div class="fy-bar-row">
                            <span class="min-w-150px">@lang('accounting::financial_year.report_type_'.$key)</span>
                            <div class="fy-bar-track">
                                <div class="fy-bar-fill" style="width: {{ min(100, ($cnt / $maxBreakdown) * 100) }}%"></div>
                            </div>
                            <span class="fw-semibold">{{ $cnt }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="fy-section-card h-100">
                <div class="fy-section-head">@lang('accounting::financial_year.report_monthly_activity')</div>
                <div class="fy-section-body">
                    @forelse ($monthly as $row)
                        <div class="fy-bar-row">
                            <span class="min-w-120px">{{ $row['label'] }}</span>
                            <div class="fy-bar-track">
                                <div class="fy-bar-fill" style="width: {{ min(100, ($row['count'] / $maxMonthly) * 100) }}%"></div>
                            </div>
                            <span class="fw-semibold">{{ $row['count'] }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">@lang('accounting::financial_year.report_no_activity')</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if (! $focusPeriod && $linkedPeriods->isNotEmpty())
        <div class="fy-section-card">
            <div class="fy-section-head">@lang('accounting::financial_year.report_linked_periods')</div>
            <div class="table-responsive fy-report-table-wrap">
                <table class="table table-row-bordered align-middle mb-0 fy-report-table">
                    <thead>
                        <tr>
                            <th>@lang('accounting::financial_year.col_period_name')</th>
                            <th>@lang('accounting::financial_year.col_start')</th>
                            <th>@lang('accounting::financial_year.col_end')</th>
                            <th>@lang('accounting::financial_year.col_status')</th>
                            <th>@lang('accounting::financial_year.col_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($linkedPeriods as $p)
                            <tr>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->start_date->format('Y-m-d') }}</td>
                                <td>{{ $p->end_date->format('Y-m-d') }}</td>
                                <td>@include('accounting::settings.partials.period_status_badge', ['period' => $p])</td>
                                <td>
                                    <a href="{{ route('accounting.financial-years.periods.report', $p->id) }}" class="btn btn-sm btn-light-primary">
                                        @lang('accounting::financial_year.action_period_report')
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="fy-section-card">
        <div class="fy-section-head">@lang('accounting::financial_year.report_recent_entries')</div>
        <div class="table-responsive fy-report-table-wrap">
            <table class="table table-row-bordered align-middle mb-0 fy-report-table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.date')</th>
                        <th>@lang('accounting::lang.ref_no')</th>
                        <th>@lang('accounting::lang.type')</th>
                        <th>@lang('accounting::lang.debit')</th>
                        <th>@lang('accounting::lang.credit')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $entry)
                        @php
                            $debit = $entry->transactions->where('type', 'debit')->sum('amount');
                            $credit = $entry->transactions->where('type', 'credit')->sum('amount');
                            $typeKey = $entry->type ? (string) $entry->type : '';
                            $typeLabel = $typeKey && \Illuminate\Support\Facades\Lang::has('accounting::lang.'.$typeKey)
                                ? __('accounting::lang.'.$typeKey)
                                : ($typeKey ?: '—');
                            $rawOpDate = $entry->operation_date
                                ?? $entry->transactions->first()?->operation_date;
                            $opDateFormatted = $rawOpDate
                                ? \Illuminate\Support\Carbon::parse($rawOpDate)->format('Y-m-d')
                                : '—';
                        @endphp
                        <tr>
                            <td>{{ $opDateFormatted }}</td>
                            <td>{{ $entry->ref_no }}</td>
                            <td>{{ $typeLabel }}</td>
                            <td>{{ number_format($debit, 2) }}</td>
                            <td>{{ number_format($credit, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted text-center py-4">@lang('accounting::financial_year.report_no_activity')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
