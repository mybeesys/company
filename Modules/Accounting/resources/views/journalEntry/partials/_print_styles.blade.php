<style>
    :root {
        --jr-border: #e8edf2;
        --jr-bg: #f9fafb;
        --jr-debit: #4b6b9a;
        --jr-credit: #4a8f7a;
        --jr-debit-bg: #f4f7fb;
        --jr-credit-bg: #f3f8f6;
        --jr-slate: #94a3b8;
        --jr-font-mono: ui-monospace, 'Cascadia Code', 'Segoe UI Mono', monospace;
    }

    * { box-sizing: border-box; }

    body.mj-print-body {
        font-family: DejaVu Sans, 'Segoe UI', Tahoma, sans-serif;
        font-size: 12px;
        color: #475569;
        margin: 0;
        padding: 20px 24px;
        background: #fff;
        font-feature-settings: 'tnum' 1;
    }

    .mj-print-header {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--jr-border);
    }

    .mj-print-header-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .mj-print-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .mj-print-logo {
        max-height: 48px;
        max-width: 110px;
        object-fit: contain;
    }

    .mj-print-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #334155;
        margin: 0 0 0.25rem;
    }

    .mj-print-company {
        font-size: 0.85rem;
        color: #64748b;
    }

    .mj-print-meta,
    .mj-print-side {
        font-size: 0.78rem;
        color: var(--jr-slate);
        line-height: 1.55;
    }

    .mj-print-side { text-align: end; }

    .mj-print-currency {
        margin-top: 0.35rem;
        font-weight: 600;
        color: #475569;
    }

    .jr-entry {
        border: 1px solid var(--jr-border);
        border-radius: 0.5rem;
        background: #fff;
        overflow: hidden;
    }

    .jr-entry-header {
        background: var(--jr-bg);
        border-bottom: 1px solid var(--jr-border);
        padding: 1.35rem 1.75rem;
    }

    .jr-entry-header-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 1rem 1.5rem;
    }

    .jr-meta-item label {
        display: block;
        font-size: 0.72rem;
        font-weight: 500;
        color: var(--jr-slate);
        margin-bottom: 0.35rem;
    }

    .jr-meta-item .value {
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
        line-height: 1.45;
        word-break: break-word;
    }

    .jr-meta-item .value--mono {
        font-family: var(--jr-font-mono);
        font-size: 0.85rem;
    }

    .jr-badge-balanced {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.85rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: #eef6f1;
        color: #4a7c59;
        border: 1px solid #d4e8dc;
    }

    .jr-badge-unbalanced {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.85rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: #faf6ee;
        color: #8a7344;
        border: 1px solid #ebe4d4;
    }

    .jr-badge-manual {
        display: inline-flex;
        padding: 0.35rem 0.85rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f0f4f8;
        color: #5c6b7a;
        border: 1px solid #dce3eb;
    }

    .jr-lines-table {
        width: calc(100% - 2.5rem);
        margin: 1rem 1.25rem 1.15rem;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .jr-lines-table thead th {
        background: #eef1f5;
        color: #5c6b7a;
        font-weight: 600;
        font-size: 0.72rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--jr-border);
        text-align: start;
    }

    .jr-lines-table tbody tr:nth-child(even) { background: #fcfcfd; }

    .jr-lines-table td {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
        color: #5c6b7a;
        line-height: 1.5;
    }

    .jr-lines-table .col-amount {
        text-align: end;
        font-variant-numeric: tabular-nums;
        font-family: var(--jr-font-mono);
        font-size: 0.8125rem;
        white-space: nowrap;
    }

    .jr-lines-table .col-gl {
        font-family: var(--jr-font-mono);
        font-size: 0.8rem;
        color: #64748b;
        white-space: nowrap;
    }

    .jr-lines-table .amount-debit {
        color: var(--jr-debit);
        font-weight: 500;
        background: var(--jr-debit-bg);
    }

    .jr-lines-table .amount-credit {
        color: var(--jr-credit);
        font-weight: 500;
        background: var(--jr-credit-bg);
    }

    .jr-lines-table .amount-empty {
        color: #d1d5db;
        text-align: center;
    }

    .jr-entry-footer {
        background: var(--jr-bg);
        border-top: 1px solid var(--jr-border);
        padding: 1.15rem 1.75rem;
    }

    .jr-footer-totals {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .jr-footer-amounts {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        font-variant-numeric: tabular-nums;
    }

    .jr-footer-amounts strong {
        font-family: var(--jr-font-mono);
        font-weight: 600;
    }

    .mj-print-actions {
        margin-top: 1.5rem;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .mj-print-actions a,
    .mj-print-actions button {
        font-family: inherit;
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        border: 1px solid var(--jr-border);
        background: #fff;
        color: #475569;
        cursor: pointer;
        text-decoration: none;
    }

    .mj-print-actions .btn-primary {
        background: #5c7cfa;
        border-color: #5c7cfa;
        color: #fff;
    }

    @media print {
        @page { size: A4 portrait; margin: 14mm 12mm; }

        body.mj-print-body { padding: 0; }

        .no-print { display: none !important; }

        .jr-entry { page-break-inside: avoid; break-inside: avoid; }

        .mj-print-header {
            margin-bottom: 0.75rem;
            padding-bottom: 0.65rem;
        }
    }
</style>
