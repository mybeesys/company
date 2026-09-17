import React from 'react';

export default function KpiCard({ card, currency, onOpen }) {
    const growth = card.growth_percent;
    const up = growth === null || growth === undefined ? null : growth >= 0;
    return (
        <button type="button" className="ed-kpi" onClick={() => onOpen(card)}>
            <div className="ed-kpi-top">
                <div className="ed-kpi-title">{card.title}</div>
                {card.badge?.label && <span className={`ed-chip ed-chip-${card.badge.tone || 'warning'}`}>{card.badge.label}</span>}
                {card.tag?.label && <span className="ed-chip ed-chip-info">{card.tag.label}</span>}
            </div>
            <div className="ed-kpi-value">
                {card.formatted}
                {card.unit ? <span className="ed-kpi-unit"> {card.unit}</span> : null}
                {card.show_currency ? <span className="currency-symbol"> {currency}</span> : null}
            </div>
            {growth !== null && growth !== undefined && (
                <div className={`ed-kpi-growth ${up ? 'ed-up' : 'ed-down'}`}>
                    {up ? '▲' : '▼'} {Math.abs(growth)}%
                </div>
            )}
            <div className="ed-kpi-sub">{card.subtitle}</div>
        </button>
    );
}

export function KPICardsGrid({ cards, status, error, currency, onOpen }) {
    if (status === 'loading') {
        return (
            <div className="ed-kpi-grid">
                {Array.from({ length: 8 }).map((_, i) => (
                    <div key={i} className="ed-kpi"><div className="ed-skel" /></div>
                ))}
            </div>
        );
    }
    if (status === 'error') {
        return <div className="ed-card ed-widget-state">{error}</div>;
    }
    return (
        <div className="ed-kpi-grid">
            {(cards || []).map((card) => (
                <KpiCard key={card.id} card={card} currency={currency} onOpen={onOpen} />
            ))}
        </div>
    );
}
