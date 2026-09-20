import React, { useEffect, useRef } from 'react';
import { chartPalette, useAppTheme } from '../hooks/useAppTheme';

export default function FinancialTrendChart({ data, locale, onBarClick, compareUrl, onNavigate }) {
    const elRef = useRef(null);
    const chartRef = useRef(null);
    const clickRef = useRef(onBarClick);
    clickRef.current = onBarClick;
    const ar = locale === 'ar';
    const theme = useAppTheme();
    const palette = chartPalette(theme);

    const categories = Array.isArray(data?.categories) ? data.categories : [];
    const series = Array.isArray(data?.series) ? data.series : [];
    const labels = categories.map((c) => String(c?.label || c?.key || ''));
    const keys = categories.map((c) => c?.key);
    const apexSeries = series.map((s) => ({
        name: String(s?.name || ''),
        type: s?.type === 'line' ? 'line' : 'column',
        data: (Array.isArray(s?.data) ? s.data : []).map((v) => Number(v) || 0),
    }));
    const colors = series.map((s) => s?.color).filter(Boolean);
    const strokeWidths = series.map((s) => (s?.type === 'line' ? 3 : 0));
    const chartKey = JSON.stringify({ labels, apexSeries, colors, mode: palette.mode });

    useEffect(() => {
        if (!apexSeries.length || !window.ApexCharts || !elRef.current) {
            return undefined;
        }
        try {
            chartRef.current?.destroy();
        } catch (e) {
            chartRef.current = null;
        }
        try {
            const options = {
                chart: {
                    type: 'line',
                    height: 320,
                    fontFamily: 'inherit',
                    background: 'transparent',
                    toolbar: { show: false },
                    foreColor: palette.fore,
                    events: {
                        dataPointSelection(_e, _ctx, cfg) {
                            const month = keys[cfg.dataPointIndex];
                            if (month && clickRef.current) clickRef.current(month);
                        },
                    },
                },
                grid: { borderColor: palette.grid, strokeDashArray: 4 },
                plotOptions: { bar: { columnWidth: '52%', borderRadius: 3 } },
                dataLabels: { enabled: false },
                stroke: { width: strokeWidths, curve: 'smooth' },
                markers: { size: strokeWidths.map((w) => (w ? 4 : 0)) },
                xaxis: {
                    categories: labels,
                    labels: { style: { colors: palette.fore } },
                    axisBorder: { color: palette.grid },
                },
                yaxis: {
                    labels: {
                        formatter: (v) => Number(v || 0).toLocaleString(),
                        style: { colors: palette.fore },
                    },
                },
                legend: { position: 'top', horizontalAlign: ar ? 'right' : 'left', labels: { colors: palette.ink } },
                tooltip: { shared: true, intersect: false },
                colors: colors.length ? colors : ['#e9b71f', '#4E91FF', '#FF6470', '#31D17C'],
                series: apexSeries,
            };
            chartRef.current = new window.ApexCharts(elRef.current, options);
            chartRef.current.render();
        } catch (e) {
            /* keep card mounted */
        }
        return () => {
            try {
                chartRef.current?.destroy();
            } catch (e) {
                /* ignore */
            }
            chartRef.current = null;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [chartKey, ar, palette.fore, palette.ink, palette.grid]);

    if (!apexSeries.length) {
        return null;
    }

    return (
        <div>
            <div ref={elRef} />
            {compareUrl && (
                <button type="button" className="ed-btn ed-btn-ghost" style={{ marginTop: 8 }} onClick={() => onNavigate(compareUrl)}>
                    {ar ? 'مقارنة الفترات' : 'Compare periods'}
                </button>
            )}
        </div>
    );
}
