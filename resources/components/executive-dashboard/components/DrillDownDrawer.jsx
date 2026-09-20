import React, { useEffect } from 'react';
import ExpenseDonutChart from './ExpenseDonutChart';

export default function DrillDownDrawer({ open, payload, status, error, locale, onClose, onOpenRow, onOpenReport, onSliceClick }) {
    const ar = locale === 'ar';

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    if (!open) return null;

    const rows = payload?.rows || [];
    const columns = payload?.columns || [];
    const slices = payload?.slices || [];
    const hasChart = slices.length > 0;
    const hasRowActions = rows.some((row) => row.url);
    const actionLabel = payload?.action_label || (ar ? 'فتح' : 'Open');
    const showEmpty = status === 'success' && !hasChart && rows.length === 0;

    return (
        <>
            <div className="ed-drawer-backdrop" onClick={onClose} />
            <aside className="ed-drawer" role="dialog" aria-modal="true">
                <div className="ed-card-head">
                    <h2>{payload?.title || (ar ? 'التفاصيل' : 'Details')}</h2>
                    <button type="button" className="ed-btn ed-btn-ghost" onClick={onClose}>{ar ? 'إغلاق' : 'Close'}</button>
                </div>
                {status === 'loading' && !hasChart && <div className="ed-widget-state">...</div>}
                {status === 'error' && <div className="ed-widget-state">{error}</div>}
                {hasChart && (
                    <ExpenseDonutChart
                        slices={slices}
                        totalFormatted={payload.total_formatted}
                        locale={locale}
                        centerLabel={payload.center_label}
                        showLegend
                        height={220}
                        onSliceClick={onSliceClick}
                    />
                )}
                {showEmpty && (
                    <div className="ed-widget-state">{payload?.empty || (ar ? 'لا توجد بيانات' : 'No data')}</div>
                )}
                {rows.length > 0 && (
                    <table className="ed-table">
                        <thead>
                            <tr>
                                {columns.map((c) => <th key={c.key}>{c.label}</th>)}
                                {hasRowActions && <th className="ed-cell-action">{ar ? 'إجراء' : 'Action'}</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.id}>
                                    {columns.map((c) => (
                                        <td key={c.key}>{row[c.key]}</td>
                                    ))}
                                    {hasRowActions && (
                                        <td className="ed-cell-action">
                                            {row.url ? (
                                                <button type="button" className="ed-btn ed-btn-ghost ed-btn-sm" onClick={() => onOpenRow(row)}>
                                                    {actionLabel}
                                                </button>
                                            ) : '—'}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
                {payload?.report_url && (
                    <button type="button" className="ed-btn ed-btn-primary" style={{ marginTop: 16 }} onClick={() => onOpenReport(payload.report_url)}>
                        {ar ? 'عرض التقرير الكامل' : 'Open full report'}
                    </button>
                )}
            </aside>
        </>
    );
}
