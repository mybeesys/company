import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const STORAGE_KEY = 'mybee.executiveDashboard.v2';
const DashboardFilterContext = createContext(null);

function readStored() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function monthBounds() {
    const now = new Date();
    const start = new Date(now.getFullYear(), 0, 1);
    const pad = (n) => String(n).padStart(2, '0');
    return {
        start_date: `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`,
        end_date: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`,
        branch_id: '',
        activity_id: '',
    };
}

function fromUrl() {
    const params = new URLSearchParams(window.location.search);
    const defaults = monthBounds();
    return {
        start_date: params.get('start_date') || defaults.start_date,
        end_date: params.get('end_date') || defaults.end_date,
        branch_id: params.get('branch_id') || '',
        activity_id: params.get('activity_id') || '',
    };
}

export function DashboardFilterProvider({ children }) {
    const stored = readStored();
    const [draft, setDraft] = useState(() => stored?.filters || fromUrl());
    const [applied, setApplied] = useState(() => stored?.filters || fromUrl());
    const [scrollY, setScrollY] = useState(stored?.scrollY || 0);
    const [salesDimension, setSalesDimension] = useState(stored?.salesDimension || 'by_product');
    const [version, setVersion] = useState(0);

    const persist = useCallback((nextFilters, extra = {}) => {
        sessionStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                filters: nextFilters,
                scrollY: extra.scrollY ?? window.scrollY,
                salesDimension: extra.salesDimension ?? salesDimension,
            })
        );
    }, [salesDimension]);

    const syncUrl = (next) => {
        const url = new URL(window.location.href);
        Object.entries(next).forEach(([k, v]) => {
            if (!v || v === 'all') url.searchParams.delete(k);
            else url.searchParams.set(k, v);
        });
        window.history.replaceState(null, '', url.toString());
    };

    const apply = useCallback(() => {
        setApplied({ ...draft });
        persist(draft);
        setVersion((v) => v + 1);
        syncUrl(draft);
    }, [draft, persist]);

    const applyPatch = useCallback((patch) => {
        setDraft((prev) => {
            const next = { ...prev, ...patch };
            setApplied(next);
            persist(next);
            setVersion((v) => v + 1);
            syncUrl(next);
            return next;
        });
    }, [persist]);

    const reset = useCallback(() => {
        const next = monthBounds();
        setDraft(next);
        setApplied(next);
        persist(next);
        setVersion((v) => v + 1);
        const url = new URL(window.location.href);
        ['start_date', 'end_date', 'branch_id', 'activity_id'].forEach((k) => url.searchParams.delete(k));
        window.history.replaceState(null, '', url.toString());
    }, [persist]);

    const patchDraft = useCallback((patch) => {
        setDraft((prev) => ({ ...prev, ...patch }));
    }, []);

    const rememberNavigation = useCallback(() => {
        persist(applied, { scrollY: window.scrollY });
    }, [applied, persist]);

    useEffect(() => {
        if (scrollY) {
            requestAnimationFrame(() => window.scrollTo(0, scrollY));
        }
        const onPageShow = (event) => {
            if (event.persisted) {
                const latest = readStored();
                if (latest?.scrollY) window.scrollTo(0, latest.scrollY);
            }
        };
        window.addEventListener('pageshow', onPageShow);
        return () => window.removeEventListener('pageshow', onPageShow);
    }, [scrollY]);

    const value = useMemo(
        () => ({
            draft,
            applied,
            version,
            salesDimension,
            setSalesDimension: (dim) => {
                setSalesDimension(dim);
                persist(applied, { salesDimension: dim });
            },
            patchDraft,
            apply,
            applyPatch,
            reset,
            rememberNavigation,
        }),
        [draft, applied, version, salesDimension, patchDraft, apply, applyPatch, reset, rememberNavigation, persist]
    );

    return <DashboardFilterContext.Provider value={value}>{children}</DashboardFilterContext.Provider>;
}

export function useDashboardFilters() {
    const ctx = useContext(DashboardFilterContext);
    if (!ctx) {
        throw new Error('DashboardFilterContext missing');
    }
    return ctx;
}
