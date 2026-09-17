import React, { useEffect, useRef } from 'react';
import { chartPalette, useAppTheme } from '../hooks/useAppTheme';

export default function ExpenseDonutChart({ slices, totalFormatted, locale, onSliceClick, centerLabel }) {
    const elRef = useRef(null);
    const chartRef = useRef(null);
    const theme = useAppTheme();
    const palette = chartPalette(theme);
    const safeSlices = Array.isArray(slices) ? slices.filter((s) => Number(s.value) > 0) : [];

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
                    height: 320,
                    background: 'transparent',
                    foreColor: palette.fore,
                    events: {
                        dataPointSelection(_e, _ctx, cfg) {
                            const slice = safeSlices[cfg.dataPointIndex];
                            if (slice) onSliceClick(slice);
                        },
                    },
                },
                theme: { mode: palette.mode },
                labels: safeSlices.map((s) => s.name),
                series: safeSlices.map((s) => Number(s.value) || 0),
                colors: safeSlices.map((s) => s.color),
                legend: {
                    position: 'bottom',
                    labels: { colors: palette.ink },
                    formatter(seriesName, opts) {
                        const slice = safeSlices[opts.seriesIndex];
                        const pct = slice?.share_percent ?? 0;
                        return `${seriesName} ${pct}%`;
                    },
                },
                stroke: { colors: [palette.surface] },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                name: { color: palette.fore },
                                value: { color: palette.ink },
                                total: {
                                    show: true,
                                    label: centerLabel || (locale === 'ar' ? 'إجمالي المصروفات' : 'Total expenses'),
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

    return <div ref={elRef} />;
}
