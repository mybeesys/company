{{-- Modals live outside tab panes so Bootstrap backdrop works on every tab. --}}
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
