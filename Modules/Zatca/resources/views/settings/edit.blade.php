@extends('layouts.app')

@section('title', __('zatca::lang.page_title'))

@section('css')
@include('zatca::partials.ui-styles')
@endsection

@section('content')
@php
    $statusLabel = match ($setting->status) {
        'configured' => __('zatca::lang.status_configured'),
        'failed' => __('zatca::lang.status_failed'),
        default => __('zatca::lang.status_pending'),
    };
    $activeTab = $activeTab ?? old('active_tab', session('active_tab', request('tab', 'connection')));
    if (! in_array($activeTab, ['connection', 'operations'], true)) {
        $activeTab = 'connection';
    }
    $canSettingsShow = $canSettingsShow ?? false;
    $canOperationsShow = $canOperationsShow ?? false;
    $canSettingsUpdate = $canSettingsUpdate ?? false;
    $canOperationsUpdate = $canOperationsUpdate ?? false;
    $canRegenerate = $canRegenerate ?? false;
    $canPurgeSandbox = $canPurgeSandbox ?? false;
    $canEinvoicingShow = $canEinvoicingShow ?? false;
@endphp

<div class="container-fluid zatca-settings py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="fs-2 fw-bold mb-1">{{ __('zatca::lang.page_title') }}</h1>
            <p class="text-muted mb-0">{{ __('zatca::lang.page_subtitle') }}</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="z-status-row">
                <span class="text-muted">{{ __('zatca::lang.status') }}:</span>
                <span class="badge {{ $setting->statusBadgeClass() }} fs-7 px-3 py-2">{{ $statusLabel }}</span>
                <span class="text-muted small">
                    {{ __('zatca::lang.last_generated_at') }}:
                    {{ $setting->credentials_generated_at?->format('Y-m-d H:i') ?? __('zatca::lang.never') }}
                </span>
            </div>
            @if ($canEinvoicingShow)
                <a href="{{ route('zatca.einvoicing.index') }}" class="btn btn-sm btn-light-primary">
                    <i class="fa fa-file-invoice me-1"></i>
                    {{ __('zatca::lang.einvoicing_page_title') }}
                </a>
            @endif
        </div>
    </div>

    @include('zatca::partials.sync-feedback')

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
            @if ($canSettingsShow)
                <li class="nav-item" role="presentation">
                    <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'connection' ? 'active' : '' }}"
                       href="{{ route('zatca.settings.edit', ['tab' => 'connection']) }}">
                        {{ __('zatca::lang.tab_connection') }}
                    </a>
                </li>
            @endif
            @if ($canOperationsShow)
                <li class="nav-item" role="presentation">
                    <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'operations' ? 'active' : '' }}"
                       href="{{ route('zatca.settings.edit', ['tab' => 'operations']) }}">
                        {{ __('zatca::lang.tab_operations') }}
                    </a>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            @if ($canSettingsShow)
                <div class="tab-pane fade {{ $activeTab === 'connection' ? 'show active' : '' }}"
                     id="zatca_connection_tab"
                     role="tabpanel">
                    @include('zatca::settings.partials.connection-settings')
                </div>
            @endif

            @if ($canOperationsShow)
                <div class="tab-pane fade {{ $activeTab === 'operations' ? 'show active' : '' }}"
                     id="zatca_operations_tab"
                     role="tabpanel">
                    @include('zatca::settings.partials.operations')
                </div>
            @endif
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

    const zatcaSandboxSample = {
        zatca_environment: 'local',
        seller_name: 'Maximum Speed Tech Supply LTD',
        organization_name: 'Maximum Speed Tech Supply LTD',
        vat_number: '399999999900003',
        commercial_registration_number: '2252039485',
        organization_unit: '3999999999',
        business_category: 'Supply activities',
        invoice_type: '1100',
        country_code: 'SA',
        street_name: 'King Abdulaziz Road',
        building_number: '1234',
        plot_identification: '1234',
        city: 'Riyadh',
        district: 'Assuwayriqiyah',
        postal_code: '11111',
        email_address: '',
        otp: '',
    };

    const zatcaCompanyDefaults = @json($companyDefaults['values'] ?? []);

    function zatcaFillFields(sample, options) {
        options = options || {};
        const onlyEmpty = !!options.onlyEmpty;
        Object.keys(sample).forEach(function (key) {
            const $field = $('#' + key);
            if (!$field.length) return;
            const current = String($field.val() || '').trim();
            if (onlyEmpty && current !== '') return;
            $field.val(sample[key]).trigger('change');
        });
        syncAppKeyVisibility();
    }

    $('#btn-zatca-fill-company').on('click', function () {
        if (!zatcaCompanyDefaults || !Object.keys(zatcaCompanyDefaults).length) {
            return;
        }
        zatcaFillFields(zatcaCompanyDefaults, { onlyEmpty: false });
        if (typeof toastr !== 'undefined') {
            toastr.success(@json(__('zatca::lang.fill_from_company_done')));
        } else {
            alert(@json(__('zatca::lang.fill_from_company_done')));
        }
    });

    $('#btn-zatca-fill-sandbox').on('click', function () {
        zatcaFillFields(zatcaSandboxSample, { onlyEmpty: false });
        if (typeof toastr !== 'undefined') {
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

    $(document).on('click', '[data-zatca-focus]', function () {
        const selector = String($(this).data('zatca-focus') || '');
        if (!selector) return;

        const $target = $(selector);
        if (!$target.length) return;

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
