<style>
    .zatca-list-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.55rem;
        height: 1.55rem;
        border-radius: 999px;
        vertical-align: middle;
        flex-shrink: 0;
        border: 1px solid transparent;
        line-height: 1;
        cursor: help;
    }

    .zatca-list-badge i {
        font-size: 0.95rem;
        line-height: 1;
    }

    .zatca-list-badge--synced {
        color: #157347;
        background: rgba(27, 127, 74, 0.16);
        border-color: rgba(27, 127, 74, 0.38);
    }

    .zatca-list-badge--pending {
        color: #9a7209;
        background: rgba(233, 183, 31, 0.18);
        border-color: rgba(184, 134, 11, 0.45);
    }

    .zatca-list-badge--failed {
        color: #a8281d;
        background: rgba(192, 57, 43, 0.16);
        border-color: rgba(192, 57, 43, 0.42);
    }

    .zatca-list-badge--draft {
        color: #5e6278;
        background: rgba(126, 130, 153, 0.14);
        border-color: rgba(94, 98, 120, 0.28);
    }

    .tooltip.zatca-sync-tooltip .tooltip-inner {
        max-width: 300px;
        padding: 0.65rem 0.8rem;
        text-align: start;
        font-size: 0.8rem;
        line-height: 1.5;
        background-color: #1e1e2d;
        color: #ffffff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.18);
    }

    .tooltip.zatca-sync-tooltip.bs-tooltip-top .tooltip-arrow::before,
    .tooltip.zatca-sync-tooltip.bs-tooltip-auto[data-popper-placement^="top"] .tooltip-arrow::before {
        border-top-color: #1e1e2d;
    }

    .tooltip.zatca-sync-tooltip.bs-tooltip-bottom .tooltip-arrow::before,
    .tooltip.zatca-sync-tooltip.bs-tooltip-auto[data-popper-placement^="bottom"] .tooltip-arrow::before {
        border-bottom-color: #1e1e2d;
    }

    .tooltip.zatca-sync-tooltip.bs-tooltip-start .tooltip-arrow::before,
    .tooltip.zatca-sync-tooltip.bs-tooltip-auto[data-popper-placement^="left"] .tooltip-arrow::before {
        border-left-color: #1e1e2d;
    }

    .tooltip.zatca-sync-tooltip.bs-tooltip-end .tooltip-arrow::before,
    .tooltip.zatca-sync-tooltip.bs-tooltip-auto[data-popper-placement^="right"] .tooltip-arrow::before {
        border-right-color: #1e1e2d;
    }

    .zatca-sync-tip-title {
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #ffffff;
    }

    .zatca-sync-tip-body {
        color: #e8eaf0;
        font-weight: 500;
    }
</style>

<script>
    window.initZatcaListSyncTooltips = function (root) {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return;
        }

        const scope = root || document;
        scope.querySelectorAll('[data-zatca-tip]').forEach(function (el) {
            const existing = bootstrap.Tooltip.getInstance(el);
            if (existing) {
                existing.dispose();
            }
            new bootstrap.Tooltip(el, {
                html: true,
                customClass: 'zatca-sync-tooltip',
                trigger: 'hover focus',
            });
        });
    };
</script>
