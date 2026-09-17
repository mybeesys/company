import React, { useEffect, useState } from 'react';

function GridIcon() {
    return (
        <svg className="ed-hub-ico" viewBox="0 0 20 20" aria-hidden="true">
            <rect x="2.5" y="2.5" width="6.5" height="6.5" rx="1.6" fill="currentColor" />
            <rect x="11" y="2.5" width="6.5" height="6.5" rx="1.6" fill="currentColor" opacity="0.55" />
            <rect x="2.5" y="11" width="6.5" height="6.5" rx="1.6" fill="currentColor" opacity="0.55" />
            <rect x="11" y="11" width="6.5" height="6.5" rx="1.6" fill="currentColor" />
        </svg>
    );
}

function CloseIcon() {
    return (
        <svg className="ed-hub-ico" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M5 5.8 14.2 15M14.2 5.8 5 15" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" fill="none" />
        </svg>
    );
}

export default function HubTabsToggle({ locale }) {
    const ar = locale === 'ar';
    const showLabel = ar ? 'لوحات الموديولات' : 'Module boards';
    const hideLabel = ar ? 'إخفاء اللوحات' : 'Hide boards';
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const panel = document.getElementById('edHubTabsPanel');
        if (!panel) return undefined;
        let stored = false;
        try {
            stored = sessionStorage.getItem('mybee.executiveDashboard.hubTabs') === '1';
        } catch (e) {
            stored = false;
        }
        panel.hidden = !stored;
        setOpen(stored);
        return undefined;
    }, []);

    const toggle = () => {
        const panel = document.getElementById('edHubTabsPanel');
        const next = !open;
        if (panel) panel.hidden = !next;
        setOpen(next);
        try {
            sessionStorage.setItem('mybee.executiveDashboard.hubTabs', next ? '1' : '0');
        } catch (e) {
            /* ignore */
        }
    };

    return (
        <button
            type="button"
            className={`ed-hub-chip${open ? ' is-open' : ''}`}
            aria-expanded={open ? 'true' : 'false'}
            aria-controls="edHubTabsPanel"
            onClick={toggle}
        >
            {open ? <CloseIcon /> : <GridIcon />}
            <span>{open ? hideLabel : showLabel}</span>
        </button>
    );
}
