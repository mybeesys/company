{{-- Journal report — screen + print styles (view layer only) --}}
<style>
    :root {
        --jr-navy: #475569;
        --jr-navy-soft: #64748b;
        --jr-slate: #94a3b8;
        --jr-border: #e8edf2;
        --jr-bg: #f9fafb;
        --jr-debit: #4b6b9a;
        --jr-credit: #4a8f7a;
        --jr-debit-bg: #f4f7fb;
        --jr-credit-bg: #f3f8f6;
        --jr-success: #5a9a6e;
        --jr-warning: #b8863b;
        --jr-font-mono: ui-monospace, 'Cascadia Code', 'Segoe UI Mono', monospace;
    }

    .jr-report {
        font-feature-settings: 'tnum' 1;
    }

    .jr-report-hero {
        background: #fff;
        border: 1px solid var(--jr-border);
        border-radius: 0.5rem;
        padding: 1.25rem 1.75rem;
        margin-bottom: 1.5rem;
    }

    .jr-report-hero h1 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #334155;
        margin: 0;
    }

    .jr-filter-card {
        border: 1px solid var(--jr-border);
        border-radius: 0.5rem;
        background: #fff;
        box-shadow: none;
    }

    .jr-filter-card .card-header {
        background: var(--jr-bg);
        border-bottom: 1px solid var(--jr-border);
        font-weight: 600;
        color: #475569;
        padding: 1rem 1.5rem;
    }

    .jr-filter-card .card-body {
        padding: 1.25rem 1.5rem;
    }

    .jr-kpi {
        border-radius: 0.5rem;
        border: 1px solid var(--jr-border);
        padding: 1.15rem 1.35rem;
        background: #fff;
        height: 100%;
    }

    .jr-kpi-label {
        font-size: 0.75rem;
        color: var(--jr-slate);
        margin-bottom: 0.4rem;
    }

    .jr-kpi-value {
        font-size: 1.15rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: #475569;
    }

    .jr-kpi--debit { border-inline-start: 3px solid #a8c4e8; }
    .jr-kpi--credit { border-inline-start: 3px solid #a8d4c4; }
    .jr-kpi--diff { border-inline-start: 3px solid #e8d4a8; }
    .jr-kpi--count { border-inline-start: 3px solid #c5cdd8; }

    .jr-entry {
        border: 1px solid var(--jr-border);
        border-radius: 0.5rem;
        background: #fff;
        margin-bottom: 1.75rem;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
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
        word-break: break-word;
        line-height: 1.45;
    }

    .jr-meta-item .value--mono {
        font-family: var(--jr-font-mono);
        font-size: 0.85rem;
    }

    .jr-badge-balanced {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
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
        gap: 0.4rem;
        padding: 0.35rem 0.85rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        background: #faf6ee;
        color: #8a7344;
        border: 1px solid #ebe4d4;
    }

    .jr-entry .table-responsive {
        padding: 0 1.25rem;
    }

    .jr-lines-table {
        width: 100%;
        margin: 0.75rem 0 1rem;
        font-size: 0.875rem;
        border-collapse: collapse;
    }

    .jr-lines-table thead th {
        background: #eef1f5;
        color: #5c6b7a;
        font-weight: 600;
        font-size: 0.72rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--jr-border);
        white-space: nowrap;
    }

    .jr-lines-table tbody tr:nth-child(even) {
        background: #fcfcfd;
    }

    .jr-lines-table tbody tr:hover {
        background: #f6f7f9;
    }

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
        background: transparent;
    }

    .jr-lines-table .col-gl {
        font-family: var(--jr-font-mono);
        font-size: 0.8rem;
        color: var(--jr-navy-soft);
        white-space: nowrap;
    }

    .jr-entry-footer {
        background: var(--jr-bg);
        border-top: 1px solid var(--jr-border);
        padding: 1.15rem 1.75rem;
        margin-top: 0.25rem;
    }

    .jr-footer-totals {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
    }

    .jr-footer-amounts {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
        font-variant-numeric: tabular-nums;
        color: #5c6b7a;
    }

    .jr-footer-amounts span strong {
        font-family: var(--jr-font-mono);
        font-size: 0.95rem;
        font-weight: 600;
    }

    .jr-footer-amounts .text-primary {
        color: var(--jr-debit) !important;
    }

    .jr-footer-amounts .text-success {
        color: var(--jr-credit) !important;
    }

    .jr-print-doc-header {
        display: none;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 10mm 14mm;
        }

        body {
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .app-sidebar,
        .app-header,
        .app-footer,
        .no-print,
        .jr-filter-card,
        .jr-toolbar,
        .drawer,
        #kt_app_sidebar,
        #kt_app_header,
        #kt_app_footer {
            display: none !important;
        }

        .app-main,
        .app-content,
        #kt_app_content,
        #kt_app_content_container,
        .container,
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }

        .jr-report {
            padding: 0 !important;
        }

        .jr-print-doc-header {
            display: block !important;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #cbd5e1;
        }

        .jr-print-doc-header .doc-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #475569;
            margin: 0;
        }

        .jr-print-doc-meta {
            font-size: 0.75rem;
            color: var(--jr-slate);
            margin-top: 0.35rem;
        }

        .jr-report-hero {
            background: none !important;
            border: none !important;
            padding: 0 0 0.5rem !important;
            margin-bottom: 0.5rem !important;
        }

        .jr-kpi-row {
            display: flex !important;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .jr-kpi {
            flex: 1 1 22%;
            min-width: 120px;
            padding: 0.65rem !important;
            box-shadow: none !important;
        }

        .jr-entry {
            page-break-inside: avoid;
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            margin-bottom: 0.85rem !important;
        }

        .jr-entry-header {
            padding: 0.75rem 1rem !important;
        }

        .jr-lines-table thead th {
            background: #eef1f5 !important;
            color: #5c6b7a !important;
            padding: 0.55rem 0.75rem !important;
            font-size: 0.65rem !important;
        }

        .jr-lines-table td {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.7rem !important;
        }

        .jr-entry-footer {
            padding: 0.65rem 1rem !important;
        }

        .jr-entry-actions {
            display: none !important;
        }
    }
</style>
