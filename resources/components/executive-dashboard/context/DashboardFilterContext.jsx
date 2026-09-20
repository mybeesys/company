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

function asIdList(value) {
    if (Array.isArray(value)) {
        return value.map(String).filter((id) => id && id !== 'all');
    }
    if (value === null || value === undefined || value === '' || value === 'all') {
        return [];
    }
    return String(value).split(/[,\s]+/).filter((id) => id && id !== 'all');
}

function monthBounds() {
    const now = new Date();
    const start = new Date(now.getFullYear(), 0, 1);
    const pad = (n) => String(n).padStart(2, '0');
    return normalize({
        start_date: `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`,
        end_date: `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`,
        branch_ids: [],
        activity_ids: [],
    });
}

function normalize(filters = {}) {
    const branch_ids = Object.prototype.hasOwnProperty.call(filters, 'branch_ids')
        ? asIdList(filters.branch_ids)
        : asIdList(filters.branch_id);
    const activity_ids = Object.prototype.hasOwnProperty.call(filters, 'activity_ids')
        ? asIdList(filters.activity_ids)
        : asIdList(filters.activity_id);

    return {
        start_date: filters.start_date || '',
        end_date: filters.end_date || '',
        branch_ids,
        activity_ids,
        branch_id: branch_ids[0] || '',
        activity_id: activity_ids[0] || '',
    };
}

function fromUrl() {
    const params = new URLSearchParams(window.location.search);
    const defaults = monthBounds();
    return normalize({
        start_date: params.get('start_date') || defaults.start_date,
        end_date: params.get('end_date') || defaults.end_date,
        branch_ids: params.get('branch_ids') || params.getAll('branch_ids[]').join(',') || params.get('branch_id') || '',
        activity_ids: params.get('activity_ids') || params.getAll('activity_ids[]').join(',') || params.get('activity_id') || '',
    });
}

function mergeFilters(prev, patch) {
    const next = { ...prev, ...patch };
    if ('branch_id' in patch && !('branch_ids' in patch)) {
        next.branch_ids = patch.branch_id ? [String(patch.branch_id)] : [];
    }
    if ('activity_id' in patch && !('activity_ids' in patch)) {
        next.activity_ids = patch.activity_id ? [String(patch.activity_id)] : [];
    }
    return normalize(next);
}

export function DashboardFilterProvider({ children }) {
    const stored = readStored();
    const [draft, setDraft] = useState(() => normalize(stored?.filters || fromUrl()));
    const [applied, setApplied] = useState(() => normalize(stored?.filters || fromUrl()));
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
        ['start_date', 'end_date', 'branch_id', 'activity_id', 'branch_ids', 'activity_ids'].forEach((k) => {
            url.searchParams.delete(k);
        });
        Object.entries(next).forEach(([k, v]) => {
            if (Array.isArray(v)) {
                if (v.length) url.searchParams.set(k, v.join(','));
                return;
            }
            if (!v || v === 'all') return;
            if (k === 'branch_id' || k === 'activity_id') return;
            url.searchParams.set(k, v);
        });
        window.history.replaceState(null, '', url.toString());
    };

    const apply = useCallback(() => {
        const next = normalize(draft);
        setDraft(next);
        setApplied(next);
        persist(next);
        setVersion((v) => v + 1);
        syncUrl(next);
    }, [draft, persist]);

    const applyPatch = useCallback((patch) => {
        setDraft((prev) => {
            const next = mergeFilters(prev, patch);
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
        ['start_date', 'end_date', 'branch_id', 'activity_id', 'branch_ids', 'activity_ids'].forEach((k) => {
            url.searchParams.delete(k);
        });
        window.history.replaceState(null, '', url.toString());
    }, [persist]);

    const patchDraft = useCallback((patch) => {
        setDraft((prev) => mergeFilters(prev, patch));
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
