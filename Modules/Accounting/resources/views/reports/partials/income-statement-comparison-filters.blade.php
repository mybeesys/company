@php
    $comparisonEnabled = (bool) ($isComparisonActive ?? false) || request()->boolean('comparison_enabled');
    $periodRows = [];

    if (! empty($comparisonPeriods)) {
        $periodRows = $comparisonPeriods;
    } elseif (is_array(request('periods'))) {
        foreach (request('periods') as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $periodRows[] = [
                'label' => $row['label'] ?? __('accounting::lang.is_compare_period_n', ['n' => (int) $index + 1]),
                'start_date' => $row['start_date'] ?? '',
                'end_date' => $row['end_date'] ?? '',
            ];
        }
    }

    while (count($periodRows) < 2) {
        $index = count($periodRows);
        $periodRows[] = [
            'label' => match ($index) {
                0 => __('accounting::lang.is_compare_period_first'),
                1 => __('accounting::lang.is_compare_period_second'),
                2 => __('accounting::lang.is_compare_period_third'),
                3 => __('accounting::lang.is_compare_period_fourth'),
                default => __('accounting::lang.is_compare_period_n', ['n' => $index + 1]),
            },
            'start_date' => $index === 0 ? request('start_date', $start_date) : '',
            'end_date' => $index === 0 ? request('end_date', $end_date) : '',
        ];
    }
@endphp

<div class="col-12">
    <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="comparison_enabled" name="comparison_enabled"
            value="1" @checked($comparisonEnabled)>
        <label class="form-check-label small fw-semibold" for="comparison_enabled">
            @lang('accounting::lang.is_compare_enable')
        </label>
    </div>
</div>

<div id="is-comparison-panel" class="col-12 {{ $comparisonEnabled ? '' : 'd-none' }}">
    <div class="is-comparison-panel border rounded p-3 bg-light-subtle">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <span class="small fw-semibold text-muted">@lang('accounting::lang.is_compare_periods_title')</span>
            <button type="button" class="btn btn-sm btn-light-primary" id="isAddComparisonPeriod">
                <i class="fa fa-plus"></i> @lang('accounting::lang.is_compare_add_period')
            </button>
        </div>

        <div id="isComparisonPeriodRows" class="d-flex flex-column gap-2">
            @foreach ($periodRows as $index => $period)
                <div class="row g-2 align-items-end is-comparison-period-row" data-period-index="{{ $index }}">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small mb-1">@lang('accounting::lang.is_compare_period_label')</label>
                        <input type="text" name="periods[{{ $index }}][label]" class="form-control form-control-sm"
                            value="{{ $period['label'] ?? '' }}" placeholder="@lang('accounting::lang.is_compare_period_label')">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small mb-1">@lang('accounting::lang.from_date')</label>
                        <input type="date" name="periods[{{ $index }}][start_date]" class="form-control form-control-sm is-period-start"
                            value="{{ $period['start_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small mb-1">@lang('accounting::lang.to_date')</label>
                        <input type="date" name="periods[{{ $index }}][end_date]" class="form-control form-control-sm is-period-end"
                            value="{{ $period['end_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2 col-lg-1">
                        <button type="button" class="btn btn-sm btn-light-danger w-100 is-remove-period" @disabled($index < 2)>
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-muted small mb-0 mt-2">@lang('accounting::lang.is_compare_hint')</p>
    </div>
</div>
