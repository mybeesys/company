<?php

namespace Modules\General\Services\Notifications;

use Modules\Accounting\Support\AccountingPermissions;
use Modules\Employee\Support\DashboardHubPermissions;
use Modules\Employee\Support\EmployeePermissions;
use Modules\Inventory\Support\InventoryPermissions;
use Modules\Purchases\Support\PurchasesPermissions;
use Modules\Sales\Support\SalesPermissions;

/**
 * Canonical in-app (+ critical email) notification types.
 *
 * @phpstan-type CatalogEntry array{
 *   module: string,
 *   severity: 'info'|'warning'|'critical',
 *   ems_permissions: list<string>,
 *   setting_type: ?string,
 *   icon: string,
 *   notify_actor: bool,
 *   title: array{ar: string, en: string},
 *   body: array{ar: string, en: string}
 * }
 */
final class NotificationCatalog
{
    /**
     * @return array<string, CatalogEntry>
     */
    public static function all(): array
    {
        return [
            'new_sell' => [
                'module' => 'sales',
                'severity' => 'info',
                'ems_permissions' => [SalesPermissions::INVOICES_SHOW, SalesPermissions::DASHBOARD_SHOW],
                'setting_type' => 'new_sell',
                'icon' => 'ki-basket',
                'notify_actor' => true,
                'title' => ['ar' => 'فاتورة مبيعات جديدة', 'en' => 'New sales invoice'],
                'body' => ['ar' => 'تم اعتماد فاتورة مبيعات {ref_no} بمبلغ {amount}.', 'en' => 'Sales invoice {ref_no} posted for {amount}.'],
            ],
            'sell_return' => [
                'module' => 'sales',
                'severity' => 'warning',
                'ems_permissions' => [SalesPermissions::RETURNS_SHOW, SalesPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-arrow-circle-left',
                'notify_actor' => true,
                'title' => ['ar' => 'مرتجع مبيعات', 'en' => 'Sales return'],
                'body' => ['ar' => 'مرتجع مبيعات {ref_no} بمبلغ {amount}.', 'en' => 'Sales return {ref_no} for {amount}.'],
            ],
            'payment_received' => [
                'module' => 'sales',
                'severity' => 'info',
                'ems_permissions' => [SalesPermissions::RECEIPTS_SHOW, SalesPermissions::SHOW_PAYMENTS, SalesPermissions::DASHBOARD_SHOW, SalesPermissions::INVOICES_SHOW],
                'setting_type' => 'payment_received',
                'icon' => 'ki-dollar',
                'notify_actor' => true,
                'title' => ['ar' => 'دفعة مستلمة', 'en' => 'Payment received'],
                'body' => ['ar' => 'تم استلام دفعة {amount} للمرجع {ref_no}.', 'en' => 'Payment of {amount} received for {ref_no}.'],
            ],
            'customer_overdue_critical' => [
                'module' => 'sales',
                'severity' => 'critical',
                'ems_permissions' => [SalesPermissions::INVOICES_SHOW, SalesPermissions::RECEIPTS_SHOW, SalesPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-notification-bing',
                'notify_actor' => true,
                'title' => ['ar' => 'ذمم عميل متأخرة', 'en' => 'Overdue customer receivable'],
                'body' => ['ar' => 'العميل {party_name} لديه رصيد مستحق {amount} يتجاوز الحد المسموح.', 'en' => 'Customer {party_name} has overdue balance {amount}.'],
            ],
            'new_purchase' => [
                'module' => 'purchases',
                'severity' => 'info',
                'ems_permissions' => [PurchasesPermissions::INVOICES_SHOW, PurchasesPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-handcart',
                'notify_actor' => true,
                'title' => ['ar' => 'فاتورة مشتريات جديدة', 'en' => 'New purchase invoice'],
                'body' => ['ar' => 'تم اعتماد فاتورة مشتريات {ref_no} بمبلغ {amount}.', 'en' => 'Purchase invoice {ref_no} posted for {amount}.'],
            ],
            'purchase_order' => [
                'module' => 'purchases',
                'severity' => 'info',
                'ems_permissions' => [PurchasesPermissions::INVOICES_SHOW, PurchasesPermissions::DASHBOARD_SHOW],
                'setting_type' => 'purchase_order',
                'icon' => 'ki-notepad',
                'notify_actor' => true,
                'title' => ['ar' => 'أمر شراء', 'en' => 'Purchase order'],
                'body' => ['ar' => 'أمر شراء {ref_no} بمبلغ {amount}.', 'en' => 'Purchase order {ref_no} for {amount}.'],
            ],
            'payment_paid' => [
                'module' => 'purchases',
                'severity' => 'info',
                'ems_permissions' => [PurchasesPermissions::INVOICES_SHOW, PurchasesPermissions::DASHBOARD_SHOW],
                'setting_type' => 'payment_paid',
                'icon' => 'ki-wallet',
                'notify_actor' => true,
                'title' => ['ar' => 'دفعة لمورد', 'en' => 'Supplier payment'],
                'body' => ['ar' => 'تم دفع {amount} للمورد — المرجع {ref_no}.', 'en' => 'Paid {amount} to supplier — ref {ref_no}.'],
            ],
            'supplier_overdue_critical' => [
                'module' => 'purchases',
                'severity' => 'critical',
                'ems_permissions' => [PurchasesPermissions::INVOICES_SHOW, PurchasesPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-notification-bing',
                'notify_actor' => true,
                'title' => ['ar' => 'مستحقات مورد حرجة', 'en' => 'Critical supplier payable'],
                'body' => ['ar' => 'المورد {party_name} لديه مستحقات {amount} تتطلب متابعة.', 'en' => 'Supplier {party_name} has payables of {amount} needing attention.'],
            ],
            'low_stock_alert_notification' => [
                'module' => 'inventory',
                'severity' => 'critical',
                'ems_permissions' => [InventoryPermissions::DASHBOARD_SHOW, InventoryPermissions::PRODUCT_SHOW],
                'setting_type' => 'low_stock_alert_notification',
                'icon' => 'ki-parcel',
                'notify_actor' => true,
                'title' => ['ar' => 'نقص مخزون', 'en' => 'Low stock alert'],
                'body' => ['ar' => 'الصنف {product_name} وصل إلى {total_qty} (الحد {threshold}).', 'en' => 'Item {product_name} is at {total_qty} (threshold {threshold}).'],
            ],
            'inventory_transfer' => [
                'module' => 'inventory',
                'severity' => 'info',
                'ems_permissions' => [InventoryPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-arrow-mix',
                'notify_actor' => true,
                'title' => ['ar' => 'تحويل مخزني', 'en' => 'Stock transfer'],
                'body' => ['ar' => 'تم تسجيل تحويل مخزني {ref_no}.', 'en' => 'Stock transfer {ref_no} recorded.'],
            ],
            'inventory_waste' => [
                'module' => 'inventory',
                'severity' => 'warning',
                'ems_permissions' => [InventoryPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-trash',
                'notify_actor' => true,
                'title' => ['ar' => 'هدر مخزون', 'en' => 'Inventory waste'],
                'body' => ['ar' => 'تم تسجيل هدر {ref_no}.', 'en' => 'Waste document {ref_no} recorded.'],
            ],
            'periodic_inventory_approved' => [
                'module' => 'inventory',
                'severity' => 'warning',
                'ems_permissions' => [InventoryPermissions::DASHBOARD_SHOW, AccountingPermissions::PERIODIC_SHOW, AccountingPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-questionnaire-tablet',
                'notify_actor' => true,
                'title' => ['ar' => 'اعتماد جرد دوري', 'en' => 'Periodic inventory approved'],
                'body' => ['ar' => 'تم اعتماد جرد دوري بتاريخ {count_date} للمنشأة {establishment}.', 'en' => 'Periodic count as of {count_date} approved for {establishment}.'],
            ],
            'journal_posted' => [
                'module' => 'accounting',
                'severity' => 'info',
                'ems_permissions' => [AccountingPermissions::JOURNAL_SHOW, AccountingPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-book',
                'notify_actor' => true,
                'title' => ['ar' => 'قيد يومية', 'en' => 'Journal posted'],
                'body' => ['ar' => 'تم ترحيل القيد {ref_no}.', 'en' => 'Journal entry {ref_no} posted.'],
            ],
            'fiscal_period_closed' => [
                'module' => 'accounting',
                'severity' => 'critical',
                'ems_permissions' => [AccountingPermissions::DASHBOARD_SHOW, DashboardHubPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-lock',
                'notify_actor' => true,
                'title' => ['ar' => 'إقفال فترة مالية', 'en' => 'Fiscal period closed'],
                'body' => ['ar' => 'تم إقفال الفترة {period_label}.', 'en' => 'Fiscal period {period_label} was closed.'],
            ],
            'accounting_routing_missing' => [
                'module' => 'accounting',
                'severity' => 'critical',
                'ems_permissions' => [AccountingPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-information-5',
                'notify_actor' => true,
                'title' => ['ar' => 'توجيه حسابات ناقص', 'en' => 'Missing account routing'],
                'body' => ['ar' => 'تعذّر الترحيل: {message}', 'en' => 'Posting blocked: {message}'],
            ],
            'created_emp' => [
                'module' => 'employee',
                'severity' => 'critical',
                'ems_permissions' => [EmployeePermissions::EMPLOYEES_SHOW, EmployeePermissions::EMPLOYEE_SHOW, DashboardHubPermissions::DASHBOARD_SHOW],
                'setting_type' => 'created_emp',
                'icon' => 'ki-profile-user',
                'notify_actor' => true,
                'title' => ['ar' => 'موظف جديد', 'en' => 'New employee'],
                'body' => ['ar' => 'تم إنشاء الموظف {employee_name}.', 'en' => 'Employee {employee_name} was created.'],
            ],
            'employee_roles_changed' => [
                'module' => 'employee',
                'severity' => 'warning',
                'ems_permissions' => [EmployeePermissions::EMPLOYEES_SHOW, EmployeePermissions::DASHBOARD_ROLES_SHOW],
                'setting_type' => null,
                'icon' => 'ki-security-user',
                'notify_actor' => true,
                'title' => ['ar' => 'تغيير صلاحيات موظف', 'en' => 'Employee roles updated'],
                'body' => ['ar' => 'تم تحديث أدوار/صلاحيات {employee_name}.', 'en' => 'Roles/permissions updated for {employee_name}.'],
            ],
            'new_order' => [
                'module' => 'reservation',
                'severity' => 'info',
                'ems_permissions' => [DashboardHubPermissions::DASHBOARD_SHOW],
                'setting_type' => 'new_order',
                'icon' => 'ki-shop',
                'notify_actor' => true,
                'title' => ['ar' => 'طلب طاولة جديد', 'en' => 'New table order'],
                'body' => ['ar' => 'طلب جديد على الطاولة {table_code}.', 'en' => 'New order on table {table_code}.'],
            ],
            'new_booking' => [
                'module' => 'reservation',
                'severity' => 'info',
                'ems_permissions' => [DashboardHubPermissions::DASHBOARD_SHOW],
                'setting_type' => 'new_booking',
                'icon' => 'ki-calendar',
                'notify_actor' => true,
                'title' => ['ar' => 'حجز طاولة', 'en' => 'Table reservation'],
                'body' => ['ar' => 'حجز جديد للطاولة {table_code}.', 'en' => 'New reservation for table {table_code}.'],
            ],
            'system_job_failed' => [
                'module' => 'system',
                'severity' => 'critical',
                'ems_permissions' => [DashboardHubPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-cross-circle',
                'notify_actor' => true,
                'title' => ['ar' => 'فشل مهمة نظام', 'en' => 'System job failed'],
                'body' => ['ar' => 'فشلت المهمة {job_name}: {message}', 'en' => 'Job {job_name} failed: {message}'],
            ],
            'system_config_gap' => [
                'module' => 'system',
                'severity' => 'warning',
                'ems_permissions' => [DashboardHubPermissions::DASHBOARD_SHOW, AccountingPermissions::DASHBOARD_SHOW],
                'setting_type' => null,
                'icon' => 'ki-setting-2',
                'notify_actor' => true,
                'title' => ['ar' => 'إعداد ناقص', 'en' => 'Configuration gap'],
                'body' => ['ar' => '{message}', 'en' => '{message}'],
            ],
        ];
    }

    /**
     * @return CatalogEntry|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
