<?php

namespace Modules\Employee\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Employee\Models\UserActivityLog;

class UserActivityPresenter
{
    /**
     * Short line for tables and lists.
     */
    public function headline(UserActivityLog $log): string
    {
        if ($log->event === UserActivityLogger::EVENT_AUTH) {
            return (string) ($log->description ?: __('employee::activity.event_auth'));
        }

        $subject = $this->subjectTitle($log);
        $entity = $this->entityLabel($log);
        $verb = $this->eventVerbPhrase($log->event);

        if ($subject !== null && $subject !== '') {
            return __('employee::activity.headline_with_subject', [
                'verb' => $verb,
                'subject' => $subject,
                'area' => $entity,
            ]);
        }

        return __('employee::activity.headline', [
            'verb' => $verb,
            'area' => $entity,
        ]);
    }

    public function areaLabel(UserActivityLog $log): string
    {
        if ($log->event === UserActivityLogger::EVENT_AUTH) {
            return __('employee::activity.area_security');
        }

        return $this->entityLabel($log);
    }

    public function eventLabel(string $event): string
    {
        return match ($event) {
            UserActivityLogger::EVENT_AUTH => __('employee::activity.event_auth'),
            UserActivityLogger::EVENT_CREATED => __('employee::activity.event_created'),
            UserActivityLogger::EVENT_UPDATED => __('employee::activity.event_updated'),
            UserActivityLogger::EVENT_DELETED => __('employee::activity.event_deleted'),
            UserActivityLogger::EVENT_VIEWED => __('employee::activity.event_viewed'),
            UserActivityLogger::EVENT_EXPORT => __('employee::activity.event_export'),
            default => __('employee::activity.event_other'),
        };
    }

    public function formatWhen(?Carbon $at): array
    {
        if ($at === null) {
            return ['absolute' => '—', 'relative' => ''];
        }

        $locale = app()->getLocale();

        return [
            'absolute' => $at->locale($locale)->translatedFormat('l، d M Y — h:i A'),
            'relative' => $at->locale($locale)->diffForHumans(),
        ];
    }

    /**
     * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
     */
    public function detailSections(UserActivityLog $log): array
    {
        $sections = [];

        $when = $this->formatWhen($log->created_at);
        $sections[] = [
            'title' => __('employee::activity.section_summary'),
            'rows' => array_filter([
                ['label' => __('employee::activity.when'), 'value' => $when['absolute']],
                ['label' => __('employee::activity.event_type'), 'value' => $this->eventLabel($log->event)],
                ['label' => __('employee::activity.area'), 'value' => $this->areaLabel($log)],
                ['label' => __('employee::activity.what_happened'), 'value' => $this->headline($log)],
            ], fn (array $row): bool => ($row['value'] ?? '') !== ''),
        ];

        $recordRows = $this->snapshotRows($log);
        if ($recordRows !== []) {
            $sections[] = [
                'title' => __('employee::activity.section_record'),
                'rows' => $recordRows,
            ];
        }

        $device = $this->deviceSummary($log->user_agent);
        $auditRows = array_filter([
            ['label' => __('employee::activity.device'), 'value' => $device],
            ['label' => __('employee::activity.ip_address'), 'value' => (string) ($log->ip_address ?? '')],
            ['label' => __('employee::activity.reference_number'), 'value' => '#'.$log->id],
        ], fn (array $row): bool => ($row['value'] ?? '') !== '' && $row['value'] !== '#');

        if ($auditRows !== []) {
            $sections[] = [
                'title' => __('employee::activity.section_audit'),
                'rows' => $auditRows,
            ];
        }

        return $sections;
    }

    /**
     * Human description stored on new log rows.
     */
    public function describeRequest(Request $request, string $event): string
    {
        $log = new UserActivityLog([
            'event' => $event,
            'route_name' => $request->route()?->getName(),
            'log_name' => explode('/', $request->path())[0] ?? null,
            'properties' => [
                'route_parameters' => $this->routeParamsFromRequest($request),
                'subjects' => $this->subjectsFromRequest($request),
            ],
        ]);

        return $this->headline($log);
    }

    private function eventVerbPhrase(string $event): string
    {
        return match ($event) {
            UserActivityLogger::EVENT_CREATED => __('employee::activity.verb_created'),
            UserActivityLogger::EVENT_UPDATED => __('employee::activity.verb_updated'),
            UserActivityLogger::EVENT_DELETED => __('employee::activity.verb_deleted'),
            UserActivityLogger::EVENT_VIEWED => __('employee::activity.verb_viewed'),
            UserActivityLogger::EVENT_EXPORT => __('employee::activity.verb_export'),
            default => __('employee::activity.verb_other'),
        };
    }

    private function entityLabel(UserActivityLog $log): string
    {
        $route = strtolower((string) ($log->route_name ?? ''));
        $segment = strtolower((string) ($log->log_name ?? ''));
        $path = strtolower((string) parse_url($log->url ?? '', PHP_URL_PATH));

        foreach ($this->areaMap() as $needle => $label) {
            if ($needle === '') {
                continue;
            }
            $n = strtolower($needle);
            if ($this->needleMatchesArea($n, $route, $segment, $path)) {
                return $label;
            }
        }

        if ($segment !== '') {
            return Str::headline(str_replace(['-', '_'], ' ', $segment));
        }

        return __('employee::activity.area_general');
    }

    private function needleMatchesArea(string $needle, string $route, string $segment, string $path): bool
    {
        if ($segment === $needle) {
            return true;
        }

        if (str_contains($route, $needle)) {
            return true;
        }

        if (str_ends_with($needle, '.') && str_starts_with($route, $needle)) {
            return true;
        }

        if (! str_ends_with($needle, '.') && str_starts_with($route, $needle.'.')) {
            return true;
        }

        if ($path !== '' && (str_contains($path, '/'.$needle) || str_contains($path, '/'.$needle.'/'))) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, string> needle => resolved label
     */
    private function areaMap(): array
    {
        $keys = array_merge($this->defaultAreaLabelKeys(), config('employee.activity_log.area_labels', []));
        $map = [];
        foreach ($keys as $needle => $labelKey) {
            $map[(string) $needle] = $this->resolveAreaLabel((string) $labelKey);
        }

        return $map;
    }

    /**
     * Ordered list: more specific needles first.
     *
     * @return array<string, string> needle => translation key
     */
    private function defaultAreaLabelKeys(): array
    {
        return [
            // ZATCA / الفوترة الإلكترونية
            'zatca-einvoicing' => 'menuItemLang.zatca_einvoicing',
            'zatca-settings' => 'menuItemLang.zatca_settings',
            'zatca.' => 'menuItemLang.zatca_einvoicing',

            // كاشير / نقاط البيع
            'cashier-payment-methods' => 'menuItemLang.cashier_payment_methods',
            'cashier-orders' => 'menuItemLang.cashier_order_settings',
            'cashier-service-fees' => 'menuItemLang.cashier_service_fees',
            'internal-consumption' => 'menuItemLang.internal_consumption_types',
            'cashier-settings.' => 'employee::activity.area_cashier',
            'cashier' => 'employee::activity.area_cashier',
            'cash-register' => 'employee::activity.area_cash_register',
            'register-report' => 'menuItemLang.Register-Report',

            // شاشات العرض
            'screen.' => 'menuItemLang.screen_module',
            'promo' => 'employee::activity.area_screens_promo',
            'playlist' => 'employee::activity.area_screens_playlist',
            'device' => 'menuItemLang.devices',

            // تقارير محاسبية (محددة قبل accounting العام)
            'income-statement' => 'menuItemLang.income-statement',
            'trial-balance' => 'menuItemLang.trial-balance',
            'balance_sheet' => 'menuItemLang.balance_sheet',
            'cash-flow' => 'menuItemLang.cash-flow',
            'ledger' => 'menuItemLang.ledger',
            'journal-report' => 'menuItemLang.journal-report',
            'accounting_reports' => 'menuItemLang.accounting_reports',
            'payment-reports' => 'menuItemLang.payment-reports',
            'sell-payment-report' => 'menuItemLang.sell-payment-report',
            'purchase-payment-report' => 'menuItemLang.purchase-payment-report',
            'product-sales-report' => 'menuItemLang.product-sales-report',
            'profit-loss' => 'menuItemLang.Profit-Loss',
            'register' => 'menuItemLang.Register-Report',

            // مبيعات ومشتريات
            'sell-return' => 'menuItemLang.sell-return',
            'purchases-return' => 'menuItemLang.purchases-return',
            'purchase-invoices' => 'menuItemLang.purchase_invoices',
            'purchase-order' => 'menuItemLang.purchase-order',
            'quotation' => 'menuItemLang.quotations',
            'coupon' => 'menuItemLang.coupons',
            'customer_receipt' => 'menuItemLang.customer_receipts',
            'supplier_receipt' => 'menuItemLang.supplier_receipts',
            'receipt_voucher' => 'menuItemLang.receipt_vouchers',
            'payment_voucher' => 'menuItemLang.payment_vouchers',
            'expense' => 'menuItemLang.expenses_manage',

            // منتجات ومخزون
            'import-product' => 'menuItemLang.importProduct',
            'import-ingredient' => 'menuItemLang.importIngredient',
            'product-barcode' => 'menuItemLang.productBarcode',
            'price-tier' => 'menuItemLang.priceTier',
            'linked-combo' => 'menuItemLang.linkedCombo',
            'ingredient' => 'menuItemLang.ingredient',
            'modifier' => 'menuItemLang.modifiers',
            'attribute' => 'menuItemLang.attribute',
            'waste' => 'menuItemLang.waste',
            'transfer' => 'menuItemLang.transfer',
            'prep' => 'menuItemLang.prep',
            'periodic' => 'menuItemLang.periodic',
            'rma' => 'menuItemLang.rma',

            // عملاء وموردون وفرنشايز
            'client-supplier' => 'menuItemLang.clients_suppliers_module',
            'franchise.' => 'menuItemLang.franchise',
            'custom-menu' => 'menuItemLang.customMenu',
            'crm' => 'menuItemLang.crm',

            // أفرع وشركة
            'establishment' => 'menuItemLang.establishments',
            'company' => 'menuItemLang.company_settings',
            'areas' => 'menuItemLang.areas',
            'tables' => 'menuItemLang.tables',
            'menu-qr' => 'menuItemLang.menu_qr',

            // موظفون وجدولة
            'dashboard-role' => 'menuItemLang.dashboard_roles',
            'pos-role' => 'menuItemLang.pos_roles',
            'timesheet-rule' => 'menuItemLang.timesheet_rule',
            'timecard' => 'menuItemLang.employees_working_hours',
            'shift' => 'menuItemLang.shift_schedule',
            'payroll' => 'menuItemLang.payroll',
            'adjustment' => 'menuItemLang.adjustments',
            'employees.' => 'menuItemLang.employees',
            'employee' => 'menuItemLang.employees',
            'activity.' => 'menuItemLang.user_activity',
            'permission' => 'employee::activity.area_permissions',
            'referrals' => 'menuItemLang.referrals',
            'my-companies' => 'menuItemLang.my_companies',
            'profile' => 'employee::activity.area_profile',

            // محاسبة عامة
            'chart-of-account' => 'menuItemLang.chart_of_accounts',
            'cost-center' => 'menuItemLang.costCenter',
            'journal' => 'menuItemLang.journalEntry',
            'accounting.' => 'menuItemLang.accounting',
            'accounting' => 'menuItemLang.accounting',

            // إعدادات
            'general-setting' => 'menuItemLang.general_setting',
            'accounting_settings' => 'menuItemLang.accounting_settings',
            'tax' => 'menuItemLang.taxes',
            'settings' => 'menuItemLang.general_setting',

            // لوحات ووحدات
            'dashboard-v2' => 'menuItemLang.dashboard',
            'dashboard' => 'menuItemLang.dashboard',
            'sales-dashbord' => 'menuItemLang.sales-dashbord',
            'purchase-dashbord' => 'menuItemLang.purchase-dashbord',
            'product_dashboard' => 'menuItemLang.product_dashboard',
            'inventory_dashboard' => 'menuItemLang.inventory_dashboard',

            'inventory.' => 'menuItemLang.inventory',
            'inventory' => 'menuItemLang.inventory',
            'product.' => 'menuItemLang.products',
            'product' => 'menuItemLang.products',
            'purchases.' => 'menuItemLang.purchases',
            'purchases' => 'menuItemLang.purchases',
            'sales.' => 'menuItemLang.sales',
            'sales' => 'menuItemLang.sales',
            'sell.' => 'menuItemLang.sales',
            'purchase.' => 'menuItemLang.purchases',
            'report.' => 'menuItemLang.reports',
            'report' => 'menuItemLang.reports',
            'client' => 'employee::activity.area_contacts',
            'supplier' => 'employee::activity.area_contacts',
            'schedule' => 'menuItemLang.schedules',
        ];
    }

    private function resolveAreaLabel(string $key): string
    {
        if (str_contains($key, '::') || str_starts_with($key, 'menuItemLang.')) {
            $translated = __($key);

            return $translated !== $key ? $translated : Str::headline(str_replace(['menuItemLang.', '_', '.'], ['', ' ', ' '], $key));
        }

        return $key;
    }

    private function subjectTitle(UserActivityLog $log): ?string
    {
        $props = $log->properties ?? [];
        $subjects = $props['subjects'] ?? null;
        if (is_array($subjects)) {
            foreach ($subjects as $snapshot) {
                if (! is_array($snapshot)) {
                    continue;
                }
                $title = $this->titleFromAttributes($snapshot);
                if ($title !== null) {
                    return $title;
                }
            }
        }

        $params = $props['route_parameters'] ?? [];
        if (is_array($params)) {
            foreach ($params as $value) {
                if (is_string($value) && str_contains($value, ':')) {
                    [, $id] = explode(':', $value, 2);

                    return __('employee::activity.record_number', ['id' => $id]);
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function titleFromAttributes(array $attributes): ?string
    {
        foreach (['name', 'name_en', 'title', 'reference', 'reference_no', 'invoice_no', 'code', 'email', 'user_name'] as $key) {
            if (! empty($attributes[$key]) && is_scalar($attributes[$key])) {
                return (string) $attributes[$key];
            }
        }

        if (! empty($attributes['id'])) {
            return __('employee::activity.record_number', ['id' => $attributes['id']]);
        }

        return null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function snapshotRows(UserActivityLog $log): array
    {
        $props = $log->properties ?? [];
        $subjects = $props['subjects'] ?? null;
        if (! is_array($subjects)) {
            return [];
        }

        $rows = [];
        foreach ($subjects as $snapshot) {
            if (! is_array($snapshot)) {
                continue;
            }
            foreach ($this->presentableFields($snapshot) as $label => $value) {
                $rows[] = ['label' => $label, 'value' => $value];
            }
            if (count($rows) >= 12) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string>
     */
    private function presentableFields(array $snapshot): array
    {
        $skip = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'id'];
        $out = [];

        foreach ($snapshot as $key => $value) {
            if (in_array($key, $skip, true) || $value === null || $value === '') {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $label = __('employee::activity.fields.'.$key);
            if ($label === 'employee::activity.fields.'.$key) {
                $label = Str::headline(str_replace('_', ' ', $key));
            }
            $out[$label] = is_bool($value)
                ? ($value ? __('employee::activity.yes') : __('employee::activity.no'))
                : (string) $value;
            if (count($out) >= 8) {
                break;
            }
        }

        return $out;
    }

    private function deviceSummary(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return '';
        }

        $ua = strtolower($userAgent);
        $browser = match (true) {
            str_contains($ua, 'edg') => 'Microsoft Edge',
            str_contains($ua, 'chrome') => 'Google Chrome',
            str_contains($ua, 'firefox') => 'Firefox',
            str_contains($ua, 'safari') => 'Safari',
            default => __('employee::activity.device_web'),
        };

        $os = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'mac') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => __('employee::activity.device_unknown_os'),
        };

        return $browser.' · '.$os;
    }

    /**
     * @return array<string, mixed>
     */
    private function routeParamsFromRequest(Request $request): array
    {
        $out = [];
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $out[$key] = class_basename($value).':'.$value->getKey();
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function subjectsFromRequest(Request $request): ?array
    {
        $snapshots = [];
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                $snapshots[$key] = $value->attributesToArray();
            }
        }

        return $snapshots === [] ? null : $snapshots;
    }
}
