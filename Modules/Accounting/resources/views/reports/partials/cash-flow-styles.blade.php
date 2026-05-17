<style>
    .cash-flow-wrap {
        --cf-border: var(--bs-border-color, #e4e6ef);
        font-variant-numeric: tabular-nums;
    }

    .cf-report-banner {
        border: 1px solid var(--cf-border);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .cf-report-banner .cf-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
    }

    .cf-filters-card {
        border: 1px solid var(--cf-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .cf-kpi {
        border: 1px solid var(--cf-border);
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: var(--bs-body-bg);
        min-height: 86px;
        height: 100%;
    }

    .cf-kpi-label { font-size: 0.78rem; color: var(--bs-gray-600); margin-bottom: 0.25rem; }
    .cf-kpi-value {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .cf-panel {
        border: 1px solid var(--cf-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .cf-panel-header {
        padding: 0.85rem 1.25rem;
        border-bottom: 1px solid var(--bs-gray-200);
        background: var(--bs-gray-100);
    }

    .cf-panel-title { font-size: 0.95rem; font-weight: 700; color: var(--bs-gray-800); margin: 0; }
    .cf-chart-body { padding: 1rem 1.25rem; min-height: 280px; }
    #cfSectionChart, #cfTrendChart, #cfBarChart { min-height: 260px; width: 100%; }

    .cf-statement-scroll { max-height: min(70vh, 720px); overflow: auto; }

    #cfStatementTable {
        margin-bottom: 0;
        font-size: 0.875rem;
    }

    #cfStatementTable thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.72rem;
        text-transform: uppercase;
        border-bottom: 2px solid var(--bs-gray-300);
        white-space: nowrap;
    }

    #cfStatementTable tr.cf-section-row td {
        background: var(--bs-gray-200);
        font-weight: 700;
        color: var(--bs-primary);
        cursor: pointer;
        border-top: 2px solid var(--bs-gray-300);
    }

    #cfStatementTable tr.cf-section-row:hover td {
        background: var(--bs-primary-light);
    }

    #cfStatementTable tr.cf-line-row td {
        border-color: var(--bs-gray-200);
        vertical-align: middle;
    }

    #cfStatementTable tr.cf-line-row.cf-subtotal td {
        background: var(--bs-gray-100);
        font-weight: 700;
        border-top: 1px solid var(--bs-gray-300);
    }

    #cfStatementTable tr.cf-summary-row td {
        background: var(--bs-primary-light);
        font-weight: 800;
        font-size: 0.95rem;
        border-top: 2px solid var(--bs-primary);
    }

    #cfStatementTable .cf-fin {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        text-align: end !important;
        white-space: nowrap;
    }

    #cfStatementTable .cf-fin-negative { color: var(--bs-danger); font-weight: 600; }

    #cfStatementTable .cf-indent-1 { padding-inline-start: 2rem !important; }

    .cf-analytics-list { list-style: none; padding: 0; margin: 0; }
    .cf-analytics-list li {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.4rem 0;
        border-bottom: 1px dashed var(--bs-gray-200);
        font-size: 0.85rem;
    }

    #cfDetailTable thead th {
        background: var(--bs-gray-100) !important;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 700;
    }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        .no-print { display: none !important; }
        .cf-statement-scroll, .cf-detail-scroll { max-height: none !important; overflow: visible !important; }
        .cf-print-header, .cf-print-footer { display: block !important; }
    }

    .cf-print-header, .cf-print-footer { display: none; }
</style>
