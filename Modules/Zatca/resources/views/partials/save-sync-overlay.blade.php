{{-- Full-screen save / ZATCA sync progress (non-destructive overlay) --}}
@php
    $zatcaOps = $zatcaOps ?? [];
    $overlayDocType = $overlayDocType ?? 'invoice';
    $instantOn = ($zatcaOps['auto_sync_mode'] ?? '') === 'instant';
    $configured = ! empty($zatcaOps['is_configured']);
@endphp

<style>
    .zatca-save-overlay {
        position: fixed;
        inset: 0;
        z-index: 20050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .22s ease, visibility .22s ease;
    }
    .zatca-save-overlay.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .zatca-save-overlay__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .42);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .zatca-save-overlay__panel {
        position: relative;
        width: min(100%, 26rem);
        border-radius: 1rem;
        background: #fff;
        border: 1px solid rgba(226, 232, 240, .95);
        box-shadow: 0 24px 64px rgba(15, 23, 42, .18);
        padding: 1.75rem 1.5rem 1.45rem;
        text-align: center;
        transform: translateY(10px) scale(.98);
        transition: transform .24s cubic-bezier(.22, 1, .36, 1);
    }
    .zatca-save-overlay.is-open .zatca-save-overlay__panel {
        transform: translateY(0) scale(1);
    }
    .zatca-save-overlay__ring {
        width: 3.25rem;
        height: 3.25rem;
        margin: 0 auto 1.1rem;
        border-radius: 50%;
        border: 2.5px solid #e8eef3;
        border-top-color: #0f766e;
        animation: zatca-save-spin .75s linear infinite;
    }
    .zatca-save-overlay.is-sync .zatca-save-overlay__ring {
        border-top-color: #e9b71f;
    }
    .zatca-save-overlay__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #111827;
        letter-spacing: -.01em;
    }
    .zatca-save-overlay__sub {
        margin: .45rem 0 0;
        font-size: .82rem;
        color: #64748b;
        line-height: 1.45;
        min-height: 2.4em;
    }
    .zatca-save-overlay__steps {
        list-style: none;
        margin: 1.15rem 0 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: .55rem;
        text-align: start;
    }
    .zatca-save-overlay__step {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .7rem;
        border-radius: .65rem;
        background: #f8fafc;
        border: 1px solid transparent;
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 600;
        transition: background .2s ease, color .2s ease, border-color .2s ease;
    }
    .zatca-save-overlay__step.is-active {
        background: #f0fdfa;
        border-color: rgba(15, 118, 110, .18);
        color: #0f766e;
    }
    .zatca-save-overlay__step.is-done {
        background: #f8fafc;
        color: #334155;
    }
    .zatca-save-overlay__step.is-active.is-sync-step {
        background: #fffbeb;
        border-color: rgba(233, 183, 31, .35);
        color: #92400e;
    }
    .zatca-save-overlay__bullet {
        width: .55rem;
        height: .55rem;
        border-radius: 50%;
        background: currentColor;
        opacity: .45;
        flex-shrink: 0;
    }
    .zatca-save-overlay__step.is-active .zatca-save-overlay__bullet {
        opacity: 1;
        box-shadow: 0 0 0 4px rgba(15, 118, 110, .12);
        animation: zatca-save-pulse 1.4s ease-out infinite;
    }
    .zatca-save-overlay__step.is-active.is-sync-step .zatca-save-overlay__bullet {
        box-shadow: 0 0 0 4px rgba(233, 183, 31, .18);
    }
    .zatca-save-overlay__step.is-done .zatca-save-overlay__bullet {
        opacity: .9;
        animation: none;
        box-shadow: none;
        background: #0f766e;
    }
    .zatca-save-overlay__hint {
        margin-top: .95rem;
        font-size: .72rem;
        color: #94a3b8;
    }
    @keyframes zatca-save-spin {
        to { transform: rotate(360deg); }
    }
    @keyframes zatca-save-pulse {
        0% { box-shadow: 0 0 0 0 rgba(15, 118, 110, .28); }
        70% { box-shadow: 0 0 0 8px rgba(15, 118, 110, 0); }
        100% { box-shadow: 0 0 0 0 rgba(15, 118, 110, 0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .zatca-save-overlay,
        .zatca-save-overlay__panel { transition: none; }
        .zatca-save-overlay__ring,
        .zatca-save-overlay__bullet { animation: none !important; }
    }
</style>

<div id="zatca-save-overlay"
     class="zatca-save-overlay"
     hidden
     aria-hidden="true"
     data-doc-type="{{ $overlayDocType }}"
     data-instant="{{ $instantOn ? '1' : '0' }}"
     data-configured="{{ $configured ? '1' : '0' }}"
     data-bind-classic="{{ ! empty($bindClassicForm) ? '1' : '0' }}">
    <div class="zatca-save-overlay__backdrop"></div>
    <div class="zatca-save-overlay__panel" role="dialog" aria-modal="true" aria-labelledby="zatca-save-overlay-title">
        <div class="zatca-save-overlay__ring" aria-hidden="true"></div>
        <h2 class="zatca-save-overlay__title" id="zatca-save-overlay-title"></h2>
        <p class="zatca-save-overlay__sub" id="zatca-save-overlay-sub"></p>
        <ol class="zatca-save-overlay__steps" id="zatca-save-overlay-steps" hidden></ol>
        <p class="zatca-save-overlay__hint">{{ __('zatca::lang.save_overlay_hint') }}</p>
    </div>
</div>

<script>
    window.zatcaSaveOverlayI18n = Object.assign({}, window.zatcaSaveOverlayI18n || {}, {
        saving_title: @json(__('zatca::lang.save_overlay_saving_title')),
        saving_sub_invoice: @json(__('zatca::lang.save_overlay_saving_sub_invoice')),
        saving_sub_return: @json(__('zatca::lang.save_overlay_saving_sub_return')),
        saving_draft_title: @json(__('zatca::lang.save_overlay_saving_draft_title')),
        saving_draft_sub: @json(__('zatca::lang.save_overlay_saving_draft_sub')),
        sync_title: @json(__('zatca::lang.save_overlay_sync_title')),
        sync_sub: @json(__('zatca::lang.save_overlay_sync_sub')),
        sync_sub_pending: @json(__('zatca::lang.save_overlay_sync_sub_pending')),
        redirect_title: @json(__('zatca::lang.save_overlay_redirect_title')),
        redirect_sub: @json(__('zatca::lang.save_overlay_redirect_sub')),
        step_save: @json(__('zatca::lang.save_overlay_step_save')),
        step_sync: @json(__('zatca::lang.save_overlay_step_sync')),
        step_done: @json(__('zatca::lang.save_overlay_step_done')),
    });
</script>
<script src="{{ url('/modules/Zatca/js/save-sync-overlay.js') }}?v={{ @filemtime(public_path('modules/Zatca/js/save-sync-overlay.js')) ?: time() }}"></script>
