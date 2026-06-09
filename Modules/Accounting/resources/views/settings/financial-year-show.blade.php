@extends('layouts.app')

@section('title', __('accounting::financial_year.year_show_title'))

@section('css')
    @include('accounting::settings.partials.fiscal_report._styles')
@endsection

@section('content')
    <div class="container-fluid py-5 fy-report">
        <div class="mb-5 no-print">
            <a href="{{ route('accounting-settings', ['tab' => 'financial-year']) }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
                @lang('accounting::financial_year.back_to_years')
            </a>
        </div>

        <div class="fy-report-hero mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="fs-2 fw-bold text-gray-900 mb-1">{{ $year->name }}</h1>
                    <p class="text-muted mb-0">@lang('accounting::financial_year.year_details_title')</p>
                </div>
                <div class="d-flex flex-wrap gap-2 no-print">
                    <a href="{{ route('accounting.financial-years.report', $year->id) }}" class="btn btn-light-primary btn-sm">
                        <i class="fas fa-file-lines me-1"></i> @lang('accounting::financial_year.action_year_report')
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-5">
            <div class="col-lg-5">
                <div class="fy-section-card">
                    <div class="fy-section-head">@lang('accounting::financial_year.year_details_title')</div>
                    <div class="fy-section-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.description')</dt>
                            <dd class="col-sm-7 fw-semibold">{{ $year->name }}</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.start_date')</dt>
                            <dd class="col-sm-7">{{ $year->start_date->format('Y-m-d') }}</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.end_date')</dt>
                            <dd class="col-sm-7">{{ $year->end_date->format('Y-m-d') }}</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.status')</dt>
                            <dd class="col-sm-7">@lang('accounting::financial_year.status_'.$year->status)</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.report_periods_count')</dt>
                            <dd class="col-sm-7">{{ $periodsCount }}</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.current_year')</dt>
                            <dd class="col-sm-7">
                                {{ $isCurrentYear ? __('messages.yes') : __('messages.no') }}
                            </dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.created_by')</dt>
                            <dd class="col-sm-7">{{ $creatorName }}</dd>
                            <dt class="col-sm-5 text-muted">@lang('accounting::financial_year.created_at')</dt>
                            <dd class="col-sm-7">{{ $year->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="fy-section-card">
                    <div class="fy-section-head">@lang('accounting::financial_year.periods_title')</div>
                    <div class="table-responsive fy-report-table-wrap">
                        <table class="table table-row-bordered align-middle mb-0 fy-report-table">
                            <thead>
                                <tr>
                                    <th>@lang('accounting::financial_year.col_period_name')</th>
                                    <th>@lang('accounting::financial_year.col_start')</th>
                                    <th>@lang('accounting::financial_year.col_end')</th>
                                    <th>@lang('accounting::financial_year.col_status')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($year->periods as $p)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.financial-years.periods.view', $p->id) }}">{{ $p->name }}</a>
                                        </td>
                                        <td>{{ $p->start_date->format('Y-m-d') }}</td>
                                        <td>{{ $p->end_date->format('Y-m-d') }}</td>
                                        <td>@include('accounting::settings.partials.period_status_badge', ['period' => $p])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
