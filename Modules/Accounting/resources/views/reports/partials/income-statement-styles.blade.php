<style>
    .income-statement-wrap {
        --is-border: var(--bs-border-color, #e4e6ef);
        --is-text: var(--bs-gray-900);
        --is-muted: var(--bs-gray-600);
        --is-accent: var(--bs-primary);
        --is-positive: var(--bs-success);
        --is-negative: var(--bs-danger);
        --is-section: var(--bs-gray-100);
        --is-subtotal: var(--bs-gray-200);
        --is-grand: var(--bs-primary-light);
        --is-profit: var(--bs-success-bg-subtle);
        --is-loss: var(--bs-danger-bg-subtle);
        font-variant-numeric: tabular-nums;
    }

    .is-report-banner {
        border: 1px solid var(--is-border);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .is-report-banner .is-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        letter-spacing: -0.02em;
    }

    .is-kpi {
        border: 1px solid #eef0f4;
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: #fcfcfd;
        min-height: 92px;
        height: 100%;
    }

    .is-kpi .is-kpi-label {
        font-size: 0.78rem;
        color: var(--bs-gray-600);
        margin-bottom: 0.25rem;
    }

    .is-kpi .is-kpi-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .is-kpi-growth {
        font-size: 0.75rem;
        margin-top: 0.35rem;
    }

    .is-kpi-growth.up { color: var(--bs-success); }
    .is-kpi-growth.down { color: var(--bs-danger); }

    .is-filters-card {
        border: 1px solid var(--is-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
    }

    .is-comparison-panel {
        border-color: var(--is-border) !important;
        background: var(--bs-gray-100) !important;
    }

    [data-bs-theme="dark"] .is-comparison-panel {
        background: rgba(15, 23, 42, 0.35) !important;
    }

    .is-table-card {
        border: 1px solid var(--is-border);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        overflow: hidden;
    }

    .is-table-scroll {
        max-height: min(72vh, 900px);
        overflow: auto;
    }

    .is-statement-table,
    #income-statement-table,
    #income-statement-comparison-table {
        margin-bottom: 0;
        font-size: 0.9rem;
        border-color: var(--is-border);
    }

    .is-statement-table thead th,
    #income-statement-table thead th,
    #income-statement-comparison-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid var(--bs-gray-300);
        padding: 0.65rem 0.85rem;
        white-space: nowrap;
        vertical-align: middle;
    }

    #income-statement-comparison-table thead th .is-period-range {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.72rem;
        font-weight: 500;
        text-transform: none;
        letter-spacing: normal;
        color: var(--bs-gray-600);
        white-space: nowrap;
    }

    .is-statement-table td,
    #income-statement-table td,
    #income-statement-comparison-table td {
        padding: 0.5rem 0.85rem;
        vertical-align: middle;
        border-color: var(--bs-gray-200);
    }

    .is-statement-table .is-fin-amount,
    #income-statement-table .is-fin-amount,
    #income-statement-comparison-table .is-fin-amount {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 500;
        white-space: nowrap;
    }

    .is-statement-table .is-fin-amount.is-negative,
    #income-statement-table .is-fin-amount.is-negative,
    #income-statement-comparison-table .is-fin-amount.is-negative {
        color: var(--bs-danger);
    }

    .is-statement-table tr.is-section td,
    #income-statement-table tr.is-section td,
    #income-statement-comparison-table tr.is-section td {
        background: var(--bs-gray-100);
        font-weight: 700;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--bs-primary);
        border-top: 2px solid var(--bs-gray-300);
    }

    .is-statement-table tr.is-subtotal td,
    #income-statement-table tr.is-subtotal td,
    #income-statement-comparison-table tr.is-subtotal td {
        background: var(--bs-gray-200);
        font-weight: 600;
        color: var(--bs-gray-800);
    }

    .is-statement-table tr.is-grand td,
    #income-statement-table tr.is-grand td,
    #income-statement-comparison-table tr.is-grand td {
        background: var(--bs-primary-light);
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--bs-gray-900);
    }

    .is-statement-table tr.is-profit-row td,
    #income-statement-table tr.is-profit-row td,
    #income-statement-comparison-table tr.is-profit-row td {
        background: var(--bs-success-bg-subtle);
        font-weight: 700;
        color: var(--bs-gray-900);
    }

    .is-statement-table tr.is-loss-row td,
    #income-statement-table tr.is-loss-row td,
    #income-statement-comparison-table tr.is-loss-row td {
        background: var(--bs-danger-bg-subtle);
        font-weight: 700;
        color: var(--bs-gray-900);
    }

    .is-statement-table tr.is-account-row:hover td,
    #income-statement-table tr.is-account-row:hover td,
    #income-statement-comparison-table tr.is-account-row:hover td {
        background: rgba(var(--bs-primary-rgb), 0.06);
    }

    .is-statement-table .is-account-label,
    #income-statement-table .is-account-label,
    #income-statement-comparison-table .is-account-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        color: var(--bs-gray-800);
    }

    .is-statement-table .is-gl-code,
    #income-statement-table .is-gl-code,
    #income-statement-comparison-table .is-gl-code {
        font-size: 0.75rem;
        color: var(--bs-gray-600);
        min-width: 3.5rem;
    }

    .is-statement-table .is-indent,
    #income-statement-table .is-indent,
    #income-statement-comparison-table .is-indent {
        display: inline-block;
        width: 1.1rem;
        flex-shrink: 0;
    }

    .is-toggle-btn {
        border: none;
        background: transparent;
        color: var(--bs-gray-600);
        padding: 0 0.25rem;
        line-height: 1;
        cursor: pointer;
    }

    .is-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .is-vat-note {
        font-size: 0.8rem;
        color: var(--bs-gray-600);
        border-inline-start: 3px solid var(--bs-primary);
        padding-inline-start: 0.75rem;
        margin-top: 1rem;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
        }

        body * {
            visibility: hidden;
        }

        #income-report,
        #income-report * {
            visibility: visible;
        }

        #income-report {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .no-print {
            display: none !important;
        }

        .is-table-scroll {
            max-height: none !important;
            overflow: visible !important;
        }

        .is-statement-table thead th,
        #income-statement-table thead th,
        #income-statement-comparison-table thead th {
            background: #f5f8fa !important;
            color: #3f4254 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .is-statement-table tr.is-section td,
        .is-statement-table tr.is-subtotal td,
        .is-statement-table tr.is-grand td,
        .is-statement-table tr.is-profit-row td,
        #income-statement-table tr.is-section td,
        #income-statement-table tr.is-subtotal td,
        #income-statement-table tr.is-grand td,
        #income-statement-table tr.is-profit-row td,
        #income-statement-comparison-table tr.is-section td,
        #income-statement-comparison-table tr.is-subtotal td,
        #income-statement-comparison-table tr.is-grand td,
        #income-statement-comparison-table tr.is-profit-row td,
        #income-statement-comparison-table tr.is-loss-row td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .is-print-header,
        .is-print-footer {
            display: block !important;
        }
    }

    .is-print-header,
    .is-print-footer {
        display: none;
    }
</style>
