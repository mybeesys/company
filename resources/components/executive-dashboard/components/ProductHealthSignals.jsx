import React from 'react';

function SignalCard({ item, locale, onOpen }) {
    const ar = locale === 'ar';
    if (!item) return null;

    return (
        <button type="button" className={`ed-signal ed-signal-${item.level || 'info'}`} onClick={onOpen}>
            <div className="ed-signal-copy">
                <div className="ed-signal-kicker">
                    <span>{item.title}</span>
                    <span className={`ed-chip ed-chip-${item.level === 'critical' ? 'danger' : (item.level === 'success' ? 'success' : 'warning')}`}>
                        {item.badge}
                    </span>
                </div>
                <strong className="ed-signal-count">{item.count}</strong>
                <p>{item.body}</p>
            </div>
            <span className="ed-signal-cta">{ar ? 'تفاصيل' : 'Details'}</span>
        </button>
    );
}

export default function ProductHealthSignals({ health, locale, onOpenQuality, onOpenMargin }) {
    const growth = health?.growth;

    return (
        <div className="ed-signals">
            <SignalCard item={health?.quality} locale={locale} onOpen={onOpenQuality} />
            <section className="ed-signal ed-signal-metric">
                <div className="ed-signal-copy">
                    <div className="ed-signal-kicker">
                        <span>{growth?.title}</span>
                    </div>
                    <strong className={`ed-signal-count ${growth?.positive ? 'ed-up' : 'ed-down'}`}>
                        {growth?.formatted || '0%'}
                    </strong>
                    <p>{growth?.subtitle}</p>
                </div>
            </section>
            <SignalCard item={health?.margin} locale={locale} onOpen={onOpenMargin} />
        </div>
    );
}
