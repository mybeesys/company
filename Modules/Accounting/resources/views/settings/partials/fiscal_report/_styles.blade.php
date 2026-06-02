<style>
    .fy-report {
        --fy-border: #e8ecf1;
        --fy-accent: #0d6e6e;
        --fy-accent-soft: #e6f4f4;
    }

    .fy-report .fy-report-hero {
        border: 1px solid var(--fy-border);
        border-radius: 12px;
        background: linear-gradient(135deg, #f8fafc 0%, var(--fy-accent-soft) 100%);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
    }

    .fy-report .fy-kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 1.5rem;
    }

    .fy-report .fy-kpi {
        border: 1px solid var(--fy-border);
        border-radius: 10px;
        background: #fff;
        padding: 14px 16px;
    }

    .fy-report .fy-kpi-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 4px;
    }

    .fy-report .fy-kpi-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
    }

    .fy-report .fy-section-card {
        border: 1px solid var(--fy-border);
        border-radius: 12px;
        background: #fff;
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .fy-report .fy-section-head {
        padding: 12px 16px;
        border-bottom: 1px solid var(--fy-border);
        font-weight: 700;
        color: #111827;
        background: #f9fafb;
    }

    .fy-report .fy-section-body {
        padding: 16px;
    }

    .fy-report .fy-bar-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
        font-size: 0.875rem;
    }

    .fy-report .fy-bar-track {
        flex: 1;
        height: 8px;
        background: #eef2f7;
        border-radius: 4px;
        overflow: hidden;
    }

    .fy-report .fy-bar-fill {
        height: 100%;
        background: var(--fy-accent);
        border-radius: 4px;
    }

    .fy-report .fy-report-table-wrap {
        padding: 1rem 1.25rem 1.25rem;
    }

    .fy-report .fy-report-table {
        margin-bottom: 0;
    }

    .fy-report .fy-report-table thead th,
    .fy-report .fy-report-table tbody td {
        text-align: center;
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }

    .fy-report .fy-report-table thead th {
        font-weight: 600;
        color: #374151;
        background: #f9fafb;
        white-space: nowrap;
    }

    .fy-report .fy-report-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .fy-report .fy-period-badge-open {
        background: #d1fae5;
        color: #047857;
    }

    .fy-report .fy-period-badge-closed {
        background: #fee2e2;
        color: #b91c1c;
    }

    @media print {
        .no-print { display: none !important; }
        .fy-report .fy-report-hero { background: #fff; }
    }
</style>
