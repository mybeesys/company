<style>
    .trial-balance-wrap {
        --tb-border: var(--bs-border-color, #e4e6ef);
        font-variant-numeric: tabular-nums;
    }

    .tb-report-banner {
        border: 1px solid var(--tb-border);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .tb-report-banner .tb-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        margin-bottom: 0.25rem;
    }

    .tb-filters-card {
        border: 1px solid var(--tb-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .tb-kpi {
        border: 1px solid var(--tb-border);
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: var(--bs-body-bg);
        min-height: 86px;
        height: 100%;
    }

    .tb-kpi-label { font-size: 0.78rem; color: var(--bs-gray-600); margin-bottom: 0.25rem; }
    .tb-kpi-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .tb-kpi-value.text-danger { color: var(--bs-danger) !important; }
    .tb-kpi-value.text-success { color: var(--bs-success) !important; }

    .tb-panel {
        border: 1px solid var(--tb-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .tb-panel-header {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--bs-gray-200);
        background: var(--bs-gray-100);
    }

    .tb-panel-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--bs-gray-800);
        margin: 0;
    }

    .tb-chart-body { padding: 1rem 1.25rem; min-height: 300px; }
    #trialBalanceTypeChart { min-height: 280px; width: 100%; }

    .tb-table-card {
        border: 1px solid var(--tb-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        overflow: hidden;
    }

    .tb-table-scroll {
        max-height: min(72vh, 880px);
        overflow: auto;
    }

    .tb-table-scroll .dataTables_wrapper { padding: 0.75rem 1rem 1rem; }

    #kt_accounts_table {
        margin-bottom: 0;
        font-size: 0.875rem;
    }

    #kt_accounts_table thead tr.tb-thead-group th {
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid var(--bs-gray-300);
        text-align: center;
        vertical-align: middle;
    }

    #kt_accounts_table thead tr#accounts_headerRow th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: var(--bs-gray-100) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        border-bottom: 2px solid var(--bs-gray-300);
        white-space: nowrap;
        vertical-align: middle;
    }

    #kt_accounts_table thead tr.tb-thead-group th {
        position: sticky;
        top: 0;
        z-index: 4;
    }

    #kt_accounts_table thead tr#accounts_headerRow th {
        top: 34px;
    }

    #kt_accounts_table tbody td,
    #kt_accounts_table tfoot th {
        vertical-align: middle;
        border-color: var(--bs-gray-200);
    }

    #kt_accounts_table tbody tr:hover td {
        background: rgba(var(--bs-primary-rgb), 0.04);
    }

    #kt_accounts_table tfoot th {
        background: var(--bs-gray-100);
        font-weight: 700;
        font-size: 0.8rem;
    }

    #kt_accounts_table .tb-fin,
    #kt_accounts_table tfoot th.tb-fin {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        text-align: end !important;
        white-space: nowrap;
    }

    #kt_accounts_table .tb-fin-negative {
        color: var(--bs-danger);
        font-weight: 600;
    }

    .tb-indent {
        display: inline-block;
        width: 1.1rem;
    }

    .tb-gl-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 600;
        color: var(--bs-gray-700);
    }

    .tb-name-cell .tb-type-badge {
        font-size: 0.65rem;
        font-weight: 600;
        vertical-align: middle;
    }

    .tb-ledger-btn {
        font-weight: 600;
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-gray-300);
        color: var(--bs-gray-800) !important;
        background: var(--bs-body-bg);
        text-decoration: none !important;
    }

    .tb-ledger-btn:hover {
        border-color: var(--bs-primary);
        color: var(--bs-primary) !important;
        background: var(--bs-primary-light);
    }

    .tb-cell-unbalanced {
        background: var(--bs-danger-bg-subtle) !important;
        color: var(--bs-danger) !important;
    }

    .tb-top-list { list-style: none; padding: 0; margin: 0; }
    .tb-top-list li {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.45rem 0;
        border-bottom: 1px dashed var(--bs-gray-200);
        font-size: 0.85rem;
    }

    .tb-top-list li:last-child { border-bottom: 0; }

    #kt_accounts_table tr.tb-group-row {
        background: var(--bs-gray-100);
        font-weight: 700;
    }

    #kt_accounts_table tr.tb-group-row td {
        border-top: 2px solid var(--bs-gray-300);
        vertical-align: middle;
    }

    .tb-accordion-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        border: 0;
        background: transparent;
        font-weight: 700;
        color: var(--bs-gray-800);
        padding: 0.15rem 0.35rem;
    }

    .tb-accordion-toggle:hover {
        color: var(--bs-primary);
    }

    .tb-accordion-icon {
        transition: transform 0.18s ease;
        font-size: 0.75rem;
    }

    .tb-accordion-icon.tb-collapsed {
        transform: rotate(-90deg);
    }

    [dir="rtl"] .tb-accordion-icon.tb-collapsed {
        transform: rotate(90deg);
    }

    .tb-group-name { font-size: 0.9rem; }

    #kt_accounts_table tr.tb-account-row.d-none {
        display: none !important;
    }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        .no-print { display: none !important; }
        .tb-table-scroll { max-height: none !important; overflow: visible !important; }
        .tb-print-header, .tb-print-footer { display: block !important; }
        #kt_accounts_table tr.tb-account-row.d-none { display: table-row !important; }
        .tb-accordion-toggle { pointer-events: none; }
    }

    .tb-print-header, .tb-print-footer { display: none; }
</style>
