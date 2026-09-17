import React from 'react';

export default function HorizontalBarChart({ items, onSelect, color = '#F28705' }) {
    if (!items?.length) {
        return null;
    }
    return (
        <div className="ed-hbar">
            {items.map((item) => (
                <button key={`${item.id}-${item.name}`} type="button" className="ed-hbar-row" onClick={() => onSelect?.(item)}>
                    <span>{item.name}</span>
                    <span className="ed-hbar-track">
                        <span className="ed-hbar-fill" style={{ width: `${item.share_percent || 0}%`, background: color }} />
                    </span>
                    <strong>{item.formatted}</strong>
                </button>
            ))}
        </div>
    );
}
