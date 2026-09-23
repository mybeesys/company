@php
    $localeAr = app()->getLocale() === 'ar';
    $periodCount = count($comparisonTable['periods'] ?? []);
@endphp

<div class="bs-table-card">
    <div class="bs-table-scroll">
        <table class="table table-sm table-hover mb-0" id="balance-sheet-comparison-table">
            <thead>
                <tr>
                    <th style="min-width: 34%">@lang('accounting::lang.account_name')</th>
                    @foreach ($comparisonTable['periods'] as $period)
                        <th class="text-end" style="min-width: {{ max(10, (int) floor(40 / max(1, $periodCount))) }}%">
                            <span>{{ $period['label'] }}</span>
                            <span class="d-block small text-muted fw-normal">
                                @lang('accounting::lang.bs_as_at'): {{ $period['end_date'] }}
                            </span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($comparisonTable['rows'] as $row)
                    @if(($row['type'] ?? '') === 'section')
                        <tr class="bs-main-section">
                            <td colspan="{{ $periodCount + 1 }}">{{ $row['label'] }}</td>
                        </tr>
                    @elseif(($row['type'] ?? '') === 'subsection')
                        <tr class="bs-subsection">
                            <td colspan="{{ $periodCount + 1 }}">{{ $row['label'] }}</td>
                        </tr>
                    @elseif(($row['type'] ?? '') === 'account')
                        @php
                            $label = $localeAr ? ($row['name_ar'] ?? '') : ($row['name_en'] ?? '');
                            $depth = (int) ($row['depth'] ?? 0);
                            $hasChildren = ! empty($row['has_children']);
                        @endphp
                        <tr class="{{ $hasChildren ? 'bs-parent-row' : '' }}"
                            data-account-id="{{ $row['account_id'] }}"
                            @if(! empty($row['parent_account_id'])) data-parent-id="{{ $row['parent_account_id'] }}" @endif>
                            <td>
                                <div style="padding-inline-start: {{ $depth * 1.85 }}rem;">
                                    @if($hasChildren)
                                        <button type="button" class="btn btn-sm btn-link p-0 me-1" data-bs-toggle-account="{{ $row['account_id'] }}">
                                            <i class="fa fa-chevron-down fa-xs"></i>
                                        </button>
                                    @endif
                                    <span class="text-muted small">{{ $row['gl_code'] ?? '' }}</span>
                                    <span>{{ $label }}</span>
                                </div>
                            </td>
                            @foreach ($comparisonTable['periods'] as $period)
                                @php $amount = (float) ($row['amounts'][$period['key']] ?? 0); @endphp
                                @include('accounting::reports.partials.income-statement-amount', ['amount' => $amount])
                            @endforeach
                        </tr>
                    @elseif(($row['type'] ?? '') === 'summary')
                        <tr class="bs-{{ str_contains((string) ($row['row_class'] ?? ''), 'grand') ? 'grand' : 'subtotal' }}">
                            <td>{{ $row['label'] }}</td>
                            @foreach ($comparisonTable['periods'] as $period)
                                @php $amount = (float) ($row['amounts'][$period['key']] ?? 0); @endphp
                                @include('accounting::reports.partials.income-statement-amount', ['amount' => $amount])
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
