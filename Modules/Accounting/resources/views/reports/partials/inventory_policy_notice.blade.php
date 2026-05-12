@php
    $policy = \Modules\General\Models\Setting::getInventoryTrackingPolicy();
    $isPeriodic = $policy === 'periodic';
@endphp

<div class="alert {{ $isPeriodic ? 'alert-warning' : 'alert-info' }} mb-5">
    <strong>@lang('accounting::lang.inventory_policy_label'):</strong>
    {{ $isPeriodic ? __('accounting::lang.inventory_policy_periodic') : __('accounting::lang.inventory_policy_perpetual') }}
    <span class="mx-2">|</span>
    {{ $isPeriodic ? __('accounting::lang.inventory_policy_periodic_report_note') : __('accounting::lang.inventory_policy_perpetual_report_note') }}
</div>
