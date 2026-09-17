import React from 'react';

export default function ProductIssueTable({ block, locale, columns, actionLabel, onOpenAll, onOpenRow, onRowAction }) {
    const ar = locale === 'ar';
    const items = block?.items || [];
    const showActions = Boolean(actionLabel) && items.some((row) => row.url || onRowAction);

    return (
        <section className="ed-card ed-issue-card">
            <div className="ed-card-head">
                <h2>{block?.title}</h2>
                {onOpenAll && (
                    <button type="button" className="ed-btn ed-btn-ghost ed-btn-sm" onClick={onOpenAll}>
                        {ar ? 'عرض الكل' : 'View all'}
                    </button>
                )}
            </div>
            {items.length === 0 ? (
                <div className="ed-widget-state ed-widget-state-compact">{block?.empty}</div>
            ) : (
                <table className="ed-table">
                    <thead>
                        <tr>
                            {columns.map((col) => (
                                <th key={col.key}>{ar ? col.ar : col.en}</th>
                            ))}
                            {showActions && <th className="ed-cell-action">{ar ? 'إجراء' : 'Action'}</th>}
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((row) => (
                            <tr key={row.id}>
                                {columns.map((col) => (
                                    <td key={col.key} className={col.danger ? 'ed-cell-danger' : undefined}>
                                        {row[col.key]}
                                    </td>
                                ))}
                                {showActions && (
                                    <td className="ed-cell-action">
                                        {row.url ? (
                                            <button
                                                type="button"
                                                className="ed-btn ed-btn-ghost ed-btn-sm"
                                                onClick={() => (row.url ? onOpenRow?.(row.url) : onRowAction?.(row))}
                                            >
                                                {actionLabel || (ar ? 'تعديل' : 'Edit')}
                                            </button>
                                        ) : (
                                            <span className="ed-muted">—</span>
                                        )}
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </section>
    );
}
