@extends('layouts.app')

@section('title', $title)

@section('css')
    @include('accounting::settings.partials.fiscal_report._styles')
@endsection

@section('content')
    <div class="container-fluid py-5">
        <div class="d-flex flex-wrap gap-2 mb-4 no-print">
            <a href="{{ route('accounting.financial-years.periods.view', $report['focus_period']->id) }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-1"></i>
                @lang('accounting::financial_year.back_to_period')
            </a>
            <a href="{{ route('accounting.financial-years.periods.report.print', $report['focus_period']->id) }}" target="_blank" class="btn btn-sm btn-light">
                <i class="fas fa-print me-1"></i> @lang('accounting::financial_year.report_print')
            </a>
            <a href="{{ route('accounting.financial-years.periods.report.pdf', $report['focus_period']->id) }}" class="btn btn-sm btn-light-danger">
                <i class="fas fa-file-pdf me-1"></i> @lang('accounting::financial_year.report_pdf')
            </a>
        </div>

        @include('accounting::settings.partials.fiscal_report._body', ['report' => $report, 'title' => $title])
    </div>
@endsection
