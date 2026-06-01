@extends('layouts.app')

@section('title', __('accounting::financial_year.accounting_settings'))

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .fy-settings {
            --fy-border: #e8ecf1;
            --fy-surface: #ffffff;
            --fy-muted: #6b7280;
            --fy-heading: #111827;
            --fy-accent: #0d6e6e;
            --fy-accent-soft: #e6f4f4;
            --fy-radius: 12px;
            --fy-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        }

        .fy-settings .fy-banner {
            border: 1px dashed #b8d4d4;
            background: var(--fy-accent-soft);
            border-radius: var(--fy-radius);
            color: #0f4f4f;
            font-size: 0.9rem;
        }

        .fy-settings .fy-card {
            border: 1px solid var(--fy-border);
            border-radius: var(--fy-radius);
            background: var(--fy-surface);
            box-shadow: var(--fy-shadow);
        }

        .fy-settings .fy-card-header {
            border-bottom: 1px solid var(--fy-border);
            padding: 1.25rem 1.5rem;
        }

        .fy-settings .fy-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--fy-heading);
            margin: 0;
        }

        .fy-settings .fy-card-subtitle {
            font-size: 0.85rem;
            color: var(--fy-muted);
            margin: 0.35rem 0 0;
        }

        .fy-settings .fy-stat-card {
            border: 1px solid var(--fy-border);
            border-radius: var(--fy-radius);
            background: #fafbfc;
            padding: 1.15rem 1.25rem;
            height: 100%;
            transition: box-shadow 0.2s ease;
        }

        .fy-settings .fy-stat-card:hover {
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }

        .fy-settings .fy-stat-label {
            font-size: 0.8rem;
            color: var(--fy-muted);
            margin-bottom: 0.35rem;
            font-weight: 500;
        }

        .fy-settings .fy-stat-value {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--fy-heading);
            font-variant-numeric: tabular-nums;
        }

        .fy-settings .fy-stat-value.fy-date {
            font-size: 1rem;
            letter-spacing: 0.01em;
        }

        .fy-settings .fy-icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fy-settings .fy-icon-wrap.teal {
            background: var(--fy-accent-soft);
            color: var(--fy-accent);
        }

        .fy-settings .fy-icon-wrap.slate {
            background: #f1f5f9;
            color: #475569;
        }

        .fy-settings .fy-icon-wrap.blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .fy-settings .fy-icon-wrap.green {
            background: #ecfdf5;
            color: #059669;
        }

        .fy-settings .fy-help {
            font-size: 0.8rem;
            color: var(--fy-muted);
            margin-top: 0.35rem;
        }

        .fy-settings .fy-label-hint {
            cursor: help;
            color: #94a3b8;
            margin-inline-start: 0.25rem;
        }

        .fy-settings .badge-fy-open {
            background: #d1fae5;
            color: #047857;
        }

        .fy-settings .badge-fy-closed {
            background: #f1f5f9;
            color: #475569;
        }

        .fy-settings .badge-fy-upcoming {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .fy-settings .fy-table thead th {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--fy-muted);
            text-transform: none;
            border-bottom: 1px solid var(--fy-border);
            padding: 1.15rem 1.5rem;
            background: #f8fafc;
        }

        .fy-settings .fy-table tbody td {
            padding: 1.15rem 1.5rem;
            vertical-align: middle;
            font-variant-numeric: tabular-nums;
        }

        .fy-settings .fy-table.fy-table-spacious tbody tr:first-child td {
            padding-top: 1.25rem;
        }

        .fy-settings .fy-empty {
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .fy-settings .fy-empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .fy-settings .fy-loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            border-radius: var(--fy-radius);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }

        .fy-settings .fy-loading-overlay.is-active {
            display: flex;
        }

        .fy-settings .fy-form-wrap {
            position: relative;
        }

        .fy-settings .is-invalid ~ .invalid-feedback {
            display: block;
        }

        html[dir="rtl"] .fy-settings .fy-table tbody td,
        html[dir="rtl"] .fy-settings .fy-stat-value {
            direction: ltr;
            text-align: end;
        }

        html[dir="rtl"] .fy-settings .fy-stat-label {
            text-align: start;
        }

        @media (max-width: 767.98px) {
            .fy-settings .fy-stat-card {
                margin-bottom: 0.5rem;
            }
        }

        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        /* Fiscal periods detail */
        .fy-detail-dl .fy-detail-row {
            padding: 0.85rem 0;
            border-bottom: 1px solid var(--fy-border);
        }

        .fy-detail-dl .fy-detail-row:last-child {
            border-bottom: 0;
        }

        .fy-detail-dl dt {
            font-size: 0.8rem;
            color: var(--fy-muted);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .fy-detail-dl dd {
            margin: 0;
            font-size: 0.95rem;
        }

        .fy-date-num {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.02em;
        }

        .fy-periods-table-wrap {
            max-height: min(62vh, 520px);
            overflow: auto;
        }

        .fy-periods-thead {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f8fafc;
            box-shadow: 0 1px 0 var(--fy-border);
        }

        .fy-periods-thead th {
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
            padding: 1.15rem 1.5rem !important;
        }

        .fy-periods-table tbody td {
            padding: 1.15rem 1.5rem !important;
        }

        .fy-periods-table-wrap {
            padding: 0.5rem 0.75rem 0;
        }

        .fy-periods-thead th.fy-sort-active .fy-sort-icon {
            color: var(--fy-accent);
        }

        .fy-sort-icon {
            font-size: 0.65rem;
            margin-inline-start: 0.35rem;
            color: #cbd5e1;
        }

        .fy-periods-search-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            inset-inline-start: 0.75rem;
            color: #94a3b8;
            font-size: 0.8rem;
            pointer-events: none;
        }

        .fy-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: #64748b;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .fy-action-btn:hover:not(:disabled) {
            background: #f1f5f9;
            color: var(--fy-accent);
            border-color: #e2e8f0;
        }

        .fy-action-btn.btn-open:hover:not(:disabled) {
            color: #059669;
            background: #ecfdf5;
        }

        .fy-action-btn.btn-close-period:hover:not(:disabled) {
            color: #dc2626;
            background: #fef2f2;
        }

        .fy-action-btn.btn-delete-year:hover:not(:disabled) {
            color: #dc2626;
            background: #fef2f2;
        }

        .fy-action-btn:disabled {
            opacity: 0.38;
            cursor: not-allowed;
        }

        .badge-fy-period-open {
            background: #d1fae5;
            color: #047857;
        }

        .badge-fy-period-closed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-fy-period-closing {
            background: #ffedd5;
            color: #c2410c;
        }

        .badge-fy-period-upcoming {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .fy-years-table-wrap {
            padding: 0.5rem 0.75rem 1rem;
        }
    </style>
@endsection

@section('content')
    <div class="fy-settings d-flex flex-column gap-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h1 class="mb-1">@lang('accounting::financial_year.accounting_settings')</h1>
            </div>
        </div>

        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-5 border-0 fw-semibold" id="accountingSettingsTabs">
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 {{ ($activeTab ?? 'financial-year') === 'financial-year' ? 'active' : '' }}"
                    data-bs-toggle="tab" href="#financial_year_settings_tab" role="tab">
                    @lang('accounting::financial_year.financial_year_settings')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800 {{ ($activeTab ?? '') === 'accounts-routing' ? 'active' : '' }}"
                    data-bs-toggle="tab" href="#accounts_routing_settings_tab" role="tab">
                    @lang('accounting::financial_year.accounts_routing_tab')
                </a>
            </li>
        </ul>

        <div class="tab-content" id="accountingSettingsTabContent">
            <div class="tab-pane fade {{ ($activeTab ?? 'financial-year') === 'financial-year' ? 'show active' : '' }}"
                id="financial_year_settings_tab" role="tabpanel">
                @include('accounting::settings.financial-year')
            </div>
            <div class="tab-pane fade {{ ($activeTab ?? '') === 'accounts-routing' ? 'show active' : '' }}"
                id="accounts_routing_settings_tab" role="tabpanel">
                @if ($hasAccounts ?? false)
                    @include('accounting::AccountsRouting.routing-form')
                @else
                    <div class="alert alert-warning d-flex align-items-center gap-3">
                        <i class="fas fa-exclamation-triangle fs-4"></i>
                        <div>
                            <span>@lang('accounting::lang.no_accounts')</span>
                            <a href="{{ route('tree-of-accounts') }}" class="fw-semibold ms-2">
                                @lang('menuItemLang.chart_of_accounts')
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @if (app()->getLocale() === 'ar')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    @endif
    <script>
        window.fySettingsConfig = {
            locale: @json(app()->getLocale()),
            storageKey: 'bee_accounting_financial_years_v1',
            messages: {
                required: @json(__('accounting::financial_year.validation_required')),
                invalidDate: @json(__('accounting::financial_year.validation_invalid_date')),
                endBeforeStart: @json(__('accounting::financial_year.validation_end_before_start')),
                sectionSetupTitle: @json(__('accounting::financial_year.section_setup_title')),
                sectionSetupSubtitle: @json(__('accounting::financial_year.section_setup_subtitle')),
                sectionAddTitle: @json(__('accounting::financial_year.section_add_title')),
                sectionAddSubtitle: @json(__('accounting::financial_year.section_add_subtitle')),
                saveFirstYear: @json(__('accounting::financial_year.save_first_year')),
                addYear: @json(__('accounting::financial_year.add_year')),
                saveSuccess: @json(__('accounting::financial_year.save_success')),
                saving: @json(__('accounting::financial_year.saving')),
                monthsUnit: @json(__('accounting::financial_year.months_unit')),
                daysUnit: @json(__('accounting::financial_year.days_unit')),
                statusOpen: @json(__('accounting::financial_year.status_open')),
                statusClosed: @json(__('accounting::financial_year.status_closed')),
                emptyCurrent: @json(__('accounting::financial_year.empty_current_title')),
                dash: '—',
                managePeriods: @json(__('accounting::financial_year.manage_periods')),
                actionViewYear: @json(__('accounting::financial_year.action_view_year')),
                actionEditYear: @json(__('accounting::financial_year.action_edit_year')),
                actionDeleteYear: @json(__('accounting::financial_year.action_delete_year')),
                yearUpdatedSuccess: @json(__('accounting::financial_year.year_updated_success')),
                yearDeletedSuccess: @json(__('accounting::financial_year.year_deleted_success')),
                confirmDeleteYearTitle: @json(__('accounting::financial_year.confirm_delete_year_title')),
                confirmDeleteYearText: @json(__('accounting::financial_year.confirm_delete_year_text')),
                confirmYesDelete: @json(__('accounting::financial_year.confirm_yes_delete')),
                periodOpen: @json(__('accounting::financial_year.period_status_open')),
                periodClosed: @json(__('accounting::financial_year.period_status_closed')),
                actionOpen: @json(__('accounting::financial_year.action_open_period')),
                actionClose: @json(__('accounting::financial_year.action_close_period')),
                actionView: @json(__('accounting::financial_year.action_view_period')),
                actionEdit: @json(__('accounting::financial_year.action_edit_period')),
                confirmCloseTitle: @json(__('accounting::financial_year.confirm_close_title')),
                confirmCloseText: @json(__('accounting::financial_year.confirm_close_text')),
                confirmOpenTitle: @json(__('accounting::financial_year.confirm_open_title')),
                confirmOpenText: @json(__('accounting::financial_year.confirm_open_text')),
                confirmYesClose: @json(__('accounting::financial_year.confirm_yes_close')),
                confirmYesOpen: @json(__('accounting::financial_year.confirm_yes_open')),
                periodClosedSuccess: @json(__('accounting::financial_year.period_closed_success')),
                periodOpenedSuccess: @json(__('accounting::financial_year.period_opened_success')),
                periodUpdatedSuccess: @json(__('accounting::financial_year.period_updated_success')),
                periodActionDisabled: @json(__('accounting::financial_year.period_action_disabled')),
                showingPeriods: @json(__('accounting::financial_year.showing_periods')),
                periodsEmpty: @json(__('accounting::financial_year.periods_empty')),
                cancel: @json(__('messages.cancel')),
            },
        };
    </script>
    <script src="{{ asset('modules/accounting/js/fiscal-periods.js') }}?v=5"></script>
    <script src="{{ asset('modules/accounting/js/financial-year-settings.js') }}?v=7"></script>
    @if ($hasAccounts ?? false)
        @include('accounting::AccountsRouting.select2-init')
    @endif
    <script>
        document.querySelectorAll('#accountingSettingsTabs a[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                const id = this.getAttribute('href');
                if (id && history.replaceState) {
                    const param = id === '#accounts_routing_settings_tab' ? 'accounts-routing' : 'financial-year';
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', param);
                    history.replaceState(null, '', url.toString());
                }
            });
        });
    </script>
@endsection
