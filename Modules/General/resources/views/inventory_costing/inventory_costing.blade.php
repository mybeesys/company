<div class="tab-pane fade show" id="inventory_costing_tab" role="tabpanel">
    <div class="container">
        <form id="update-prefix" method="POST" action="{{ route('update-inventory-costing-method') }}">
            @csrf

            <div class="row my-5">
                <div class="col-lg-6 col-md-8 mb-5">
                    <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <label class="fs-6 fw-semibold mb-2">
                            @lang('general::general.inventory_costing_method')
                        </label>
                    </div>
                    <select class="form-select select-2 form-select-solid kt_ecommerce_select2_account"
                        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); max-width: 420px"
                        name="inventory_costing_method" id="inventory_costing_method">
                        <option value="average" {{ ($inventory_costing_method ?? '') == 'average' ? 'selected' : '' }}>
                            @lang('general::general.average')
                        </option>
                        <option value="fifo" {{ ($inventory_costing_method ?? '') == 'fifo' ? 'selected' : '' }}>
                            @lang('general::general.fifo')
                        </option>
                        <option value="lifo" {{ ($inventory_costing_method ?? '') == 'lifo' ? 'selected' : '' }}>
                            @lang('general::general.lifo')
                        </option>
                    </select>
                    <div class="form-text mt-2">@lang('general::general.inventory_costing_method_help')</div>
                </div>
            </div>
            <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px">
                @lang('messages.save')
            </button>
        </form>

        <div class="separator my-8"></div>

        <div class="card border border-warning border-dashed bg-light-warning">
            <div class="card-body">
                <h4 class="fw-bold text-gray-900 mb-2">@lang('general::general.inventory_costing_rebuild_title')</h4>
                <p class="text-muted fs-7 mb-4">@lang('general::general.inventory_costing_rebuild_help')</p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button type="button" class="btn btn-light-primary border" id="btnPreviewInventoryCosting">
                        <i class="bi bi-search me-1"></i>
                        @lang('general::general.inventory_costing_preview_button')
                    </button>
                </div>

                <div id="inventoryCostingPreviewLoading" class="d-none text-muted py-4">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    @lang('general::general.inventory_costing_preview_loading')
                </div>

                <div id="inventoryCostingPreviewError" class="alert alert-danger d-none"></div>

                <div id="inventoryCostingPreviewPanel" class="d-none">
                    <div class="mb-4">
                        <div class="fw-semibold mb-2">@lang('general::general.inventory_costing_preview_method')</div>
                        <span class="badge badge-light-primary fs-7" id="previewMethodLabel">—</span>
                    </div>

                    <div id="previewIssuesWrap" class="mb-4 d-none">
                        <div class="fw-semibold mb-2">@lang('general::general.inventory_costing_preview_issues')</div>
                        <ul class="list-unstyled mb-0" id="previewIssuesList"></ul>
                    </div>

                    <div class="row g-3 mb-4" id="previewSummaryCards"></div>

                    <div id="previewDiscrepanciesWrap" class="d-none">
                        <div class="fw-semibold mb-2">@lang('general::general.inventory_costing_preview_details')</div>
                        <div class="table-responsive border rounded bg-white">
                            <table class="table table-sm table-row-bordered align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>@lang('report::fields.product_name')</th>
                                        <th>@lang('report::fields.establishment_name')</th>
                                        <th>@lang('general::general.inventory_costing_preview_problem')</th>
                                        <th class="text-end">@lang('general::general.inventory_costing_current')</th>
                                        <th class="text-end">@lang('general::general.inventory_costing_expected')</th>
                                        <th class="text-end">@lang('general::general.inventory_costing_diff')</th>
                                    </tr>
                                </thead>
                                <tbody id="previewDiscrepanciesBody"></tbody>
                            </table>
                        </div>
                        <div class="text-muted fs-8 mt-2 d-none" id="previewTruncatedNote"></div>
                    </div>

                    <div id="previewNoIssues" class="alert alert-success d-none mb-4">
                        @lang('general::general.inventory_costing_preview_no_issues')
                    </div>

                    <form method="POST" action="{{ route('rebuild-inventory-costing') }}" id="inventoryCostingRebuildForm" class="d-none mt-4 border-top pt-4">
                        @csrf
                        <input type="hidden" name="preview_token" id="previewTokenInput" value="">
                        <div class="alert alert-warning py-3 mb-4">
                            @lang('general::general.inventory_costing_rebuild_final_warning')
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_rebuild" value="1" id="confirm_rebuild" required>
                            <label class="form-check-label" for="confirm_rebuild">
                                @lang('general::general.inventory_costing_rebuild_acknowledge')
                            </label>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="confirm_rebuild_final" value="1" id="confirm_rebuild_final" required>
                            <label class="form-check-label" for="confirm_rebuild_final">
                                @lang('general::general.inventory_costing_rebuild_acknowledge_final')
                            </label>
                        </div>
                        <button type="submit" class="btn btn-warning" id="btnExecuteRebuild">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            @lang('general::general.inventory_costing_rebuild_execute_button')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

        <script>
            (function() {
                const previewUrl = @json(route('preview-inventory-costing-rebuild'));
                const labels = {
                    current: @json(__('general::general.inventory_costing_current')),
                    expected: @json(__('general::general.inventory_costing_expected')),
                    qty: @json(__('report::fields.quantity')),
                    avg: @json(__('accounting::lang.cost_price')),
                    value: @json(__('general::general.inventory_costing_stock_value')),
                    truncated: @json(__('general::general.inventory_costing_preview_truncated')),
                    confirmExecute: @json(__('general::general.inventory_costing_rebuild_confirm_final')),
                    previewFailed: @json(__('general::general.inventory_costing_preview_failed')),
                };

                const $btn = $('#btnPreviewInventoryCosting');
                const $loading = $('#inventoryCostingPreviewLoading');
                const $error = $('#inventoryCostingPreviewError');
                const $panel = $('#inventoryCostingPreviewPanel');
                const $form = $('#inventoryCostingRebuildForm');

                function esc(s) {
                    return $('<div>').text(s ?? '').html();
                }

                function renderPreview(data) {
                    $panel.removeClass('d-none');
                    $('#previewMethodLabel').text(data.method_label || data.method || '—');

                    const $issuesWrap = $('#previewIssuesWrap');
                    const $issuesList = $('#previewIssuesList');
                    $issuesList.empty();
                    if (data.issues && data.issues.length) {
                        $issuesWrap.removeClass('d-none');
                        data.issues.forEach(function(issue) {
                            const cls = issue.severity === 'error' ? 'text-danger' : (issue.severity === 'warning' ? 'text-warning' : 'text-muted');
                            $issuesList.append('<li class="mb-2 ' + cls + '"><i class="bi bi-dot"></i> ' + esc(issue.message) + '</li>');
                        });
                    } else {
                        $issuesWrap.addClass('d-none');
                    }

                    const summary = data.summary || {};
                    $('#previewSummaryCards').html(
                        '<div class="col-md-3"><div class="border rounded p-3 bg-white"><div class="text-muted fs-8">' + esc(@json(__('general::general.inventory_costing_disc_count'))) + '</div><div class="fw-bold fs-4">' + esc(summary.discrepancy_count ?? 0) + '</div></div></div>' +
                        '<div class="col-md-3"><div class="border rounded p-3 bg-white"><div class="text-muted fs-8">' + esc(@json(__('general::general.inventory_costing_qty_mismatch_count'))) + '</div><div class="fw-bold fs-4">' + esc(summary.qty_mismatch_count ?? 0) + '</div></div></div>' +
                        '<div class="col-md-3"><div class="border rounded p-3 bg-white"><div class="text-muted fs-8">' + esc(@json(__('general::general.inventory_costing_stored_products'))) + '</div><div class="fw-bold fs-4">' + esc(summary.stored_products ?? 0) + '</div></div></div>' +
                        '<div class="col-md-3"><div class="border rounded p-3 bg-white"><div class="text-muted fs-8">' + esc(@json(__('general::general.inventory_costing_historical_movements'))) + '</div><div class="fw-bold fs-4">' + esc(summary.historical_movements ?? 0) + '</div></div></div>'
                    );

                    const rows = data.discrepancies || [];
                    const $body = $('#previewDiscrepanciesBody');
                    $body.empty();

                    if (rows.length === 0) {
                        $('#previewDiscrepanciesWrap').addClass('d-none');
                        $('#previewNoIssues').removeClass('d-none');
                    } else {
                        $('#previewNoIssues').addClass('d-none');
                        $('#previewDiscrepanciesWrap').removeClass('d-none');
                        rows.forEach(function(row) {
                            const problems = (row.issue_labels || []).join(' · ');
                            const current = labels.qty + ': ' + (row.current_qty ?? '—') + '<br>' + labels.avg + ': ' + (row.current_avg_cost ?? '—') + '<br>' + labels.value + ': ' + (row.current_stock_value ?? '—');
                            const expected = labels.qty + ': ' + (row.expected_qty ?? '—') + '<br>' + labels.avg + ': ' + (row.expected_avg_cost ?? '—') + '<br>' + labels.value + ': ' + (row.expected_stock_value ?? '—');
                            const diff = labels.qty + ': ' + (row.qty_diff ?? '—');
                            $body.append('<tr><td>' + esc(row.product_name) + '</td><td>' + esc(row.establishment_name) + '</td><td><span class="badge badge-light-warning">' + esc(problems) + '</span></td><td class="text-end small">' + current + '</td><td class="text-end small">' + expected + '</td><td class="text-end small fw-semibold text-danger">' + diff + '</td></tr>');
                        });
                    }

                    if (data.discrepancies_truncated > 0) {
                        $('#previewTruncatedNote').removeClass('d-none').text(labels.truncated.replace(':count', data.discrepancies_truncated));
                    } else {
                        $('#previewTruncatedNote').addClass('d-none');
                    }

                    if (data.can_rebuild && data.preview_token) {
                        $('#previewTokenInput').val(data.preview_token);
                        $form.removeClass('d-none');
                    } else {
                        $form.addClass('d-none');
                    }
                }

                $btn.on('click', function() {
                    $error.addClass('d-none').text('');
                    $loading.removeClass('d-none');
                    $panel.addClass('d-none');
                    $form.addClass('d-none');

                    $.get(previewUrl)
                        .done(function(res) {
                            if (!res.success) {
                                $error.removeClass('d-none').text(res.message || labels.previewFailed);
                                return;
                            }
                            renderPreview(res.data);
                        })
                        .fail(function(xhr) {
                            const msg = xhr.responseJSON?.message || labels.previewFailed;
                            $error.removeClass('d-none').text(msg);
                        })
                        .always(function() {
                            $loading.addClass('d-none');
                        });
                });

                $form.on('submit', function(e) {
                    if (!confirm(labels.confirmExecute)) {
                        e.preventDefault();
                    }
                });
            })();
        </script>
