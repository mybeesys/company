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
    if (! in_array($activeTab, ['connection', 'send'], true)) {
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

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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
                   data-bs-toggle="tab"
                   href="#zatca_connection_tab"
                   role="tab">
                    {{ __('zatca::lang.tab_connection') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'send' ? 'active' : '' }}"
                   data-bs-toggle="tab"
                   href="#zatca_send_sell_tab"
                   role="tab">
                    {{ __('zatca::lang.tab_send_sell') }}
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

    $('a[data-bs-toggle="tab"][href="#zatca_send_sell_tab"]').on('shown.bs.tab', function () {
        initSelect2('#zatca_send_sell_tab');
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

    // Sell invoice sync table
    const $syncForm = $('#zatca-sync-sell-form');
    const $selectAll = $('#zatca-select-all');
    const $bulkBtn = $('#zatca-bulk-sync-btn');
    const $selectedCount = $('#zatca-selected-count');

    function updateSelectedCount() {
        const count = $syncForm.find('.zatca-row-check:checked').length;
        $selectedCount.text(count);
        $bulkBtn.prop('disabled', count === 0);
        const total = $syncForm.find('.zatca-row-check').length;
        $selectAll.prop('checked', total > 0 && count === total);
        $selectAll.prop('indeterminate', count > 0 && count < total);
    }

    $selectAll.on('change', function () {
        $syncForm.find('.zatca-row-check').prop('checked', this.checked);
        updateSelectedCount();
    });

    $syncForm.on('change', '.zatca-row-check', updateSelectedCount);

    $syncForm.on('click', '.zatca-sync-one-btn', function () {
        const id = String($(this).data('transaction-id'));
        $syncForm.find('.zatca-row-check').prop('checked', false);
        $syncForm.find('.zatca-row-check[value="' + id + '"]').prop('checked', true);
        updateSelectedCount();
        $syncForm.trigger('submit');
    });

    $syncForm.on('submit', function (e) {
        if ($syncForm.find('.zatca-row-check:checked').length === 0) {
            e.preventDefault();
            return false;
        }
    });

    updateSelectedCount();
})();
</script>
@endsection
