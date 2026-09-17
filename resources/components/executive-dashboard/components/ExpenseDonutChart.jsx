import React, { useEffect, useMemo, useRef } from 'react';
import { chartPalette, useAppTheme } from '../hooks/useAppTheme';

function formatAmount(value, locale) {
    const amount = Number(value) || 0;
    try {
        return amount.toLocaleString(locale === 'ar' ? 'ar-SA' : 'en-US', { maximumFractionDigits: 0 });
    } catch {
        return String(Math.round(amount));
    }
}

export default function ExpenseDonutChart({ slices, totalFormatted, locale, onSliceClick, centerLabel }) {
    const elRef = useRef(null);
    const chartRef = useRef(null);
    const clickRef = useRef(onSliceClick);
    clickRef.current = onSliceClick;
    const theme = useAppTheme();
    const palette = chartPalette(theme);
    const ar = locale === 'ar';
    const safeSlices = useMemo(
        () => (Array.isArray(slices) ? slices.filter((s) => Number(s.value) > 0) : []),
        [slices]
    );

    const sliceKey = JSON.stringify(safeSlices.map((s) => [s.id, s.value, s.name, s.color]));

    useEffect(() => {
        if (!safeSlices.length || !window.ApexCharts || !elRef.current) {
            return undefined;
        }
        chartRef.current?.destroy();
        try {
            const options = {
                chart: {
                    type: 'donut',
                    height: 260,
                    background: 'transparent',
                    foreColor: palette.fore,
                    toolbar: { show: false },
                    events: {
                        dataPointSelection(_e, _ctx, cfg) {
                            const slice = safeSlices[cfg.dataPointIndex];
                            if (slice && clickRef.current) clickRef.current(slice);
                        },
                    },
                },
                theme: { mode: palette.mode },
                labels: safeSlices.map((s) => s.name),
                series: safeSlices.map((s) => Number(s.value) || 0),
                colors: safeSlices.map((s) => s.color),
                legend: { show: false },
                dataLabels: { enabled: false },
                tooltip: {
                    y: {
                        formatter: (val, opts) => {
                            const slice = safeSlices[opts.seriesIndex];
                            const pct = slice?.share_percent ?? 0;
                            return `${formatAmount(val, locale)} (${pct}%)`;
                        },
                    },
                },
                stroke: { colors: [palette.surface], width: 2 },
                plotOptions: {
                    pie: {
                        expandOnClick: false,
                        donut: {
                            size: '74%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '12px',
                                    color: palette.fore,
                                    offsetY: -6,
                                },
                                value: {
                                    show: true,
                                    fontSize: '16px',
                                    fontWeight: 800,
                                    color: palette.ink,
                                    offsetY: 6,
                                    formatter: () => totalFormatted,
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: centerLabel || (ar ? 'إجمالي المصروف' : 'Total expenses'),
                                    fontSize: '12px',
                                    color: palette.fore,
                                    formatter: () => totalFormatted,
                                },
                            },
                        },
                    },
                },
            };
            chartRef.current = new window.ApexCharts(elRef.current, options);
            chartRef.current.render();
        } catch (e) {
            /* keep empty mount rather than crashing the page */
        }
        return () => chartRef.current?.destroy();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sliceKey, totalFormatted, locale, centerLabel, palette.mode, palette.fore, palette.ink, palette.surface]);

    if (!safeSlices.length) {
        return null;
    }

    return (
        <div className="ed-donut">
            <div className="ed-donut-chart" ref={elRef} />
            <ul className="ed-donut-legend">
                {safeSlices.map((slice) => (
                    <li key={String(slice.id)}>
                        <button type="button" className="ed-donut-legend-item" onClick={() => onSliceClick?.(slice)}>
                            <span className="ed-donut-swatch" style={{ background: slice.color }} />
                            <span className="ed-donut-legend-copy">
                                <strong>{slice.name}</strong>
                                <em>{formatAmount(slice.value, locale)}</em>
                            </span>
                            <span className="ed-donut-pct">{slice.share_percent}%</span>
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
