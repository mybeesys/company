import React from 'react';

function SignalCard({ item, locale, onOpen }) {
    const ar = locale === 'ar';
    if (!item) return null;

    const chip = item.level === 'critical' ? 'danger' : (item.level === 'success' ? 'success' : (item.level === 'info' ? 'info' : 'warning'));

    return (
        <button type="button" className={`ed-signal ed-signal-${item.level || 'info'}`} onClick={onOpen}>
            <div className="ed-signal-copy">
                <div className="ed-signal-kicker">
                    <span>{item.title}</span>
                    <span className={`ed-chip ed-chip-${chip}`}>{item.badge}</span>
                </div>
                <strong className="ed-signal-count">{item.count}</strong>
                <p>{item.body}</p>
                {Array.isArray(item.examples) && item.examples.length > 0 && (
                    <ul className="ed-signal-examples">
                        {item.examples.map((name, idx) => (
                            <li key={`${item.id}-${idx}`}>{name}</li>
                        ))}
                    </ul>
                )}
            </div>
            <span className="ed-signal-cta">{ar ? 'تفاصيل' : 'Details'}</span>
        </button>
    );
}

export default function InventoryHealthSignals({ health, locale, onOpenSignal }) {
    const signals = Array.isArray(health?.signals) ? health.signals : [];

    return (
        <div className="ed-signals ed-signals-4">
            {signals.map((item) => (
                <SignalCard key={item.id} item={item} locale={locale} onOpen={() => onOpenSignal(item.id)} />
            ))}
        </div>
    );
}
