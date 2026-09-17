import React, { useCallback, useEffect, useState } from 'react';
import { fetchBootstrap, fetchWidget } from './api';
import { useDashboardFilters } from './context/DashboardFilterContext';
import { useExecutiveDashboard } from './hooks/useExecutiveDashboard';
import DashboardFilterBar from './components/FilterBar';
import { KPICardsGrid } from './components/KpiCard';
import WidgetFrame from './components/WidgetFrame';
import WidgetErrorBoundary from './components/WidgetErrorBoundary';
import FinancialTrendChart from './components/FinancialTrendChart';
import ExpenseDonutChart from './components/ExpenseDonutChart';
import HorizontalBarChart from './components/HorizontalBarChart';
import DecisionCenter from './components/DecisionCenter';
import ProductHealthSignals from './components/ProductHealthSignals';
import ProductIssueTable from './components/ProductIssueTable';
import InventoryHealthSignals from './components/InventoryHealthSignals';
import ProductGrowthChart from './components/ProductGrowthChart';
import DrillDownDrawer from './components/DrillDownDrawer';

const DIMENSIONS = [
    { id: 'by_product', ar: 'المنتج', en: 'Product' },
    { id: 'by_service', ar: 'الخدمة', en: 'Service' },
    { id: 'by_category', ar: 'التصنيف', en: 'Category' },
    { id: 'by_customer', ar: 'العميل', en: 'Customer' },
    { id: 'by_salesman', ar: 'المندوب', en: 'Sales rep' },
];

export default function ExecutiveDashboardView() {
    const root = document.getElementById('executive-dashboard-root');
    const locale = root?.getAttribute('data-locale') || 'ar';
    const ar = locale === 'ar';
    const { applied, rememberNavigation, salesDimension, setSalesDimension, applyPatch } = useDashboardFilters();
    const dimKey = ['by_product', 'by_service', 'by_category', 'by_customer', 'by_salesman'].includes(salesDimension)
        ? salesDimension
        : 'by_product';

    const [bootstrap, setBootstrap] = useState(null);
    const [bootStatus, setBootStatus] = useState('loading');
    const [drawer, setDrawer] = useState({ open: false, status: 'idle', data: null, error: null });
    const dashboard = useExecutiveDashboard();

    useEffect(() => {
        const url = root?.getAttribute('data-bootstrap-url');
        fetchBootstrap(url, applied)
            .then((data) => {
                setBootstrap(data);
                setBootStatus('success');
            })
            .catch((err) => {
                setBootStatus('error');
                setBootstrap({ error: err.message });
            });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const go = useCallback(
        (href) => {
            if (!href) return;
            rememberNavigation();
            window.location.href = href;
        },
        [rememberNavigation]
    );

    const openDrilldown = useCallback(
        (source, extra = {}) => {
            const base = root?.getAttribute('data-widget-url');
            setDrawer({ open: true, status: 'loading', data: null, error: null });
            window.history.pushState({ edDrawer: true }, '');
            fetchWidget(base, 'drilldown', applied, { source, ...extra })
                .then((data) => setDrawer({ open: true, status: 'success', data, error: null }))
                .catch((err) => setDrawer({ open: true, status: 'error', data: null, error: err.message }));
        },
        [applied, root]
    );

    useEffect(() => {
        const onPop = () => setDrawer((d) => ({ ...d, open: false }));
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, []);

    const closeDrawer = () => {
        setDrawer((d) => ({ ...d, open: false }));
        if (window.history.state?.edDrawer) window.history.back();
    };

    if (bootStatus === 'error' && !bootstrap?.options) {
        return <div className="ed-card ed-widget-state">{bootstrap?.error}</div>;
    }

    const routes = bootstrap?.routes || {};
    const currency = bootstrap?.currency || '';
    const payload = dashboard.data || {};
    const empty = payload.labels?.empty;
    const analysisItems = payload.sales_analysis?.[dimKey] || [];
    const showBranches = payload.branches_visible && (payload.top_branches || []).length > 0
        && (bootstrap?.options?.branches || []).length > 1;
    const kpiStatus = dashboard.status === 'success' ? 'success' : dashboard.status;

    return (
        <div className="ed-page">
            <header className="ed-hero">
                <h1>{ar ? 'لوحة قرار تنفيذية' : 'Executive decision board'}</h1>
                <p>{ar ? 'نظرة واضحة على أداء منشأتك للفترة المحددة' : 'A clear view of business performance for the selected period'}</p>
            </header>

            <DashboardFilterBar bootstrap={bootstrap} locale={locale} />

            <WidgetErrorBoundary>
                <KPICardsGrid
                    cards={payload.cards}
                    status={kpiStatus}
                    error={dashboard.error}
                    currency={currency}
                    onOpen={(card) => openDrilldown(card.drilldown)}
                />
            </WidgetErrorBoundary>

            <div className="ed-charts">
                <WidgetErrorBoundary title={ar ? 'الأداء المالي عبر الفترات' : 'Financial performance'}>
                    <WidgetFrame
                        title={ar ? 'الأداء المالي عبر الفترات' : 'Financial performance'}
                        subtitle={payload.financial_chart?.note}
                        status={dashboard.status === 'loading' ? 'loading' : (payload.financial_chart?.series?.length ? 'success' : (dashboard.status === 'error' ? 'error' : 'empty'))}
                        error={dashboard.error}
                        emptyText={empty}
                    >
                        <FinancialTrendChart
                            data={payload.financial_chart}
                            locale={locale}
                            compareUrl={routes.compare}
                            onNavigate={go}
                            onBarClick={(month) => openDrilldown('sales', { month })}
                        />
                    </WidgetFrame>
                </WidgetErrorBoundary>
                <WidgetErrorBoundary title={ar ? 'توزيع المصروفات' : 'Expense distribution'}>
                    <WidgetFrame
                        title={ar ? 'توزيع المصروفات' : 'Expense distribution'}
                        status={dashboard.status === 'loading' ? 'loading' : ((payload.expense_categories || []).length ? 'success' : (dashboard.status === 'error' ? 'error' : 'empty'))}
                        error={dashboard.error}
                        emptyText={empty}
                    >
                        <ExpenseDonutChart
                            slices={payload.expense_categories}
                            totalFormatted={payload.expense_total_formatted}
                            locale={locale}
                            onSliceClick={(slice) => openDrilldown('expense-category', { category_id: slice.id })}
                        />
                    </WidgetFrame>
                </WidgetErrorBoundary>
            </div>

            {payload.accounting_health?.visible && (
                <div className="ed-charts">
                    <WidgetErrorBoundary title={ar ? 'اتجاه الحركات' : 'Transaction trend'}>
                        <WidgetFrame
                            title={ar ? 'اتجاه الحركات' : 'Transaction trend'}
                            status={dashboard.status === 'loading' ? 'loading' : (payload.accounting_health.trend?.series?.length ? 'success' : 'empty')}
                            emptyText={empty}
                            actions={
                                routes.accounting_dashboard ? (
                                    <button type="button" className="ed-btn ed-btn-ghost ed-btn-sm" onClick={() => go(routes.accounting_dashboard)}>
                                        {ar ? 'لوحة المحاسبة' : 'Accounting dashboard'}
                                    </button>
                                ) : null
                            }
                        >
                            <ProductGrowthChart data={payload.accounting_health.trend} locale={locale} />
                        </WidgetFrame>
                    </WidgetErrorBoundary>
                    <WidgetErrorBoundary title={ar ? 'مخطط الحسابات' : 'Chart of accounts'}>
                        <WidgetFrame
                            title={ar ? 'مخطط الحسابات' : 'Chart of accounts'}
                            status={dashboard.status === 'loading' ? 'loading' : ((payload.accounting_health.chart_of_accounts?.slices || []).length ? 'success' : 'empty')}
                            emptyText={empty}
                        >
                            <ExpenseDonutChart
                                slices={payload.accounting_health.chart_of_accounts?.slices}
                                totalFormatted={payload.accounting_health.chart_of_accounts?.formatted_total}
                                locale={locale}
                                centerLabel={ar ? 'إجمالي الأرصدة' : 'Total balances'}
                                onSliceClick={() => {
                                    if (routes.accounting_dashboard) go(routes.accounting_dashboard);
                                }}
                            />
                        </WidgetFrame>
                    </WidgetErrorBoundary>
                </div>
            )}

            {payload.product_health?.visible && (
                <>
                    <WidgetErrorBoundary title={ar ? 'صحة كتالوج المنتجات' : 'Product catalog health'}>
                        {dashboard.status === 'loading' ? (
                            <div className="ed-signals">
                                <section className="ed-card"><div className="ed-skel" /></section>
                                <section className="ed-card"><div className="ed-skel" /></section>
                                <section className="ed-card"><div className="ed-skel" /></section>
                            </div>
                        ) : (
                            <ProductHealthSignals
                                health={payload.product_health}
                                locale={locale}
                                onOpenQuality={() => openDrilldown('zero-price')}
                                onOpenMargin={() => openDrilldown('negative-margin')}
                            />
                        )}
                    </WidgetErrorBoundary>
                    <div className="ed-product-lists">
                        <WidgetErrorBoundary title={ar ? 'منتجات تحتاج تصحيح سعر' : 'Products needing a price fix'}>
                            <ProductIssueTable
                                block={payload.product_health.price_fixes}
                                locale={locale}
                                columns={[
                                    { key: 'name', ar: 'المنتج', en: 'Product' },
                                    { key: 'price', ar: 'السعر', en: 'Price', danger: true },
                                ]}
                                actionLabel={ar ? 'تصحيح السعر' : 'Fix price'}
                                onOpenAll={() => openDrilldown('zero-price')}
                                onOpenRow={go}
                            />
                        </WidgetErrorBoundary>
                        <WidgetErrorBoundary title={ar ? 'منتجات بخسارة' : 'Loss-making products'}>
                            <ProductIssueTable
                                block={payload.product_health.loss_makers}
                                locale={locale}
                                columns={[
                                    { key: 'name', ar: 'المنتج', en: 'Product' },
                                    { key: 'cost', ar: 'التكلفة', en: 'Cost' },
                                    { key: 'price', ar: 'سعر البيع', en: 'Sell price' },
                                    { key: 'gap', ar: 'الفارق', en: 'Gap', danger: true },
                                ]}
                                actionLabel={ar ? 'تعديل السعر' : 'Edit price'}
                                onOpenAll={() => openDrilldown('negative-margin')}
                                onOpenRow={go}
                            />
                        </WidgetErrorBoundary>
                    </div>
                    <WidgetErrorBoundary title={ar ? 'نمو المنتجات والقوائم' : 'Products & menus growth'}>
                        <WidgetFrame
                            title={ar ? 'نمو المنتجات والقوائم (آخر 6 أشهر)' : 'Products & menus growth (last 6 months)'}
                            status={dashboard.status === 'loading' ? 'loading' : (payload.product_health.catalog_growth?.series?.length ? 'success' : 'empty')}
                            emptyText={empty}
                            actions={
                                routes.product_dashboard ? (
                                    <button type="button" className="ed-btn ed-btn-ghost ed-btn-sm" onClick={() => go(routes.product_dashboard)}>
                                        {ar ? 'لوحة المنتجات' : 'Product dashboard'}
                                    </button>
                                ) : null
                            }
                        >
                            <ProductGrowthChart data={payload.product_health.catalog_growth} locale={locale} />
                        </WidgetFrame>
                    </WidgetErrorBoundary>
                </>
            )}

            {payload.inventory_health?.visible && (
                <>
                    <WidgetErrorBoundary title={ar ? 'صحة المخزون' : 'Inventory health'}>
                        {dashboard.status === 'loading' ? (
                            <div className="ed-signals ed-signals-4">
                                {Array.from({ length: 4 }).map((_, i) => (
                                    <section key={i} className="ed-card"><div className="ed-skel" /></section>
                                ))}
                            </div>
                        ) : (
                            <InventoryHealthSignals
                                health={payload.inventory_health}
                                locale={locale}
                                onOpenSignal={(id) => openDrilldown(id)}
                            />
                        )}
                    </WidgetErrorBoundary>
                    <WidgetErrorBoundary title={ar ? 'اتجاه الحركة الشهرية' : 'Monthly movement'}>
                        <WidgetFrame
                            title={ar ? 'اتجاه الحركة الشهرية (وارد / صادر)' : 'Monthly movement (inbound / outbound)'}
                            status={dashboard.status === 'loading' ? 'loading' : (payload.inventory_health.movement?.series?.length ? 'success' : 'empty')}
                            emptyText={empty}
                            actions={
                                routes.inventory ? (
                                    <button type="button" className="ed-btn ed-btn-ghost ed-btn-sm" onClick={() => go(routes.inventory)}>
                                        {ar ? 'لوحة المخزون' : 'Inventory dashboard'}
                                    </button>
                                ) : null
                            }
                        >
                            <ProductGrowthChart data={payload.inventory_health.movement} locale={locale} />
                        </WidgetFrame>
                    </WidgetErrorBoundary>
                    <div className="ed-product-lists">
                        <WidgetErrorBoundary title={ar ? 'أعلى / أقل رصيد لكل مستودع' : 'Warehouse stock extremes'}>
                            <ProductIssueTable
                                block={payload.inventory_health.warehouses}
                                locale={locale}
                                columns={[
                                    { key: 'name', ar: 'المستودع', en: 'Warehouse' },
                                    { key: 'highest', ar: 'أعلى رصيد', en: 'Highest' },
                                    { key: 'lowest', ar: 'أقل رصيد', en: 'Lowest' },
                                ]}
                                actionLabel={ar ? 'تصفية المستودع' : 'Filter warehouse'}
                                onRowAction={(row) => {
                                    if (row.warehouse_id) applyPatch({ branch_id: String(row.warehouse_id) });
                                }}
                            />
                        </WidgetErrorBoundary>
                        <WidgetErrorBoundary title={ar ? 'أهم العناصر الحرجة' : 'Critical items'}>
                            <ProductIssueTable
                                block={payload.inventory_health.critical}
                                locale={locale}
                                columns={[
                                    { key: 'name', ar: 'الصنف', en: 'Item' },
                                    { key: 'warehouse', ar: 'المستودع', en: 'Warehouse' },
                                    { key: 'qty', ar: 'الرصيد', en: 'Qty', danger: true },
                                ]}
                                actionLabel={ar ? 'فتح المخزون' : 'Open inventory'}
                                onOpenAll={() => openDrilldown('critical-stock')}
                                onOpenRow={go}
                            />
                        </WidgetErrorBoundary>
                    </div>
                </>
            )}

            <div className="ed-bottom">
                <WidgetErrorBoundary title={ar ? 'مركز القرار' : 'Decision center'}>
                    {dashboard.status === 'loading' ? (
                        <section className="ed-card"><div className="ed-skel" /></section>
                    ) : (
                        <DecisionCenter alerts={payload.decision_alerts} locale={locale} onNavigate={go} />
                    )}
                </WidgetErrorBoundary>
                <WidgetErrorBoundary title={ar ? 'تحليل المبيعات الديناميكي' : 'Dynamic sales analysis'}>
                    <WidgetFrame
                        title={ar ? 'تحليل المبيعات الديناميكي' : 'Dynamic sales analysis'}
                        status={dashboard.status === 'loading' ? 'loading' : (analysisItems.length ? 'success' : (dashboard.status === 'error' ? 'error' : 'empty'))}
                        error={dashboard.error}
                        emptyText={empty}
                        actions={
                            <div className="ed-tabs">
                                {DIMENSIONS.map((d) => (
                                    <button
                                        key={d.id}
                                        type="button"
                                        className={`ed-tab ${dimKey === d.id ? 'is-active' : ''}`}
                                        onClick={() => setSalesDimension(d.id)}
                                    >
                                        {ar ? d.ar : d.en}
                                    </button>
                                ))}
                            </div>
                        }
                    >
                        <HorizontalBarChart items={analysisItems} color="#F28705" />
                    </WidgetFrame>
                </WidgetErrorBoundary>
                {showBranches && (
                    <WidgetErrorBoundary title={ar ? 'المبيعات حسب الفروع' : 'Sales by branch'}>
                        <WidgetFrame
                            title={ar ? 'المبيعات حسب الفروع' : 'Sales by branch'}
                            status="success"
                            emptyText={empty}
                        >
                            <HorizontalBarChart
                                items={payload.top_branches}
                                onSelect={(item) => {
                                    if (item.id) applyPatch({ branch_id: String(item.id) });
                                }}
                            />
                        </WidgetFrame>
                    </WidgetErrorBoundary>
                )}
            </div>

            <DrillDownDrawer
                open={drawer.open}
                payload={drawer.data}
                status={drawer.status}
                error={drawer.error}
                locale={locale}
                onClose={closeDrawer}
                onOpenRow={(row) => go(row.url)}
                onOpenReport={(url) => go(url)}
            />
        </div>
    );
}
