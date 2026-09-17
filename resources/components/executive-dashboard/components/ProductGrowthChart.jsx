import React, { useEffect, useRef } from 'react';
import { chartPalette, useAppTheme } from '../hooks/useAppTheme';

export default function ProductGrowthChart({ data, locale }) {
    const elRef = useRef(null);
    const chartRef = useRef(null);
    const ar = locale === 'ar';
    const theme = useAppTheme();
    const palette = chartPalette(theme);
    const categories = Array.isArray(data?.categories) ? data.categories : [];
    const series = Array.isArray(data?.series) ? data.series : [];
    const labels = categories.map((c) => String(c?.label || c?.key || ''));
    const apexSeries = series.map((s) => ({
        name: String(s?.name || ''),
        data: (Array.isArray(s?.data) ? s.data : []).map((v) => Number(v) || 0),
    }));
    const colors = series.map((s) => s?.color).filter(Boolean);
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
                    height: 300,
                    fontFamily: 'inherit',
                    background: 'transparent',
                    toolbar: { show: false },
                    foreColor: palette.fore,
                    zoom: { enabled: false },
                },
                stroke: { width: 3, curve: 'smooth' },
                markers: { size: 4, strokeWidth: 0 },
                grid: { borderColor: palette.grid, strokeDashArray: 4 },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: labels,
                    labels: { style: { colors: palette.fore } },
                    axisBorder: { color: palette.grid },
                },
                yaxis: {
                    min: 0,
                    decimalsInFloat: 0,
                    labels: {
                        formatter: (v) => String(Math.round(Number(v) || 0)),
                        style: { colors: palette.fore },
                    },
                },
                legend: { position: 'top', horizontalAlign: ar ? 'right' : 'left', labels: { colors: palette.ink } },
                tooltip: { shared: true, intersect: false },
                colors: colors.length ? colors : ['#4E91FF', '#31D17C'],
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

    return <div ref={elRef} />;
}
