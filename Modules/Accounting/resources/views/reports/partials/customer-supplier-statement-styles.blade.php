<style>
    .css-wrap {
        --css-border: var(--bs-border-color, #e4e6ef);
        font-variant-numeric: tabular-nums;
    }

    .css-report-banner {
        border: 1px solid var(--css-border);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .css-report-banner .css-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
    }

    .css-filters-card {
        border: 1px solid var(--css-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .css-kpi {
        border: 1px solid var(--css-border);
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: var(--bs-body-bg);
        min-height: 86px;
        height: 100%;
    }

    .css-kpi-label { font-size: 0.78rem; color: var(--bs-gray-600); margin-bottom: 0.25rem; }
    .css-kpi-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .css-kpi-highlight {
        border-color: var(--bs-primary);
        background: var(--bs-primary-light, rgba(27, 132, 255, 0.08));
    }

    .css-panel {
        border: 1px solid var(--css-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .css-panel-header {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--bs-gray-200);
        background: var(--bs-gray-100);
    }

    .css-panel-title { font-size: 0.95rem; font-weight: 700; color: var(--bs-gray-800); margin: 0; }
    .css-chart-body { padding: 1rem 1.25rem; min-height: 280px; }
    #cssCompositionChart, #cssBalanceTrend, #cssBarChart { min-height: 260px; width: 100%; }

    .css-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 0.75rem;
        padding: 1rem 1.25rem;
    }

    .css-summary-item {
        border: 1px solid var(--bs-gray-200);
        border-radius: 0.5rem;
        padding: 0.65rem 0.85rem;
        text-align: center;
    }

    .css-summary-item.css-highlight {
        border-color: var(--bs-primary);
        background: var(--bs-primary-light, rgba(27, 132, 255, 0.06));
    }

    .css-summary-label { font-size: 0.72rem; color: var(--bs-gray-600); }
    .css-summary-amount { font-size: 0.9rem; font-weight: 700; }

    .css-statement-scroll { max-height: min(70vh, 720px); overflow: auto; }

    #cssStatementTable {
        margin-bottom: 0;
        font-size: 0.875rem;
        border-collapse: collapse;
        --bs-table-border-color: var(--bs-gray-200);
        --bs-table-hover-bg: var(--bs-gray-100);
    }

    #cssStatementTable th,
    #cssStatementTable td {
        border-left: none !important;
        border-right: none !important;
        border-top: none !important;
        border-bottom: 1px solid var(--bs-gray-200) !important;
        box-shadow: none !important;
    }

    #cssStatementTable thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-gray-100) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        border-bottom: 2px solid var(--bs-gray-300) !important;
        white-space: nowrap;
    }

    #cssStatementTable tbody tr:hover td {
        background: var(--bs-gray-100);
    }

    #cssStatementTable tfoot td {
        background: var(--bs-gray-100);
        border-top: 2px solid var(--bs-gray-300) !important;
        border-bottom: none !important;
    }

    #cssStatementTable tbody td { vertical-align: middle; font-size: 0.85rem; }
    #cssStatementTable .css-fin {
        text-align: end;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        white-space: nowrap;
    }

    #cssStatementTable .css-fin-negative { color: var(--bs-danger); }

    #cssStatementTable tr.css-opening-row td { background: var(--bs-gray-100); font-weight: 600; }
    #cssStatementTable tr.css-important-row td {
        background-color: rgba(0, 0, 0, 0.02);
    }

    [data-bs-theme="dark"] #cssStatementTable tr.css-important-row td {
        background-color: rgba(255, 255, 255, 0.03);
    }
    #cssStatementTable tr.css-group-child { display: none; }
    #cssStatementTable tr.css-group-child.css-visible { display: table-row; }

    #cssStatementTable tr.css-settlement-row td {
        background-color: rgba(50, 168, 82, 0.06);
    }
    [data-bs-theme="dark"] #cssStatementTable tr.css-settlement-row td {
        background-color: rgba(50, 168, 82, 0.12);
    }

    .css-aging-bar {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        display: flex;
        margin-top: 0.5rem;
    }

    .css-aging-bar span { height: 100%; }

    .css-analytics-list { list-style: none; padding: 0; margin: 0; }
    .css-analytics-list li {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.45rem 0;
        border-bottom: 1px dashed var(--bs-gray-200);
        font-size: 0.85rem;
    }

    .css-print-header, .css-print-footer { display: none; }

    @media print {
        .no-print, .css-filters-card, .css-report-banner .btn, .sidebar, .header { display: none !important; }
        .css-print-header, .css-print-footer { display: block !important; }
        .css-panel, .css-report-banner { border: 1px solid #ccc; box-shadow: none; }
        .css-statement-scroll { max-height: none; overflow: visible; }
        #cssStatementTable thead th { position: static; }
    }

    [data-bs-theme="dark"] .css-report-banner {
        background: linear-gradient(180deg, var(--bs-gray-800) 0%, var(--bs-body-bg) 100%);
    }
</style>
