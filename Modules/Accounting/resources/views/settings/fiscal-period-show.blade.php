@extends('layouts.app')

@section('title', __('accounting::financial_year.period_show_title'))

@section('css')
    @include('accounting::settings.partials.fiscal_report._styles')
@endsection

@section('content')
    <div class="container-fluid py-5 fy-report">
        <div class="mb-5 no-print">
            <a href="{{ route('accounting-settings', ['tab' => 'financial-year', 'year' => $year->id]) }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
                @lang('accounting::financial_year.back_to_year')
            </a>
        </div>

        <div class="fy-report-hero mb-5">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h1 class="fs-2 fw-bold mb-1">{{ $period->name }}</h1>
                    <p class="text-muted mb-0">{{ $year->name }}</p>
                </div>
                <a href="{{ route('accounting.financial-years.periods.report', $period->id) }}" class="btn btn-light-primary btn-sm no-print">
                    <i class="fas fa-file-lines me-1"></i> @lang('accounting::financial_year.action_period_report')
                </a>
            </div>
        </div>

        <div class="fy-section-card">
            <div class="fy-section-head">@lang('accounting::financial_year.period_details_title')</div>
            <div class="fy-section-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.col_period_name')</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $period->name }}</dd>
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.start_date')</dt>
                    <dd class="col-sm-8">{{ $period->start_date->format('Y-m-d') }}</dd>
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.end_date')</dt>
                    <dd class="col-sm-8">{{ $period->end_date->format('Y-m-d') }}</dd>
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.status')</dt>
                    <dd class="col-sm-8">@include('accounting::settings.partials.period_status_badge', ['period' => $period])</dd>
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.parent_year')</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('accounting-settings', ['tab' => 'financial-year', 'year' => $year->id]) }}">{{ $year->name }}</a>
                    </dd>
                    <dt class="col-sm-4 text-muted">@lang('accounting::financial_year.created_by')</dt>
                    <dd class="col-sm-8">{{ $creatorName }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
