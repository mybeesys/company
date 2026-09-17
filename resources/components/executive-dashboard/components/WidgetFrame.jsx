import React from 'react';

export default function WidgetFrame({ title, status, error, emptyText, children, actions }) {
    return (
        <section className="ed-card">
            <div className="ed-card-head">
                <h2>{title}</h2>
                {actions}
            </div>
            {status === 'loading' && (
                <div className="ed-widget-state" role="status">
                    <div style={{ width: '100%' }}>
                        <div className="ed-skel" />
                        <div className="ed-skel" style={{ marginTop: 10, width: '70%' }} />
                    </div>
                </div>
            )}
            {status === 'error' && (
                <div className="ed-widget-state" role="alert">
                    {error || 'تعذر تحميل هذا القسم'}
                </div>
            )}
            {status === 'empty' && <div className="ed-widget-state">{emptyText || 'لا توجد بيانات'}</div>}
            {status === 'success' && children}
        </section>
    );
}
