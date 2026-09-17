import React from 'react';

export default function DecisionCenter({ alerts, locale, onNavigate }) {
    const ar = locale === 'ar';
    const items = alerts || [];

    return (
        <section className="ed-card ed-decision">
            <div className="ed-card-head">
                <h2>{ar ? 'مركز القرار' : 'Decision center'}</h2>
            </div>
            {items.length === 0 && <p className="ed-insight">{ar ? 'لا توجد تنبيهات' : 'No alerts'}</p>}
            {items.map((item) => (
                <div key={item.id} className={`ed-alert ${item.level || 'info'}`}>
                    <div>
                        <div className="ed-alert-title">{item.title}</div>
                        <strong className="ed-alert-count">{item.count}</strong>
                    </div>
                    {item.href && (
                        <button
                            type="button"
                            className="ed-btn ed-btn-ghost ed-btn-sm"
                            onClick={() => onNavigate(item.href)}
                        >
                            {item.action_label}
                        </button>
                    )}
                </div>
            ))}
        </section>
    );
}
