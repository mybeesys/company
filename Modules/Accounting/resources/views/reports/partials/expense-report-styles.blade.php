<style>
    .expense-report-wrap {
        --er-border: var(--bs-border-color, #e4e6ef);
        --er-accent: var(--bs-primary);
        --er-positive: var(--bs-success);
        --er-negative: var(--bs-danger);
        --er-section: var(--bs-gray-100);
        --er-subtotal: var(--bs-gray-200);
        --er-highlight: var(--bs-primary-light);
        font-variant-numeric: tabular-nums;
    }

    .er-report-banner {
        border: 1px solid var(--er-border);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .er-report-banner .er-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        letter-spacing: -0.02em;
        margin-bottom: 0.25rem;
    }

    .er-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-end;
    }

    [dir="rtl"] .er-toolbar {
        justify-content: flex-start;
    }

    .er-filters-card {
        border: 1px solid var(--er-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
    }

    .er-kpi {
        border: 1px solid var(--er-border);
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: var(--bs-body-bg);
        min-height: 88px;
        height: 100%;
    }

    .er-kpi-label { font-size: 0.78rem; color: var(--bs-gray-600); margin-bottom: 0.25rem; }
    .er-kpi-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .er-kpi-growth.up { color: var(--bs-success); font-size: 0.75rem; }
    .er-kpi-growth.down { color: var(--bs-danger); font-size: 0.75rem; }

    .er-panel {
        border: 1px solid var(--er-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        margin-bottom: 1rem;
        overflow: hidden;
    }

    .er-panel-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-gray-200);
        background: var(--bs-gray-100);
    }

    .er-panel-header .er-panel-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--bs-gray-800);
        margin: 0;
    }

    .er-chart-body { padding: 1rem 1.25rem; min-height: 300px; }

    #expenseCategoryChart, #expenseTrendChart { min-height: 280px; width: 100%; }

    .er-table-scroll { max-height: min(70vh, 800px); overflow: auto; }

    #expenseReportTable {
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    #expenseReportTable thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid var(--bs-gray-300);
        padding: 0.65rem 0.85rem;
        white-space: nowrap;
        vertical-align: middle;
    }

    #expenseReportTable tr.er-cat-row td {
        background: var(--bs-gray-100);
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--bs-primary);
        border-top: 2px solid var(--bs-gray-300);
        cursor: pointer;
        vertical-align: middle;
    }

    #expenseReportTable tr.er-cat-row:hover td {
        background: var(--bs-primary-light);
    }

    #expenseReportTable tr.er-cat-row .er-cat-icon {
        color: var(--bs-gray-600);
        width: 1rem;
        display: inline-block;
        text-align: center;
    }

    #expenseReportTable tr.er-detail-row td {
        vertical-align: middle;
        border-color: var(--bs-gray-200);
        padding: 0.5rem 0.85rem;
    }

    #expenseReportTable tr.er-detail-row.er-high td {
        background: var(--bs-danger-bg-subtle);
    }

    #expenseReportTable tr.er-detail-row:hover td {
        background: rgba(var(--bs-primary-rgb), 0.06);
    }

    #expenseReportTable .is-fin-amount {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 500;
        white-space: nowrap;
    }

    #expenseReportTable .is-fin-amount.is-negative {
        color: var(--bs-danger);
    }

    .er-classification-table thead th {
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 2px solid var(--bs-gray-300);
    }

    .er-classification-table tbody td {
        border-color: var(--bs-gray-200);
        vertical-align: middle;
    }

    @media (max-width: 991.98px) {
        .er-toolbar {
            justify-content: flex-start;
            width: 100%;
            margin-top: 0.5rem;
        }
    }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body * { visibility: hidden; }
        #expense-report-root, #expense-report-root * { visibility: visible; }
        #expense-report-root {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print { display: none !important; }
        .er-table-scroll { max-height: none !important; overflow: visible !important; }
        .er-chart-body { break-inside: avoid; }
        .er-print-header, .er-print-footer { display: block !important; }
        #expenseReportTable thead th {
            background: #f5f8fa !important;
            color: #3f4254 !important;
        }
    }

    .er-print-header, .er-print-footer { display: none; }
</style>
