{{-- ZATCA operations cards — UI only; all IDs / names / actions preserved --}}
<style>
    .zatca-ops-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        box-shadow: none;
        transition: box-shadow .2s ease, border-color .2s ease;
        overflow: hidden;
    }
    .zatca-ops-card:hover {
        box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .06);
        border-color: #dee2e6;
    }
    .zatca-ops-card .zatca-ops-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: rgba(var(--bs-primary-rgb), .1);
        color: var(--bs-primary);
        font-size: 1rem;
    }
    .zatca-ops-card .zatca-ops-icon.is-warning {
        background: rgba(var(--bs-warning-rgb), .15);
        color: var(--bs-warning);
    }
    .zatca-ops-card .zatca-ops-icon.is-danger {
        background: rgba(var(--bs-danger-rgb), .1);
        color: var(--bs-danger);
    }
    .zatca-ops-card .form-control:focus,
    .zatca-ops-card .form-select:focus {
        border-color: rgba(var(--bs-primary-rgb), .45);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .15);
    }
    .zatca-ops-card .form-check.form-switch {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .85rem;
        margin: 0 0 .75rem;
        min-height: auto;
        border: 1px solid #eef0f2;
        border-radius: .75rem;
        background: #fafbfc;
    }
    .zatca-ops-card .form-check.form-switch .form-check-input {
        float: none;
        margin: 0;
        margin-inline-start: 0;
        flex-shrink: 0;
        width: 2.5em;
        height: 1.3em;
        cursor: pointer;
        position: relative;
    }
    .zatca-ops-card .form-check.form-switch .form-check-label {
        cursor: pointer;
        color: #1a1a1a;
        font-size: .925rem;
        padding: 0;
        margin: 0;
        flex: 1 1 auto;
    }
    .zatca-ops-note {
        background: #f4f5f7;
        border: 1px solid #e5e7eb;
        border-radius: .75rem;
        padding: .9rem 1rem;
        font-size: .8125rem;
        line-height: 1.55;
        color: #1a1a1a !important;
        margin-bottom: 1rem;
    }
    .zatca-ops-note,
    .zatca-ops-note li,
    .zatca-ops-note p,
    .zatca-ops-note ol {
        color: #1a1a1a !important;
    }
    .zatca-ops-card .btn-danger-soft {
        color: var(--bs-danger);
        background-color: rgba(var(--bs-danger-rgb), .08);
        border: 1px solid rgba(var(--bs-danger-rgb), .25);
    }
    .zatca-ops-card .btn-danger-soft:hover {
        color: #fff;
        background-color: var(--bs-danger);
        border-color: var(--bs-danger);
    }
    .zatca-ops-stat {
        background: #f8f9fa;
        border-radius: .75rem;
        padding: .75rem 1rem;
        font-size: .875rem;
        color: #495057;
    }
</style>

<div class="row g-4 align-items-stretch">
    {{-- بوابة التطوير والتجربة --}}
    <div class="col-lg-4 d-flex">
        <div class="card zatca-ops-card h-100 w-100 d-flex flex-column border">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="zatca-ops-icon is-warning" aria-hidden="true">
                        <i class="fa fa-flask"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="fw-bold fs-6 text-dark mb-1">{{ __('zatca::lang.ops_sandbox_title') }}</h2>
                        <p class="text-muted fs-7 mb-0">{{ __('zatca::lang.ops_sandbox_subtitle') }}</p>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="zatca-ops-stat">
                        {{ __('zatca::lang.ops_sandbox_local_count', ['count' => $sandboxCounts['local'] ?? 0]) }}
                    </div>
                    <div class="zatca-ops-stat">
                        {{ __('zatca::lang.ops_sandbox_simulation_count', ['count' => $sandboxCounts['simulation'] ?? 0]) }}
                    </div>
                </div>

                <div class="mt-auto">
                    @if (in_array((string) $setting->zatca_environment, ['local', 'simulation'], true))
                        <form method="POST"
                              action="{{ route('zatca.settings.purge-sandbox') }}"
                              onsubmit="return confirm(@json(__('zatca::lang.ops_purge_confirm')));">
                            @csrf
                            <input type="hidden" name="confirm" value="1">
                            <input type="hidden" name="active_tab" value="operations">
                            <button type="submit" class="btn btn-danger-soft w-100 rounded-2">
                                <i class="fa fa-trash me-1"></i>
                                {{ __('zatca::lang.ops_purge_button') }}
                            </button>
                        </form>
                        <div class="zatca-ops-note mt-3 mb-0">
                            {{ __('zatca::lang.ops_purge_help') }}
                        </div>
                    @else
                        <div class="alert alert-warning mb-0 rounded-3 border-0">
                            {{ __('zatca::lang.ops_purge_production_blocked') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ترحيل تلقائي --}}
    <div class="col-lg-4 d-flex">
        <div class="card zatca-ops-card h-100 w-100 d-flex flex-column border">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="zatca-ops-icon" aria-hidden="true">
                        <i class="fa fa-sync-alt"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="fw-bold fs-6 text-dark mb-1">{{ __('zatca::lang.ops_autosync_title') }}</h2>
                        <p class="text-muted fs-7 mb-0">{{ __('zatca::lang.ops_autosync_subtitle') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('zatca.settings.operations') }}" id="zatca-ops-autosync-form" class="d-flex flex-column flex-grow-1">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="operations">
                    <input type="hidden" name="disable_discount" value="{{ old('disable_discount', $setting->disable_discount ? '1' : '0') }}">
                    <input type="hidden" name="disable_order_tax" value="{{ old('disable_order_tax', $setting->disable_order_tax ? '1' : '0') }}">
                    <input type="hidden" name="default_sales_discount" value="{{ old('default_sales_discount', $setting->default_sales_discount ?? 0) }}">
                    <input type="hidden" name="lock_synced_invoices" value="{{ old('lock_synced_invoices', ($setting->lock_synced_invoices ?? true) ? '1' : '0') }}">

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="auto_sync_mode">{{ __('zatca::lang.ops_autosync_mode') }}</label>
                        <select name="auto_sync_mode" id="auto_sync_mode" class="form-select rounded-2">
                            <option value="disable" @selected(old('auto_sync_mode', $setting->auto_sync_mode ?? 'disable') === 'disable')>
                                {{ __('zatca::lang.ops_autosync_disable') }}
                            </option>
                            <option value="instant" @selected(old('auto_sync_mode', $setting->auto_sync_mode ?? 'disable') === 'instant')>
                                {{ __('zatca::lang.ops_autosync_instant') }}
                            </option>
                            <option value="daily" @selected(old('auto_sync_mode', $setting->auto_sync_mode ?? 'disable') === 'daily')>
                                {{ __('zatca::lang.ops_autosync_daily') }}
                            </option>
                        </select>
                    </div>

                    <div class="zatca-ops-note">
                        <ol class="mb-0 ps-3">
                            <li class="mb-1">{{ __('zatca::lang.ops_autosync_help_disable') }}</li>
                            <li class="mb-1">{{ __('zatca::lang.ops_autosync_help_instant') }}</li>
                            <li class="mb-0">{{ __('zatca::lang.ops_autosync_help_daily') }}</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-2 mt-auto">
                        <i class="fa fa-save me-1"></i>
                        {{ __('zatca::lang.ops_save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- قواعد الفاتورة --}}
    <div class="col-lg-4 d-flex">
        <div class="card zatca-ops-card h-100 w-100 d-flex flex-column border">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="zatca-ops-icon" aria-hidden="true">
                        <i class="fa fa-sliders-h"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="fw-bold fs-6 text-dark mb-1">{{ __('zatca::lang.ops_rules_title') }}</h2>
                        <p class="text-muted fs-7 mb-0">{{ __('zatca::lang.ops_rules_subtitle') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('zatca.settings.operations') }}" id="zatca-ops-rules-form" class="d-flex flex-column flex-grow-1">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="operations">
                    <input type="hidden" name="auto_sync_mode" value="{{ old('auto_sync_mode', $setting->auto_sync_mode ?? 'disable') }}">

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="disable_discount" value="0">
                        <input class="form-check-input" type="checkbox" name="disable_discount" value="1"
                               id="disable_discount"
                               @checked(old('disable_discount', $setting->disable_discount))>
                        <label class="form-check-label" for="disable_discount">
                            {{ __('zatca::lang.ops_disable_discount') }}
                        </label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="disable_order_tax" value="0">
                        <input class="form-check-input" type="checkbox" name="disable_order_tax" value="1"
                               id="disable_order_tax"
                               @checked(old('disable_order_tax', $setting->disable_order_tax))>
                        <label class="form-check-label" for="disable_order_tax">
                            {{ __('zatca::lang.ops_disable_order_tax') }}
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="default_sales_discount">{{ __('zatca::lang.ops_default_discount') }}</label>
                        <input type="number" step="0.01" min="0" max="100"
                               name="default_sales_discount" id="default_sales_discount"
                               class="form-control rounded-2"
                               value="{{ old('default_sales_discount', $setting->default_sales_discount ?? 0) }}">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="lock_synced_invoices" value="0">
                        <input class="form-check-input" type="checkbox" name="lock_synced_invoices" value="1"
                               id="lock_synced_invoices"
                               @checked(old('lock_synced_invoices', $setting->lock_synced_invoices ?? true))>
                        <label class="form-check-label" for="lock_synced_invoices">
                            {{ __('zatca::lang.ops_lock_synced') }}
                        </label>
                    </div>

                    <div class="zatca-ops-note">
                        {{ __('zatca::lang.ops_lock_warning') }}
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-2 mt-auto">
                        <i class="fa fa-check me-1"></i>
                        {{ __('zatca::lang.ops_apply_rules') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
