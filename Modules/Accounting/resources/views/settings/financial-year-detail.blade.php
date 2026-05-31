{{-- Fiscal year detail: periods management (UI only) --}}
<section id="fy-year-detail-view" class="d-none">
    <div class="mb-5">
        <button type="button" class="btn btn-sm btn-light-primary" id="fy-detail-back">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>
            @lang('accounting::financial_year.back_to_years')
        </button>
    </div>

    <div class="row g-5 fy-detail-layout">
        {{-- RTL: first column = right — year details --}}
        <div class="col-lg-4">
            <div class="fy-card fy-detail-sidebar h-100">
                <div class="fy-card-header">
                    <h2 class="fy-card-title mb-0">@lang('accounting::financial_year.year_details_title')</h2>
                </div>
                <div class="card-body p-5">
                    <dl class="fy-detail-dl mb-0">
                        <div class="fy-detail-row">
                            <dt>@lang('accounting::financial_year.description')</dt>
                            <dd id="fy-detail-name" class="fw-bold text-gray-900">—</dd>
                        </div>
                        <div class="fy-detail-row">
                            <dt>@lang('accounting::financial_year.start_date')</dt>
                            <dd id="fy-detail-start" class="fy-date-num">—</dd>
                        </div>
                        <div class="fy-detail-row">
                            <dt>@lang('accounting::financial_year.end_date')</dt>
                            <dd id="fy-detail-end" class="fy-date-num">—</dd>
                        </div>
                        <div class="fy-detail-row">
                            <dt>@lang('accounting::financial_year.status')</dt>
                            <dd id="fy-detail-status">—</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- RTL: second column = left — periods table --}}
        <div class="col-lg-8">
            <div class="fy-card h-100">
                <div class="fy-card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h2 class="fy-card-title mb-1">@lang('accounting::financial_year.periods_title')</h2>
                        <p class="fy-card-subtitle mb-0">@lang('accounting::financial_year.periods_subtitle')</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="position-relative">
                            <i class="fas fa-search fy-periods-search-icon"></i>
                            <input type="search" class="form-control form-control-sm form-control-solid ps-10 w-200px"
                                id="fy-periods-search" placeholder="@lang('accounting::financial_year.search_periods')"
                                autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div class="fy-loading-overlay" id="fy-periods-loading" aria-hidden="true">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>

                    <div class="table-responsive fy-periods-table-wrap">
                        <table class="table table-row-bordered align-middle fy-table fy-table-spacious fy-periods-table mb-0">
                            <thead class="fy-periods-thead">
                                <tr>
                                    <th class="fy-sortable" data-sort="name">
                                        @lang('accounting::financial_year.col_period_name')
                                        <i class="fas fa-sort fy-sort-icon"></i>
                                    </th>
                                    <th class="fy-sortable" data-sort="start_date">
                                        @lang('accounting::financial_year.col_start')
                                        <i class="fas fa-sort fy-sort-icon"></i>
                                    </th>
                                    <th class="fy-sortable" data-sort="end_date">
                                        @lang('accounting::financial_year.col_end')
                                        <i class="fas fa-sort fy-sort-icon"></i>
                                    </th>
                                    <th class="fy-sortable text-center" data-sort="status">
                                        @lang('accounting::financial_year.col_status')
                                        <i class="fas fa-sort fy-sort-icon"></i>
                                    </th>
                                    <th class="text-end min-w-140px">@lang('accounting::financial_year.col_actions')</th>
                                </tr>
                            </thead>
                            <tbody id="fy-periods-tbody"></tbody>
                        </table>
                    </div>

                    <div id="fy-periods-empty" class="fy-empty d-none">
                        <div class="fy-empty-icon"><i class="fas fa-calendar-xmark"></i></div>
                        <p class="text-muted mb-0">@lang('accounting::financial_year.periods_empty')</p>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-5 py-4 border-top"
                        id="fy-periods-pagination-wrap">
                        <span class="text-muted fs-7" id="fy-periods-showing"></span>
                        <ul class="pagination pagination-sm mb-0" id="fy-periods-pagination"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Period view modal --}}
<div class="modal fade" id="fyPeriodViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold">@lang('accounting::financial_year.period_details_title')</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body pt-2">
                <dl class="fy-detail-dl mb-0" id="fy-period-view-content"></dl>
            </div>
        </div>
    </div>
</div>

{{-- Period edit modal --}}
<div class="modal fade" id="fyPeriodEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold">@lang('accounting::financial_year.edit_period_title')</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="fy-period-edit-form">
                <div class="modal-body">
                    <input type="hidden" id="fy-edit-period-id" />
                    <div class="mb-5">
                        <label class="form-label fw-semibold required">@lang('accounting::financial_year.col_period_name')</label>
                        <input type="text" class="form-control form-control-solid" id="fy-edit-period-name"
                            maxlength="80" required />
                    </div>
                    <div class="row g-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold required">@lang('accounting::financial_year.start_date')</label>
                            <input type="text" class="form-control form-control-solid" id="fy-edit-period-start"
                                required />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold required">@lang('accounting::financial_year.end_date')</label>
                            <input type="text" class="form-control form-control-solid" id="fy-edit-period-end"
                                required />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
