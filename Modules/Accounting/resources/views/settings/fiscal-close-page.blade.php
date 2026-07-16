@extends('layouts.app')

@section('title', __('accounting::fiscal_close.wizard_title'))

@section('css')
    @include('accounting::settings.partials.fiscal_report._styles')
    <style>
        .fy-close-stepper {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--bs-gray-200);
            background: #fff;
        }
        .fy-close-stepper .step {
            flex: 1;
            padding: 1rem 1.25rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--bs-gray-600);
            border-inline-end: 1px solid var(--bs-gray-200);
            position: relative;
        }
        .fy-close-stepper .step:last-child { border-inline-end: none; }
        .fy-close-stepper .step.active {
            background: var(--bs-primary);
            color: #fff;
        }
        .fy-close-stepper .step.done {
            background: var(--bs-light-success, #e8fff3);
            color: var(--bs-success);
        }
        .fy-close-panel { display: none; }
        .fy-close-panel.active { display: block; }
        .fy-close-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .fy-close-summary-item {
            background: #f9f9f9;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }
        .fy-close-summary-item .label {
            font-size: 0.8rem;
            color: var(--bs-gray-600);
            margin-bottom: 0.25rem;
        }
        .fy-close-summary-item .value {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .fy-close-lines-wrap {
            max-height: none;
        }
        .fy-close-lines-wrap .table thead th {
            position: sticky;
            top: 0;
            background: #f9f9f9;
            z-index: 1;
        }
        .fy-close-actions {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px solid var(--bs-gray-200);
            padding: 1.25rem 0;
            margin-top: 2rem;
            z-index: 10;
        }
        #fy-close-loading {
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-5 fy-report" id="fy-close-page"
        data-year-id="{{ $year->id }}"
        data-period-id="{{ $period?->id }}"
        data-close-target="{{ $period ? 'period' : 'year' }}">
        <div class="mb-5">
            <a href="{{ route('accounting-settings', ['tab' => 'financial-year', 'year' => $year->id]) }}" class="btn btn-sm btn-light-primary">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
                @lang('accounting::fiscal_close.page_back')
            </a>
        </div>

        <div class="fy-report-hero mb-5">
            <h1 class="fs-2 fw-bold mb-1">@lang('accounting::fiscal_close.wizard_title')</h1>
            <p class="text-muted mb-0" id="fy-close-subtitle">
                @if ($period)
                    @lang('accounting::fiscal_close.page_subtitle_period', ['period' => $period->name, 'year' => $year->name])
                @else
                    @lang('accounting::fiscal_close.page_subtitle_year', ['name' => $year->name])
                @endif
            </p>
        </div>

        <div id="fy-close-loading">
            <div class="text-center text-muted">
                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                @lang('accounting::fiscal_close.page_loading')
            </div>
        </div>

        <div id="fy-close-error" class="alert alert-danger d-none"></div>

        <div id="fy-close-main" class="d-none">
            <div class="fy-close-stepper" id="fy-close-stepper">
                <div class="step active" data-step="1">@lang('accounting::fiscal_close.wizard_step_readiness')</div>
                <div class="step" data-step="2">@lang('accounting::fiscal_close.wizard_step_preview')</div>
                <div class="step" data-step="3">@lang('accounting::fiscal_close.wizard_step_confirm')</div>
            </div>

            <div class="fy-close-panel active" data-panel="1">
                <div class="fy-section-card">
                    <div class="fy-section-head">@lang('accounting::fiscal_close.wizard_step_readiness')</div>
                    <div class="fy-section-body" id="fy-close-readiness"></div>
                </div>
            </div>

            <div class="fy-close-panel" data-panel="2">
                <div class="fy-section-card mb-5">
                    <div class="fy-section-head d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>@lang('accounting::fiscal_close.wizard_step_preview')</span>
                        <span id="fy-close-balance-badge"></span>
                    </div>
                    <div class="fy-section-body">
                        <div class="fy-close-summary-grid mb-5" id="fy-close-summary"></div>
                        <div class="fy-close-lines-wrap table-responsive" id="fy-close-lines"></div>
                        <div class="alert alert-light-info border border-info border-dashed mt-4 mb-0 fs-7" id="fy-close-preview-note"></div>
                    </div>
                </div>
            </div>

            <div class="fy-close-panel" data-panel="3">
                <div class="fy-section-card">
                    <div class="fy-section-head">@lang('accounting::fiscal_close.wizard_step_confirm')</div>
                    <div class="fy-section-body">
                        <div class="alert alert-light-warning border border-warning border-dashed fs-7 mb-4">
                            @lang('accounting::fiscal_close.wizard_admin_only_note')
                        </div>
                        <div id="fy-close-execute-result" class="d-none"></div>
                    </div>
                </div>
            </div>

            <div class="fy-close-actions d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <a href="{{ route('accounting-settings', ['tab' => 'financial-year', 'year' => $year->id]) }}" class="btn btn-light">
                        @lang('messages.cancel')
                    </a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-light d-none" id="fy-close-btn-back">
                        @lang('accounting::fiscal_close.wizard_back')
                    </button>
                    <button type="button" class="btn btn-primary d-none" id="fy-close-btn-next">
                        @lang('accounting::fiscal_close.wizard_next')
                    </button>
                    <a href="{{ route('accounting-settings', ['tab' => 'accounts-routing']) }}" class="btn btn-primary d-none" id="fy-close-btn-routing">
                        @lang('accounting::fiscal_close.wizard_go_routing')
                    </a>
                    <button type="button" class="btn btn-danger d-none" id="fy-close-btn-execute">
                        @lang('accounting::fiscal_close.wizard_execute_and_close')
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.fyClosePageConfig = {
            csrfToken: @json(csrf_token()),
            yearId: @json($year->id),
            periodId: @json($period?->id),
            closeTarget: @json($period ? 'period' : 'year'),
            backUrl: @json(route('accounting-settings', ['tab' => 'financial-year', 'year' => $year->id])),
            accountsRoutingUrl: @json(route('accounting-settings', ['tab' => 'accounts-routing'])),
            journalShowBaseUrl: @json(url('journal-entry-show')),
            api: {
                readiness: @json(route('accounting.financial-years.accounting-close.readiness', $year->id)),
                preview: @json(route('accounting.financial-years.accounting-close.preview', $year->id)),
                execute: @json(route('accounting.financial-years.accounting-close.execute', $year->id)),
                closeYear: @json(route('accounting.financial-years.close', $year->id)),
                closePeriod: @json($period ? route('accounting.financial-years.periods.close', $period->id) : null),
            },
            messages: {
                apiError: @json(__('accounting::financial_year.api_error')),
                fiscalCloseRoutingReady: @json(__('accounting::fiscal_close.wizard_routing_ready')),
                fiscalCloseRoutingMissing: @json(__('accounting::fiscal_close.wizard_routing_missing')),
                fiscalCloseWizardBlockers: @json(__('accounting::fiscal_close.wizard_blockers')),
                fiscalCloseWizardWarnings: @json(__('accounting::fiscal_close.wizard_warnings')),
                fiscalCloseWizardJournalDate: @json(__('accounting::fiscal_close.wizard_journal_date')),
                fiscalCloseWizardBalanced: @json(__('accounting::fiscal_close.wizard_balanced')),
                fiscalCloseWizardUnbalanced: @json(__('accounting::fiscal_close.wizard_unbalanced')),
                fiscalCloseWizardTotalIncome: @json(__('accounting::fiscal_close.wizard_total_income')),
                fiscalCloseWizardTotalExpenses: @json(__('accounting::fiscal_close.wizard_total_expenses')),
                fiscalCloseWizardNetIncome: @json(__('accounting::fiscal_close.wizard_net_income')),
                fiscalCloseWizardPlAccounts: @json(__('accounting::fiscal_close.wizard_pl_accounts')),
                fiscalCloseWizardColAccount: @json(__('accounting::fiscal_close.wizard_col_account')),
                fiscalCloseWizardColDebit: @json(__('accounting::fiscal_close.wizard_col_debit')),
                fiscalCloseWizardColCredit: @json(__('accounting::fiscal_close.wizard_col_credit')),
                fiscalCloseWizardColDescription: @json(__('accounting::fiscal_close.wizard_col_description')),
                fiscalCloseWizardNoLines: @json(__('accounting::fiscal_close.wizard_no_lines')),
                fiscalCloseWizardExecuting: @json(__('accounting::fiscal_close.wizard_executing')),
                fiscalCloseExecuteSuccess: @json(__('accounting::fiscal_close.execute_success')),
                fiscalCloseViewJournal: @json(__('accounting::fiscal_close.wizard_view_journal')),
                pageAdminCloseSuccess: @json(__('accounting::fiscal_close.page_admin_close_success')),
            },
        };
    </script>
    <script src="{{ asset('modules/accounting/js/fiscal-period-close-page.js') }}?v=1"></script>
@endsection
