<div class="sub-invoice">
    <div class="sub-invoice__top">
        <div class="flex-grow-1">
            <div class="sub-invoice__id">{{ $invoice['id'] }}</div>
            <div class="sub-invoice__label">{{ $invoice['label'] }}</div>
            <div class="sub-invoice__meta">
                <span>{{ $periodLabel($invoice['period'] ?? null) }}</span>
                <span>
                    @lang('general::general.subscription_period_range'):
                    {{ $invoice['date'] ? \Illuminate\Support\Carbon::parse($invoice['date'])->format('Y-m-d') : '—' }}
                    @if (! empty($invoice['ended_at']))
                        → {{ \Illuminate\Support\Carbon::parse($invoice['ended_at'])->format('Y-m-d') }}
                    @endif
                </span>
                <span class="{{ $statusBadge[$invoice['status']] ?? 'sub-pill' }}">
                    {{ $statusLabels[$invoice['status']] ?? $invoice['status'] }}
                </span>
                @if (! empty($invoice['is_current']))
                    <span class="sub-pill sub-pill--ok">@lang('general::general.current')</span>
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="sub-invoice__amount mb-2">
                {{ $money($invoice['amount'], $invoice['currency'] ?? 'SAR') }}
            </div>
            <a href="{{ $invoice['print_url'] }}" target="_blank" class="sub-btn sub-btn--ghost py-2 px-3">
                <i class="fas fa-print"></i>
                @lang('general::general.subscription_print')
            </a>
        </div>
    </div>

    @if (! empty($invoice['line_items']))
        <div class="sub-lines">
            @foreach ($invoice['line_items'] as $line)
                <div class="sub-line">
                    <span>{{ $line['label'] ?? ($line['key'] ?? '—') }}</span>
                    <span class="fw-bold {{ (($line['total'] ?? 0) < 0) ? 'text-success' : '' }}">
                        {{ $money($line['total'] ?? 0, $invoice['currency'] ?? 'SAR') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
