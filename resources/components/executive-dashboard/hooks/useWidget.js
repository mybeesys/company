import { useEffect, useState } from 'react';
import { fetchWidget } from '../api';
import { useDashboardFilters } from '../context/DashboardFilterContext';

export function useWidget(widget, extra = {}) {
    const { applied, version } = useDashboardFilters();
    const [state, setState] = useState({ status: 'loading', data: null, error: null });
    const extraKey = JSON.stringify(extra);

    useEffect(() => {
        let cancelled = false;
        const root = document.getElementById('executive-dashboard-root');
        const base = root?.getAttribute('data-widget-url');
        setState({ status: 'loading', data: null, error: null });
        fetchWidget(base, widget, applied, extra)
            .then((data) => {
                if (cancelled) return;
                const empty =
                    !data ||
                    (Array.isArray(data.items) && data.items.length === 0 && data.visible !== false) ||
                    (Array.isArray(data.cards) && data.cards.length === 0) ||
                    (Array.isArray(data.slices) && data.slices.length === 0);
                setState({
                    status: empty && widget !== 'kpis' && widget !== 'insights' && widget !== 'alerts' ? 'empty' : 'success',
                    data,
                    error: null,
                });
            })
            .catch((err) => {
                if (!cancelled) {
                    setState({ status: 'error', data: null, error: err.message || 'Error' });
                }
            });
        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [widget, version, extraKey]);

    return state;
}
