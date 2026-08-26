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
