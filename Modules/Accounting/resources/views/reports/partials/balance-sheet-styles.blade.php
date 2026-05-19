<style>
    .balance-sheet-wrap {
        font-variant-numeric: tabular-nums;
    }

    .bs-report-banner {
        border: 1px solid var(--bs-border-color, #e4e6ef);
        border-radius: 0.625rem;
        background: linear-gradient(180deg, var(--bs-gray-100) 0%, var(--bs-body-bg) 100%);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
    }

    .bs-report-banner .bs-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-gray-900);
    }

    .bs-kpi {
        border: 1px solid #eef0f4;
        border-radius: 0.625rem;
        padding: 12px 14px;
        background: #fcfcfd;
        min-height: 88px;
        height: 100%;
    }

    .bs-kpi-label { font-size: 0.78rem; color: var(--bs-gray-600); }
    .bs-kpi-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .bs-ratio-card {
        border: 1px solid var(--bs-border-color, #e4e6ef);
        border-radius: 0.625rem;
        padding: 1rem 1.25rem;
        background: var(--bs-body-bg);
        margin-bottom: 1rem;
    }

    .bs-ratio-item {
        text-align: center;
        padding: 0.5rem;
    }

    .bs-ratio-item .bs-ratio-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--bs-primary);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .bs-ratio-item .bs-ratio-label {
        font-size: 0.75rem;
        color: var(--bs-gray-600);
        margin-top: 0.25rem;
    }

    .bs-filters-card {
        border: 1px solid var(--bs-border-color, #e4e6ef);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        padding: 1rem 1.25rem;
    }

    .bs-table-card {
        border: 1px solid var(--bs-border-color, #e4e6ef);
        border-radius: 0.625rem;
        background: var(--bs-body-bg);
        overflow: hidden;
    }

    .bs-table-scroll {
        max-height: min(75vh, 920px);
        overflow: auto;
    }

    #balance-sheet-table {
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    #balance-sheet-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--bs-primary-light) !important;
        color: var(--bs-gray-700) !important;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        border-bottom: 2px solid var(--bs-gray-300);
        padding: 0.65rem 0.85rem;
    }

    #balance-sheet-table td {
        padding: 0.5rem 0.85rem;
        border-color: var(--bs-gray-200);
        vertical-align: middle;
    }

    #balance-sheet-table tr.bs-main-section td {
        background: var(--bs-primary);
        color: #fff;
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    #balance-sheet-table tr.bs-subsection td {
        background: var(--bs-gray-100);
        font-weight: 700;
        font-size: 0.82rem;
        color: var(--bs-primary);
        border-top: 2px solid var(--bs-gray-300);
    }

    #balance-sheet-table tr.bs-group-header td {
        background: var(--bs-gray-100);
        font-weight: 600;
        font-size: 0.8rem;
        color: var(--bs-gray-700);
        padding-top: 0.65rem;
    }

    #balance-sheet-table tr.bs-subtotal td {
        background: var(--bs-gray-200);
        font-weight: 600;
    }

    #balance-sheet-table tr.bs-grand td {
        background: var(--bs-primary-light);
        font-weight: 700;
    }

    #balance-sheet-table tr.bs-equation td {
        background: var(--bs-success-bg-subtle);
        font-weight: 700;
        font-size: 0.95rem;
    }

    #balance-sheet-table tr.bs-account-row:hover td {
        background: rgba(var(--bs-primary-rgb), 0.06);
    }

    #balance-sheet-table .bs-account-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    #balance-sheet-table .bs-gl-code {
        font-size: 0.75rem;
        color: var(--bs-gray-600);
        min-width: 3.5rem;
    }

    .bs-toggle-btn {
        border: none;
        background: transparent;
        color: var(--bs-gray-600);
        padding: 0 0.25rem;
        cursor: pointer;
    }

    @media print {
        @page { size: A4 portrait; margin: 12mm 10mm; }

        body * { visibility: hidden; }
        #balance-sheet-report, #balance-sheet-report * { visibility: visible; }
        #balance-sheet-report {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .no-print { display: none !important; }
        .bs-table-scroll { max-height: none !important; overflow: visible !important; }

        #balance-sheet-table thead th {
            background: #f5f8fa !important;
            color: #3f4254 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #balance-sheet-table tr.bs-main-section td {
            background: #e9f3ff !important;
            color: #181c32 !important;
        }

        .bs-print-header, .bs-print-footer { display: block !important; }
    }

    .bs-print-header, .bs-print-footer { display: none; }
</style>
