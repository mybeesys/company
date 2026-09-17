import React from 'react';
import ReactDOM from 'react-dom/client';
import './styles.css';
import ExecutiveDashboardView from './ExecutiveDashboardView';
import { DashboardFilterProvider } from './context/DashboardFilterContext';

const el = document.getElementById('executive-dashboard-root');
if (el) {
    ReactDOM.createRoot(el).render(
        <DashboardFilterProvider>
            <ExecutiveDashboardView />
        </DashboardFilterProvider>
    );
}
