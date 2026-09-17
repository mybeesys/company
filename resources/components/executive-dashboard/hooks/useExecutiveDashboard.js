import { useEffect, useState } from 'react';
import { getExecutiveDashboardData } from '../api';
import { useDashboardFilters } from '../context/DashboardFilterContext';

export function useExecutiveDashboard() {
    const { applied, version } = useDashboardFilters();
    const [state, setState] = useState({ status: 'loading', data: null, error: null });

    useEffect(() => {
        let cancelled = false;
        const root = document.getElementById('executive-dashboard-root');
        const url = root?.getAttribute('data-data-url');
        setState({ status: 'loading', data: null, error: null });
        getExecutiveDashboardData(url, applied)
            .then((data) => {
                if (!cancelled) setState({ status: 'success', data, error: null });
            })
            .catch((err) => {
                if (!cancelled) setState({ status: 'error', data: null, error: err.message || 'Error' });
            });
        return () => {
            cancelled = true;
        };
    }, [applied, version]);

    return state;
}
