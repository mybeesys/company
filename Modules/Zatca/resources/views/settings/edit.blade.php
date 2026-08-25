@extends('layouts.app')

@section('title', __('zatca::lang.page_title'))

@section('css')
<style>
    .zatca-settings {
        --z-border: #e8ecf1;
        --z-muted: #6b7280;
        --z-heading: #111827;
        --z-accent: #0f766e;
        --z-accent-soft: #ecfdf5;
        --z-radius: 12px;
        --z-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
    }
    .zatca-settings .z-card {
        border: 1px solid var(--z-border);
        border-radius: var(--z-radius);
        background: #fff;
        box-shadow: var(--z-shadow);
        margin-bottom: 1.25rem;
    }
    .zatca-settings .z-card-header {
        padding: 1.15rem 1.35rem;
        border-bottom: 1px solid var(--z-border);
    }
    .zatca-settings .z-card-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--z-heading);
    }
    .zatca-settings .z-card-subtitle {
        margin: .35rem 0 0;
        font-size: .85rem;
        color: var(--z-muted);
    }
    .zatca-settings .z-card-body { padding: 1.35rem; }
    .zatca-settings .z-help { font-size: .8rem; color: var(--z-muted); margin-top: .35rem; }
    .zatca-settings .z-banner {
        border: 1px dashed #99f6e4;
        background: var(--z-accent-soft);
        border-radius: var(--z-radius);
        padding: 1rem 1.25rem;
        color: #115e59;
        margin-bottom: 1.25rem;
    }
    .zatca-settings .z-status-row {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1.5rem;
        align-items: center;
    }
    .zatca-settings label.required:after {
        content: " *";
        color: #dc2626;
    }
    #zatca_app_key_wrap { display: none; }
    #zatca_app_key_wrap.is-visible { display: block; }

    /* Setup readiness */
    .z-readiness {
        border: 1px solid var(--z-border);
        border-radius: var(--z-radius);
        background: linear-gradient(180deg, #fbfcfd 0%, #fff 48%);
        box-shadow: var(--z-shadow);
        overflow: hidden;
    }
    .z-readiness__toggle {
        width: 100%;
        border: 0;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .95rem 1.15rem;
        text-align: start;
        cursor: pointer;
    }
    .z-readiness__toggle:hover { background: rgba(15, 23, 42, .02); }
    .z-readiness__toggle-main {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }
    .z-readiness__mini-pct {
        flex: 0 0 auto;
        min-width: 52px;
        height: 36px;
        padding: 0 .7rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .9rem;
        color: var(--z-ready-color, #0f766e);
        background: color-mix(in srgb, var(--z-ready-color, #0f766e) 12%, #fff);
        border: 1px solid color-mix(in srgb, var(--z-ready-color, #0f766e) 28%, #fff);
    }
    .z-readiness__toggle-copy { min-width: 0; }
    .z-readiness__toggle-hint {
        display: block;
        font-size: .78rem;
        margin-top: .15rem;
    }
    .z-readiness__chevron {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #4b5563;
        transition: transform .2s ease;
        flex: 0 0 auto;
    }
    .z-readiness__toggle[aria-expanded="true"] .z-readiness__chevron {
        transform: rotate(180deg);
    }
    .z-readiness__hero {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 1.25rem;
        padding: .35rem 1.4rem 1.1rem;
        border-top: 1px solid var(--z-border);
    }
    @media (max-width: 767.98px) {
        .z-readiness__hero { grid-template-columns: 1fr; justify-items: center; text-align: center; }
    }
    .z-readiness__meter {
        position: relative;
        width: 120px;
        height: 120px;
    }
    .z-readiness__ring { width: 120px; height: 120px; transform: rotate(-90deg); }
    .z-readiness__ring-bg,
    .z-readiness__ring-fg {
        fill: none;
        stroke-width: 2.6;
    }
    .z-readiness__ring-bg { stroke: #eef2f6; }
    .z-readiness__ring-fg {
        stroke: var(--z-ready-color, #0f766e);
        stroke-linecap: round;
        transition: stroke-dasharray .4s ease;
    }
    .z-readiness__pct {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .z-readiness__pct-num {
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1;
        color: var(--z-heading);
    }
    .z-readiness__pct-label {
        font-size: .72rem;
        color: var(--z-muted);
        margin-top: .2rem;
    }
    .z-readiness__title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--z-heading);
    }
    .z-readiness__summary {
        color: #374151;
        margin: 0;
        font-size: .95rem;
        line-height: 1.55;
    }
    .z-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .75rem;
        font-size: .78rem;
        font-weight: 600;
        border: 1px solid transparent;
    }
    .z-chip--ok { background: #ecfdf3; color: #027a48; border-color: #abefc6; }
    .z-chip--warn { background: #fffaeb; color: #b54708; border-color: #fedf89; }
    .z-chip--muted { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }
    .z-readiness__missing-title {
        font-size: .8rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: .55rem;
    }
    .z-readiness__missing-list {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
    }
    .z-gap-pill {
        border: 1px solid #fecdd3;
        background: #fff1f2;
        color: #9f1239;
        border-radius: 999px;
        padding: .35rem .7rem;
        display: inline-flex;
        align-items: baseline;
        gap: .45rem;
        cursor: pointer;
        transition: .15s ease;
    }
    .z-gap-pill:hover {
        background: #ffe4e6;
        border-color: #fda4af;
        transform: translateY(-1px);
    }
    .z-gap-pill__label { font-size: .78rem; font-weight: 700; }
    .z-gap-pill__group { font-size: .68rem; opacity: .75; }
    .z-readiness__all-good {
        display: inline-flex;
        align-items: center;
        background: #ecfdf3;
        color: #027a48;
        border: 1px solid #abefc6;
        border-radius: 10px;
        padding: .65rem .9rem;
        font-weight: 600;
        font-size: .9rem;
    }
    .z-readiness__groups {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: .85rem;
        padding: 1rem 1.15rem 1.25rem;
    }
    .z-ready-group {
        border: 1px solid var(--z-border);
        border-radius: 10px;
        background: #fff;
        padding: .85rem .9rem;
    }
    .z-ready-group.is-complete { border-color: #abefc6; background: #f6fef9; }
    .z-ready-group.is-incomplete { border-color: #f9e2a8; }
    .z-ready-group__head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .5rem;
        margin-bottom: .65rem;
    }
    .z-ready-group__icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #374151;
        font-size: .8rem;
    }
    .z-ready-group.is-complete .z-ready-group__icon { background: #dcfae6; color: #027a48; }
    .z-ready-group__title { font-size: .86rem; font-weight: 700; color: var(--z-heading); }
    .z-ready-group__meta { font-size: .72rem; color: var(--z-muted); }
    .z-ready-group__items {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: .3rem;
    }
    .z-ready-item {
        width: 100%;
        border: 0;
        background: transparent;
        display: flex;
        gap: .55rem;
        text-align: start;
        padding: .35rem .2rem;
        border-radius: 8px;
        cursor: pointer;
    }
    .z-ready-item:hover { background: rgba(15, 23, 42, .03); }
    .z-ready-item__state {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 18px;
        margin-top: .1rem;
        font-size: .65rem;
    }
    .is-ok .z-ready-item__state { background: #dcfae6; color: #027a48; }
    .is-miss .z-ready-item__state { background: #fee4e2; color: #b42318; }
    .z-ready-item__label {
        display: block;
        font-size: .8rem;
        font-weight: 600;
        color: #111827;
        line-height: 1.3;
    }
    .z-ready-item__hint {
        display: block;
        font-size: .72rem;
        color: #9f1239;
        margin-top: .15rem;
        line-height: 1.35;
    }
    .zatca-field-flash {
        animation: zatcaFlash 1.2s ease;
        box-shadow: 0 0 0 3px rgba(15, 118, 110, .25) !important;
        border-color: #0f766e !important;
    }
    @keyframes zatcaFlash {
        0% { box-shadow: 0 0 0 0 rgba(15, 118, 110, .45); }
        100% { box-shadow: 0 0 0 3px rgba(15, 118, 110, 0); }
    }

    /* Sync feedback */
    .z-sync-feedback {
        border-radius: 12px;
        border: 1px solid #e8ecf1;
        background: #fff;
        box-shadow: 0 4px 24px rgba(15, 23, 42, .06);
        overflow: hidden;
    }
    .z-sync-feedback--error { border-color: #fecdca; }
    .z-sync-feedback--ok { border-color: #abefc6; }
    .z-sync-feedback__head {
        padding: 1rem 1.2rem;
        border-bottom: 1px solid #eef2f6;
        background: #fbfcfd;
    }
    .z-sync-feedback__title {
        font-weight: 800;
        color: #111827;
        font-size: 1rem;
    }
    .z-sync-feedback__summary {
        color: #6b7280;
        font-size: .9rem;
        margin-top: .2rem;
    }
    .z-sync-item {
        padding: 1rem 1.2rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .z-sync-item:last-child { border-bottom: 0; }
    .z-sync-item.is-fail { background: #fffaf9; }
    .z-sync-item.is-ok { background: #f6fef9; }
    .z-sync-item__top {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
        margin-bottom: .45rem;
    }
    .z-sync-item__summary {
        color: #374151;
        font-size: .92rem;
        margin-bottom: .55rem;
    }
    .z-sync-item__list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: .4rem;
    }
    .z-sync-item__list li {
        display: flex;
        gap: .5rem;
        align-items: flex-start;
        font-size: .85rem;
        line-height: 1.45;
        padding: .45rem .65rem;
        border-radius: 8px;
    }
    .z-sync-item__list--errors li {
        background: #fef3f2;
        color: #912018;
        border: 1px solid #fecdca;
    }
    .z-sync-item__list--warnings li {
        background: #fffaeb;
        color: #93370d;
        border: 1px solid #fedf89;
    }
    .z-sync-item__list code {
        font-size: .72rem;
        background: rgba(0,0,0,.06);
        padding: .1rem .35rem;
        border-radius: 4px;
        white-space: nowrap;
    }
    .z-sync-warnings {
        margin-top: .65rem;
    }
    .z-sync-warnings summary {
        cursor: pointer;
        color: #b54708;
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: .4rem;
    }
    .z-sync-row-error {
        color: #b42318;
        font-size: .78rem;
        line-height: 1.35;
        max-width: 260px;
        white-space: pre-line;
    }
</style>
@endsection

@section('content')
@php
    $statusLabel = match ($setting->status) {
        'configured' => __('zatca::lang.status_configured'),
        'failed' => __('zatca::lang.status_failed'),
        default => __('zatca::lang.status_pending'),
    };
    $activeTab = old('active_tab', session('active_tab', request('tab', 'connection')));
    if (! in_array($activeTab, ['connection', 'send', 'returns', 'operations'], true)) {
        $activeTab = 'connection';
    }
@endphp

<div class="container-fluid zatca-settings py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="fs-2 fw-bold mb-1">{{ __('zatca::lang.page_title') }}</h1>
            <p class="text-muted mb-0">{{ __('zatca::lang.page_subtitle') }}</p>
        </div>
        <div class="z-status-row">
            <span class="text-muted">{{ __('zatca::lang.status') }}:</span>
            <span class="badge {{ $setting->statusBadgeClass() }} fs-7 px-3 py-2">{{ $statusLabel }}</span>
            <span class="text-muted small">
                {{ __('zatca::lang.last_generated_at') }}:
                {{ $setting->credentials_generated_at?->format('Y-m-d H:i') ?? __('zatca::lang.never') }}
            </span>
        </div>
    </div>

    @if (session('sync_feedback'))
        @php $fb = session('sync_feedback'); @endphp
        <div class="z-sync-feedback {{ ($fb['failed'] ?? 0) > 0 ? 'z-sync-feedback--error' : 'z-sync-feedback--ok' }} mb-4">
            <div class="z-sync-feedback__head">
                <div>
                    <div class="z-sync-feedback__title">
                        {{ __('zatca::lang.sync_feedback_title') }}
                    </div>
                    <div class="z-sync-feedback__summary">
                        {{ __('zatca::lang.sync_batch_summary', [
                            'success' => $fb['success'] ?? 0,
                            'failed' => $fb['failed'] ?? 0,
                        ]) }}
                    </div>
                </div>
            </div>
            @foreach (($fb['items'] ?? []) as $item)
                <div class="z-sync-item {{ ($item['ok'] ?? false) ? 'is-ok' : 'is-fail' }}">
                    <div class="z-sync-item__top">
                        <span class="fw-bold">{{ $item['ref'] ?? '—' }}</span>
                        <span class="badge {{ ($item['ok'] ?? false) ? 'badge-light-success' : 'badge-light-danger' }}">
                            {{ ($item['ok'] ?? false) ? __('zatca::lang.sync_status_synced') : __('zatca::lang.sync_status_failed') }}
                        </span>
                        @if (! empty($item['reporting_status']))
                            @php
                                $statusLabel = $item['reporting_status_label'] ?? null;
                                if (! $statusLabel) {
                                    $statusKey = 'zatca::lang.reporting_status_'.strtolower((string) $item['reporting_status']);
                                    $translated = __($statusKey);
                                    $statusLabel = $translated !== $statusKey
                                        ? $translated
                                        : $item['reporting_status'];
                                }
                            @endphp
                            <span class="badge badge-light">{{ $statusLabel }}</span>
                        @endif
                    </div>
                    <div class="z-sync-item__summary">{{ $item['summary'] ?? '' }}</div>
                    @if (! empty($item['errors']))
                        <ul class="z-sync-item__list z-sync-item__list--errors">
                            @foreach ($item['errors'] as $err)
                                @php
                                    $errCode = strtoupper((string) ($err['code'] ?? ''));
                                    $showCode = $errCode !== ''
                                        && ! in_array($errCode, ['EXCEPTION', 'ERROR', 'CONNECTION', 'AUTH', 'ZATCA'], true);
                                @endphp
                                <li>
                                    @if ($showCode)
                                        <code>{{ $err['code'] }}</code>
                                    @endif
                                    <span>{{ $err['message'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($item['warnings']))
                        <details class="z-sync-warnings">
                            <summary>{{ __('zatca::lang.sync_warnings_count', ['count' => count($item['warnings'])]) }}</summary>
                            <ul class="z-sync-item__list z-sync-item__list--warnings">
                                @foreach ($item['warnings'] as $warn)
                                    <li>
                                        @if (! empty($warn['code']))
                                            <code>{{ $warn['code'] }}</code>
                                        @endif
                                        <span>{{ $warn['message'] ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($setting->status === 'failed' && $setting->last_error && $activeTab === 'connection')
        <div class="alert alert-warning">
            <strong>{{ __('zatca::lang.last_error') }}:</strong> {{ $setting->last_error }}
        </div>
    @endif

    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'connection' ? 'active' : '' }}"
                   href="{{ route('zatca.settings.edit', ['tab' => 'connection']) }}">
                    {{ __('zatca::lang.tab_connection') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'send' ? 'active' : '' }}"
                   href="{{ route('zatca.settings.edit', array_filter(['tab' => 'send', 'zatca_status' => request('zatca_status')])) }}">
                    {{ __('zatca::lang.tab_send_sell') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'returns' ? 'active' : '' }}"
                   href="{{ route('zatca.settings.edit', array_filter(['tab' => 'returns', 'zatca_return_status' => request('zatca_return_status')])) }}">
                    {{ __('zatca::lang.tab_send_returns') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'operations' ? 'active' : '' }}"
                   href="{{ route('zatca.settings.edit', ['tab' => 'operations']) }}">
                    {{ __('zatca::lang.tab_operations') }}
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'connection' ? 'show active' : '' }}"
                 id="zatca_connection_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.connection-settings')
            </div>

            <div class="tab-pane fade {{ $activeTab === 'send' ? 'show active' : '' }}"
                 id="zatca_send_sell_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.send-sell-invoice')
            </div>

            <div class="tab-pane fade {{ $activeTab === 'returns' ? 'show active' : '' }}"
                 id="zatca_send_returns_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.send-sell-returns')
            </div>

            <div class="tab-pane fade {{ $activeTab === 'operations' ? 'show active' : '' }}"
                 id="zatca_operations_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.operations')
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const select2Options = {
        width: '100%',
        dir: document.documentElement.getAttribute('dir') || 'ltr',
    };

    function initSelect2(scope) {
        $(scope).find('select.select-2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2(select2Options);
        });
    }

    initSelect2('#zatca_connection_tab');
    if ($('#zatca_send_sell_tab').hasClass('active')) {
        initSelect2('#zatca_send_sell_tab');
    }
    if ($('#zatca_send_returns_tab').hasClass('active')) {
        initSelect2('#zatca_send_returns_tab');
    }

    $('a[data-bs-toggle="tab"][href="#zatca_send_sell_tab"]').on('shown.bs.tab', function () {
        initSelect2('#zatca_send_sell_tab');
    });
    $('a[data-bs-toggle="tab"][href="#zatca_send_returns_tab"]').on('shown.bs.tab', function () {
        initSelect2('#zatca_send_returns_tab');
    });
    $('a[data-bs-toggle="tab"][href="#zatca_connection_tab"]').on('shown.bs.tab', function () {
        initSelect2('#zatca_connection_tab');
    });

    const $form = $('#zatca-settings-form');
    const $envSelect = $('#zatca_environment');
    const $appKeyWrap = $('#zatca_app_key_wrap');
    const $appKeyInput = $('#zatca_app_key');
    const $regenerateBtn = $('#btn-zatca-regenerate');

    function syncAppKeyVisibility() {
        const isProduction = $envSelect.val() === 'production';
        $appKeyWrap.toggleClass('is-visible', isProduction);
        $appKeyInput.prop('required', isProduction);
    }

    $envSelect.on('change', syncAppKeyVisibility);
    syncAppKeyVisibility();

    $regenerateBtn.on('click', function () {
        const form = $form.get(0);
        if (!form) return;

        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) {
            methodInput.remove();
        }
        form.setAttribute('action', @json(route('zatca.settings.regenerate')));
        form.setAttribute('method', 'POST');
        form.submit();
    });

    /**
     * Official Fatoora package sandbox sample (example.php Setting + Seller address).
     * OU uses 10-digit TIN (ZATCA rule) — package text "Riyadh Branch" is not accepted by the portal.
     */
    const zatcaSandboxSample = {
        zatca_environment: 'local',
        seller_name: 'Maximum Speed Tech Supply LTD',
        organization_name: 'Maximum Speed Tech Supply LTD',
        vat_number: '399999999900003',
        commercial_registration_number: '2252039485',
        organization_unit: '3999999999',
        business_category: 'Supply activities',
        country_code: 'SA',
        invoice_type: '1100',
        city: 'Riyadh',
        district: 'Assuwayriqiyah',
        building_number: '1234',
        postal_code: '12643',
        plot_identification: '1234',
        street_name: 'King Abdulaziz Road',
        email_address: 'Support@fatoorazatca.com',
        otp: '123456',
        common_name: 'TST-886431145-399999999900003',
        egs_serial_number: '1-TST|2-TST|3-ed22f1d8-e6a2-1118-9b58-d9a8f11e445f',
    };

    $('#btn-zatca-fill-sandbox').on('click', function () {
        const $connForm = $('#zatca-settings-form');
        if (!$connForm.length) return;

        Object.keys(zatcaSandboxSample).forEach(function (key) {
            const $field = $connForm.find('[name="' + key + '"]');
            if (!$field.length) return;

            $field.val(zatcaSandboxSample[key]);
            if ($field.hasClass('select-2') || $field.hasClass('select2-hidden-accessible')) {
                $field.trigger('change');
            }
        });

        $envSelect.trigger('change');

        if (window.toastr) {
            toastr.success(@json(__('zatca::lang.fill_sandbox_done')));
        } else {
            alert(@json(__('zatca::lang.fill_sandbox_done')));
        }

        const $otp = $('#otp');
        if ($otp.length) {
            $otp.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
            $otp.addClass('zatca-field-flash');
            setTimeout(function () { $otp.removeClass('zatca-field-flash'); }, 1200);
        }
    });

    // Sell invoice sync table
    const $syncForm = $('#zatca-sync-sell-form');
    const $selectAll = $('#zatca-select-all');
    const $bulkBtn = $('#zatca-bulk-sync-btn');
    const $selectedCount = $('#zatca-selected-count');

    function updateSelectedCount() {
        const $enabled = $syncForm.find('.zatca-row-check:not(:disabled)');
        const count = $enabled.filter(':checked').length;
        $selectedCount.text(count);
        $bulkBtn.prop('disabled', count === 0);
        const total = $enabled.length;
        $selectAll.prop('checked', total > 0 && count === total);
        $selectAll.prop('indeterminate', count > 0 && count < total);
        $selectAll.prop('disabled', total === 0);
    }

    $selectAll.on('change', function () {
        $syncForm.find('.zatca-row-check:not(:disabled)').prop('checked', this.checked);
        updateSelectedCount();
    });

    $syncForm.on('change', '.zatca-row-check', updateSelectedCount);

    $syncForm.on('click', '.zatca-sync-one-btn', function () {
        const id = String($(this).data('transaction-id'));
        $syncForm.find('.zatca-row-check:not(:disabled)').prop('checked', false);
        $syncForm.find('.zatca-row-check[value="' + id + '"]:not(:disabled)').prop('checked', true);
        updateSelectedCount();
        $syncForm.trigger('submit');
    });

    $syncForm.on('submit', function (e) {
        if ($syncForm.find('.zatca-row-check:checked:not(:disabled)').length === 0) {
            e.preventDefault();
            return false;
        }
    });

    updateSelectedCount();

    // Credit notes / sell-returns sync table
    const $returnForm = $('#zatca-sync-return-form');
    const $returnSelectAll = $('#zatca-return-select-all');
    const $returnBulkBtn = $('#zatca-bulk-return-sync-btn');
    const $returnSelectedCount = $('#zatca-return-selected-count');

    function updateReturnSelectedCount() {
        const $enabled = $returnForm.find('.zatca-return-row-check:not(:disabled)');
        const count = $enabled.filter(':checked').length;
        $returnSelectedCount.text(count);
        $returnBulkBtn.prop('disabled', count === 0);
        const total = $enabled.length;
        $returnSelectAll.prop('checked', total > 0 && count === total);
        $returnSelectAll.prop('indeterminate', count > 0 && count < total);
        $returnSelectAll.prop('disabled', total === 0);
    }

    $returnSelectAll.on('change', function () {
        $returnForm.find('.zatca-return-row-check:not(:disabled)').prop('checked', this.checked);
        updateReturnSelectedCount();
    });

    $returnForm.on('change', '.zatca-return-row-check', updateReturnSelectedCount);

    $returnForm.on('click', '.zatca-sync-return-one-btn', function () {
        const id = String($(this).data('transaction-id'));
        $returnForm.find('.zatca-return-row-check:not(:disabled)').prop('checked', false);
        $returnForm.find('.zatca-return-row-check[value="' + id + '"]:not(:disabled)').prop('checked', true);
        updateReturnSelectedCount();
        $returnForm.trigger('submit');
    });

    $returnForm.on('submit', function (e) {
        if ($returnForm.find('.zatca-return-row-check:checked:not(:disabled)').length === 0) {
            e.preventDefault();
            return false;
        }
    });

    updateReturnSelectedCount();

    // Focus / highlight missing readiness fields
    $(document).on('click', '[data-zatca-focus]', function () {
        const selector = String($(this).data('zatca-focus') || '');
        if (!selector) return;

        const $target = $(selector);
        if (!$target.length) return;

        const $connTab = $('a[data-bs-toggle="tab"][href="#zatca_connection_tab"]');
        if ($connTab.length && ! $('#zatca_connection_tab').hasClass('active')) {
            $connTab.tab('show');
        }

        const $body = $('#zatca-readiness-body');
        if ($body.length && ! $body.hasClass('show')) {
            const collapse = bootstrap.Collapse.getOrCreateInstance($body.get(0), { toggle: false });
            collapse.show();
        }

        setTimeout(function () {
            const el = $target.get(0);
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            $target.addClass('zatca-field-flash');
            if ($target.is('input, select, textarea')) {
                $target.trigger('focus');
            }
            setTimeout(function () {
                $target.removeClass('zatca-field-flash');
            }, 1300);
        }, 220);
    });

    // Readiness collapse: remember user preference
    (function initReadinessCollapse() {
        const root = document.getElementById('zatca-readiness');
        const body = document.getElementById('zatca-readiness-body');
        const toggle = document.getElementById('zatca-readiness-toggle');
        if (!root || !body || !toggle) return;

        const storageKey = 'zatca.readiness.expanded';
        const actionEl = toggle.querySelector('.z-readiness__toggle-action');
        const defaultExpanded = root.getAttribute('data-default-expanded') === '1';
        const saved = localStorage.getItem(storageKey);
        const shouldExpand = saved === null ? defaultExpanded : saved === '1';

        const collapse = bootstrap.Collapse.getOrCreateInstance(body, { toggle: false });
        if (shouldExpand) {
            collapse.show();
        } else {
            collapse.hide();
        }

        function syncActionLabel() {
            if (!actionEl) return;
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            actionEl.textContent = expanded
                ? actionEl.getAttribute('data-label-collapse')
                : actionEl.getAttribute('data-label-expand');
        }

        body.addEventListener('shown.bs.collapse', function () {
            toggle.setAttribute('aria-expanded', 'true');
            localStorage.setItem(storageKey, '1');
            syncActionLabel();
        });
        body.addEventListener('hidden.bs.collapse', function () {
            toggle.setAttribute('aria-expanded', 'false');
            localStorage.setItem(storageKey, '0');
            syncActionLabel();
        });

        syncActionLabel();
    })();
})();
</script>
@endsection
