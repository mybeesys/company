<style>
    .dashboard-hub-tabs {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #eef1f7;
        padding: 6px 8px;
        box-shadow: 0 2px 12px rgba(62, 57, 107, 0.05);
        gap: 4px;
        flex-wrap: nowrap;
        overflow-x: auto;
        scrollbar-width: thin;
    }
    .dashboard-hub-tabs .nav-link {
        white-space: nowrap;
        border-radius: 8px;
        color: #5e6278;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 14px;
        border: 0;
        margin: 0;
    }
    .dashboard-hub-tabs .nav-link.active {
        background: var(--bs-primary-light);
        color: var(--bs-primary);
    }
    .dashboard-hub-tabs .nav-link:not(.active):hover {
        background: #f8f9fc;
        color: #181c32;
    }
</style>
