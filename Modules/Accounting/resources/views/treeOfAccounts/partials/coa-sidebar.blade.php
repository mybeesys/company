<div class="coa-sidebar-compact">
    <div class="card card-flush coa-panel coa-panel-compact">
        <div class="card-header border-0 pt-4 pb-0 px-4">
            <h3 class="card-title fw-bold text-gray-800 fs-6 mb-0">
                <i class="ki-outline ki-chart-simple-2 fs-5 text-primary me-2"></i>
                @lang('accounting::lang.coa_primary_type_balances')
            </h3>
        </div>
        <div class="card-body pt-3 pb-4 px-4">
            <div class="d-flex flex-column gap-2">
                @foreach ($primaryTypeSummary ?? [] as $typeKey => $row)
                    <div class="coa-type-summary-item coa-type-summary-item-compact">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="fw-semibold text-gray-700 fs-7">{{ $row['label'] }}</span>
                            <span class="fw-bold text-gray-900 fs-7">@format_currency($row['balance'])</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
