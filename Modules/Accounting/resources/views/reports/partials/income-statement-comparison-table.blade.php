@php
    $localeAr = app()->getLocale() === 'ar';
    $periodCount = count($comparisonTable['periods'] ?? []);
    $colWidth = $periodCount > 0 ? max(12, (int) floor(45 / $periodCount)) : 12;
@endphp

<div class="is-table-card">
    <div class="is-table-scroll">
        <table class="table table-sm table-hover mb-0 is-statement-table" id="income-statement-comparison-table">
            <thead>
                <tr>
                    <th style="min-width: 34%">@lang('accounting::lang.account_name')</th>
                    @foreach ($comparisonTable['periods'] as $period)
                        <th class="text-end" style="min-width: {{ max(10, (int) floor(40 / max(1, $periodCount))) }}%">
                            <span>{{ $period['label'] }}</span>
                            <span class="is-period-range">{{ $period['start_date'] }} — {{ $period['end_date'] }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($comparisonTable['rows'] as $row)
                    @if(($row['type'] ?? '') === 'section')
                        <tr class="is-section">
                            <td colspan="{{ $periodCount + 1 }}">{{ $row['label'] }}</td>
                        </tr>
                    @elseif(($row['type'] ?? '') === 'account')
                        @php
                            $label = $localeAr ? ($row['name_ar'] ?? '') : ($row['name_en'] ?? '');
                            $depth = (int) ($row['depth'] ?? 0);
                            $hasChildren = ! empty($row['has_children']);
                        @endphp
                        <tr class="is-account-row {{ $hasChildren ? 'is-parent-row' : '' }}"
                            data-account-id="{{ $row['account_id'] }}"
                            @if(! empty($row['parent_account_id'])) data-parent-id="{{ $row['parent_account_id'] }}" @endif>
                            <td>
                                <div class="is-account-label" style="padding-inline-start: {{ $depth * 1.1 }}rem;">
                                    @if($hasChildren)
                                        <button type="button" class="is-toggle-btn" data-toggle-account="{{ $row['account_id'] }}" aria-label="toggle">
                                            <i class="fa fa-chevron-down fa-xs"></i>
                                        </button>
                                    @else
                                        <span class="is-indent"></span>
                                    @endif
                                    <span class="is-gl-code">{{ $row['gl_code'] ?? '' }}</span>
                                    <span>{{ $label }}</span>
                                </div>
                            </td>
                            @foreach ($comparisonTable['periods'] as $period)
                                @php
                                    $amount = (float) ($row['amounts'][$period['key']] ?? 0);
                                @endphp
                                <td class="is-fin-amount text-end {{ $amount < 0 ? 'is-negative' : '' }}">
                                    @format_accounting_amount($amount)
                                </td>
                            @endforeach
                        </tr>
                    @elseif(($row['type'] ?? '') === 'summary')
                        @php
                            $summaryClass = $row['row_class'] ?? 'is-subtotal';
                            if (($row['data_key'] ?? '') === 'net_profit') {
                                $hasLoss = collect($row['amounts'] ?? [])->contains(fn ($amount) => (float) $amount < -0.0001);
                                $summaryClass = $hasLoss ? 'is-loss-row' : 'is-profit-row';
                            }
                        @endphp
                        <tr class="{{ $summaryClass }}">
                            <td>{{ $row['label'] }}</td>
                            @foreach ($comparisonTable['periods'] as $period)
                                @php
                                    $amount = (float) ($row['amounts'][$period['key']] ?? 0);
                                @endphp
                                <td class="is-fin-amount text-end {{ $amount < 0 ? 'is-negative' : '' }}">
                                    @format_accounting_amount($amount)
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
