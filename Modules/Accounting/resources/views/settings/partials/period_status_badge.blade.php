@php
    $rawStatus = $status ?? (isset($period) ? $period->status : 'open');
    $displayKey = in_array($rawStatus, ['closed', 'closing', 'upcoming'], true) ? 'closed' : 'open';
@endphp
<span class="badge fy-period-badge fy-period-badge-{{ $displayKey }} fw-semibold px-3 py-2">
    @lang('accounting::financial_year.period_status_'.$displayKey)
</span>
