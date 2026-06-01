<div id="fy-years-list-view">
<section class="mb-6 fy-card p-5" id="fy-locking-section">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="flex-grow-1">
            <h2 class="fs-5 fw-bold text-gray-900 mb-1">@lang('accounting::financial_year.period_locking_title')</h2>
            <p class="text-muted fs-7 mb-0">@lang('accounting::financial_year.period_locking_help')</p>
        </div>
        <div class="form-check form-switch form-check-custom form-check-solid">
            <input class="form-check-input" type="checkbox" id="fy-period-locking-toggle"
                @checked($financialPeriodLockingEnabled ?? false) />
            <label class="form-check-label fw-semibold" for="fy-period-locking-toggle">
                @lang('accounting::financial_year.period_locking_enabled')
            </label>
        </div>
    </div>
</section>
{{-- Section 1: Current year dashboard --}}
<section class="mb-8" id="fy-current-section">
    <div class="mb-4">
        <h2 class="fs-4 fw-bold text-gray-900 mb-1">@lang('accounting::financial_year.section_current_title')</h2>
    </div>

    <div id="fy-current-empty" class="fy-card fy-empty">
        <div class="fy-empty-icon"><i class="fas fa-calendar-alt"></i></div>
        <h3 class="fs-5 fw-bold text-gray-800 mb-2">@lang('accounting::financial_year.empty_current_title')</h3>
        <p class="text-muted mb-0 mx-auto" style="max-width: 420px;">@lang('accounting::financial_year.empty_current_text')</p>
        <button type="button" class="btn btn-primary mt-5" id="fy-btn-add-year-current">
            <i class="fas fa-plus me-2"></i>@lang('accounting::financial_year.add_year')
        </button>
    </div>

    <div id="fy-current-cards" class="row g-4 d-none">
        <div class="col-sm-6 col-xl">
            <div class="fy-stat-card d-flex align-items-center gap-3">
                <div class="fy-icon-wrap teal"><i class="fas fa-calendar-check"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fy-stat-label">@lang('accounting::financial_year.current_year')</div>
                    <div class="fy-stat-value text-truncate" id="fy-stat-year">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="fy-stat-card d-flex align-items-center gap-3">
                <div class="fy-icon-wrap blue"><i class="fas fa-play"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fy-stat-label">@lang('accounting::financial_year.start_date')</div>
                    <div class="fy-stat-value fy-date" id="fy-stat-start">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="fy-stat-card d-flex align-items-center gap-3">
                <div class="fy-icon-wrap slate"><i class="fas fa-stop"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fy-stat-label">@lang('accounting::financial_year.end_date')</div>
                    <div class="fy-stat-value fy-date" id="fy-stat-end">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="fy-stat-card d-flex align-items-center gap-3">
                <div class="fy-icon-wrap green"><i class="fas fa-hourglass-half"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fy-stat-label">@lang('accounting::financial_year.months_count')</div>
                    <div class="fy-stat-value" id="fy-stat-months">—</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="fy-stat-card d-flex align-items-center gap-3">
                <div class="fy-icon-wrap teal"><i class="fas fa-circle-dot"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fy-stat-label">@lang('accounting::financial_year.status')</div>
                    <div id="fy-stat-status"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 3: History table --}}
<section>
    <div class="fy-card">
        <div class="fy-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="fy-card-title">@lang('accounting::financial_year.section_history_title')</h2>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="fy-btn-add-year">
                <i class="fas fa-plus me-2"></i>@lang('accounting::financial_year.add_year')
            </button>
        </div>
        <div class="card-body p-0 position-relative">
            <div class="fy-loading-overlay" id="fy-table-loading" aria-hidden="true">
                <div class="spinner-border text-primary" role="status"></div>
            </div>

            <div id="fy-history-empty" class="fy-empty">
                <div class="fy-empty-icon"><i class="fas fa-table-list"></i></div>
                <h3 class="fs-5 fw-bold text-gray-800 mb-2">@lang('accounting::financial_year.empty_history_title')</h3>
                <p class="text-muted mb-0 mx-auto px-4" style="max-width: 440px;">
                    @lang('accounting::financial_year.empty_history_text')
                </p>
                <button type="button" class="btn btn-primary mt-5" id="fy-btn-add-year-empty">
                    <i class="fas fa-plus me-2"></i>@lang('accounting::financial_year.add_year')
                </button>
            </div>

            <div class="table-responsive fy-years-table-wrap d-none" id="fy-history-table-wrap">
                <table class="table table-row-bordered table-row-gray-100 align-middle fy-table fy-table-spacious mb-0">
                    <thead>
                        <tr>
                            <th>@lang('accounting::financial_year.col_year')</th>
                            <th>@lang('accounting::financial_year.col_start')</th>
                            <th>@lang('accounting::financial_year.col_end')</th>
                            <th>@lang('accounting::financial_year.col_duration')</th>
                            <th class="text-center">@lang('accounting::financial_year.col_status')</th>
                            <th class="text-end">@lang('accounting::financial_year.col_actions')</th>
                        </tr>
                    </thead>
                    <tbody id="fy-history-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>
</div>

@include('accounting::settings.financial-year-detail')

{{-- Add fiscal year modal --}}
<div class="modal fade" id="fyYearAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title fw-bold mb-1" id="fy-add-modal-title">@lang('accounting::financial_year.section_add_title')</h3>
                    <p class="text-muted fs-7 mb-0" id="fy-add-modal-subtitle">@lang('accounting::financial_year.section_add_subtitle')</p>
                    <p class="text-primary fs-7 mb-0 mt-2 d-none" id="fy-auto-next-hint">@lang('accounting::financial_year.auto_next_year_hint')</p>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="fy-add-year-form" novalidate>
                <div class="modal-body position-relative fy-form-wrap">
                    <div class="fy-loading-overlay" id="fy-form-loading" aria-hidden="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">@lang('accounting::financial_year.saving')</span>
                        </div>
                    </div>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy_start_date">
                                @lang('accounting::financial_year.start_date')
                                <i class="fas fa-circle-question fy-label-hint" data-bs-toggle="tooltip"
                                    title="@lang('accounting::financial_year.tooltip_start_date')"></i>
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy_start_date"
                                name="start_date" placeholder="YYYY-MM-DD" autocomplete="off" required />
                            <div class="fy-help">@lang('accounting::financial_year.start_date_help')</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy_end_date">
                                @lang('accounting::financial_year.end_date')
                                <i class="fas fa-circle-question fy-label-hint" data-bs-toggle="tooltip"
                                    title="@lang('accounting::financial_year.tooltip_end_date')"></i>
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy_end_date"
                                name="end_date" placeholder="YYYY-MM-DD" autocomplete="off" required />
                            <div class="fy-help">@lang('accounting::financial_year.end_date_help')</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="fy_description">
                                @lang('accounting::financial_year.description')
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy_description"
                                name="description" maxlength="120"
                                placeholder="@lang('accounting::financial_year.description_placeholder')" />
                            <div class="fy-help">@lang('accounting::financial_year.description_help')</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy_status">
                                @lang('accounting::financial_year.status')
                                <i class="fas fa-circle-question fy-label-hint" data-bs-toggle="tooltip"
                                    title="@lang('accounting::financial_year.tooltip_status')"></i>
                            </label>
                            <select class="form-select form-select-solid" id="fy_status" name="status" required>
                                <option value="open">@lang('accounting::financial_year.status_open')</option>
                                <option value="closed">@lang('accounting::financial_year.status_closed')</option>
                            </select>
                            <div class="fy-help">@lang('accounting::financial_year.status_help')</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary" id="fy-save-btn">
                        <i class="fas fa-save me-2"></i><span id="fy-save-btn-label">@lang('accounting::financial_year.save_first_year')</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit fiscal year modal --}}
<div class="modal fade" id="fyYearEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold">@lang('accounting::financial_year.edit_year_title')</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="fy-year-edit-form">
                <div class="modal-body">
                    <input type="hidden" id="fy-edit-year-id" />
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy-edit-year-start">
                                @lang('accounting::financial_year.start_date')
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy-edit-year-start"
                                required autocomplete="off" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy-edit-year-end">
                                @lang('accounting::financial_year.end_date')
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy-edit-year-end"
                                required autocomplete="off" />
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="fy-edit-year-description">
                                @lang('accounting::financial_year.description')
                            </label>
                            <input type="text" class="form-control form-control-solid" id="fy-edit-year-description"
                                maxlength="120" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required" for="fy-edit-year-status">
                                @lang('accounting::financial_year.status')
                            </label>
                            <select class="form-select form-select-solid" id="fy-edit-year-status" required>
                                <option value="open">@lang('accounting::financial_year.status_open')</option>
                                <option value="closed">@lang('accounting::financial_year.status_closed')</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-muted fs-7 mt-4 mb-0">
                        @lang('accounting::financial_year.edit_year_periods_hint')
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
