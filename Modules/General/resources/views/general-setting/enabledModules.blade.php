@php
    $gate = app(\App\Services\EntitlementGate::class);
    $state = $gate->forCompany();
    $moduleKeys = $state['legacy'] ? [] : ($state['modules'] ?? []);
    $labels = $subscriptionModuleLabels ?? [];
@endphp

<div class="card card-flush border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-6">
            <div>
                <h3 class="fs-4 fw-bold text-gray-900 mb-1">@lang('general::general.subscription_modules')</h3>
                <div class="text-muted fs-7">@lang('general::general.subscription_modules_hint')</div>
            </div>
            <a href="{{ url('/subscription') }}" class="btn btn-sm btn-warning">
                @lang('general::general.manage_subscription')
            </a>
        </div>

        @if ($state['legacy'])
            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5">
                <div class="fw-semibold text-gray-700">
                    @lang('general::general.subscription_modules_legacy')
                </div>
            </div>
        @elseif (count($moduleKeys) === 0)
            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-5">
                <div class="fw-semibold text-gray-700">
                    @lang('general::general.subscription_modules_empty')
                </div>
            </div>
        @else
            <div class="d-flex flex-wrap gap-3 mb-8">
                @foreach ($moduleKeys as $key)
                    <span class="badge badge-light-primary fs-7 fw-bold px-4 py-3">
                        {{ $labels[$key] ?? str_replace('_', ' ', $key) }}
                    </span>
                @endforeach
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="border border-dashed border-gray-300 rounded p-5 h-100">
                        <div class="text-muted fs-7 mb-1">@lang('general::general.quota_employees')</div>
                        <div class="fs-2 fw-bold text-gray-900">{{ $state['employees_quota'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border border-dashed border-gray-300 rounded p-5 h-100">
                        <div class="text-muted fs-7 mb-1">@lang('general::general.quota_branches')</div>
                        <div class="fs-2 fw-bold text-gray-900">{{ $state['establishments_quota'] ?? '—' }}</div>
                    </div>
                </div>
                @if (in_array('digital_screens', $moduleKeys, true))
                    <div class="col-md-4">
                        <div class="border border-dashed border-gray-300 rounded p-5 h-100">
                            <div class="text-muted fs-7 mb-1">@lang('general::general.quota_screen_devices')</div>
                            <div class="fs-2 fw-bold text-gray-900">{{ $state['screen_devices_quota'] ?? 0 }}</div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
