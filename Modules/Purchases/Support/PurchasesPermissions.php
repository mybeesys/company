<?php

namespace Modules\Purchases\Support;

/**
 * Central Purchases EMS permission names (must match dashboard-permissions.php).
 */
final class PurchasesPermissions
{
    public const ALL_SHOW = 'purchases.all.show';

    public const ALL_PRINT = 'purchases.all.print';

    public const ALL_CREATE = 'purchases.all.create';

    public const ALL_UPDATE = 'purchases.all.update';

    public const ALL_DELETE = 'purchases.all.delete';

    public const DASHBOARD_SHOW = 'purchases.Dashboard.show';

    public const SUPPLIERS_SHOW = 'purchases.Suppliers.show';

    public const SUPPLIERS_PRINT = 'purchases.Suppliers.print';

    public const SUPPLIERS_CREATE = 'purchases.Suppliers.create';

    public const SUPPLIERS_UPDATE = 'purchases.Suppliers.update';

    public const SUPPLIERS_DELETE = 'purchases.Suppliers.delete';

    public const SUPPLIER_ACTIVATE = 'purchases.Activate supplier.create';

    public const SUPPLIER_DEACTIVATE = 'purchases.Deactivate supplier.create';

    public const ORDERS_SHOW = 'purchases.Purchase Orders.show';

    public const ORDERS_PRINT = 'purchases.Purchase Orders.print';

    public const ORDERS_CREATE = 'purchases.Purchase Orders.create';

    public const INVOICES_SHOW = 'purchases.Purchase invoices.show';

    public const INVOICES_PRINT = 'purchases.Purchase invoices.print';

    public const INVOICES_CREATE = 'purchases.Purchase invoices.create';

    public const CONVERT_PO = 'purchases.Create invoice from purchase order.create';

    public const ADD_PAYMENT = 'purchases.Add payment to invoice.create';

    public const SHOW_PAYMENTS = 'purchases.Show invoice payments.show';

    public const CREATE_INVOICE_RETURN = 'purchases.Create purchase return.create';

    public const RETURNS_SHOW = 'purchases.Purchase returns.show';

    public const RETURNS_PRINT = 'purchases.Purchase returns.print';

    public const RETURNS_CREATE = 'purchases.Purchase returns.create';

    public const REFERENCE_INVOICE_SHOW = 'purchases.Reference invoice.show';

    public const VOUCHERS_SHOW = 'purchases.Supplier vouchers.show';

    public const VOUCHERS_PRINT = 'purchases.Supplier vouchers.print';

    public const VOUCHERS_CREATE = 'purchases.Supplier vouchers.create';

    public const VOUCHERS_UPDATE = 'purchases.Supplier vouchers.update';

    public const VOUCHERS_DELETE = 'purchases.Supplier vouchers.delete';

    /**
     * Map a purchases document type + CRUD action to the EMS permission name.
     * Returns null when the type is not a purchases document (sales stay unconstrained here).
     */
    public static function forTransactionType(?string $type, string $action): ?string
    {
        $invoices = [
            'show' => self::INVOICES_SHOW,
            'print' => self::INVOICES_PRINT,
            'create' => self::INVOICES_CREATE,
        ];

        $map = [
            'purchases' => $invoices,
            'purchase' => $invoices,
            'purchases-order' => [
                'show' => self::ORDERS_SHOW,
                'print' => self::ORDERS_PRINT,
                'create' => self::ORDERS_CREATE,
            ],
            'purchases-return' => [
                'show' => self::RETURNS_SHOW,
                'print' => self::RETURNS_PRINT,
                'create' => self::RETURNS_CREATE,
            ],
        ];

        return $map[$type ?? ''][$action] ?? null;
    }

    public static function isPurchasesTransactionType(?string $type): bool
    {
        return self::forTransactionType($type, 'show') !== null;
    }

    /**
     * Permissions that grant access to the purchases dashboard (specific + any list show).
     *
     * @return list<string>
     */
    public static function dashboardAccess(): array
    {
        return [
            self::DASHBOARD_SHOW,
            self::INVOICES_SHOW,
            self::ORDERS_SHOW,
            self::RETURNS_SHOW,
            self::SUPPLIERS_SHOW,
            self::VOUCHERS_SHOW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentShowAny(): array
    {
        return [
            self::INVOICES_SHOW,
            self::ORDERS_SHOW,
            self::RETURNS_SHOW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentCreateAny(): array
    {
        return [
            self::INVOICES_CREATE,
            self::ORDERS_CREATE,
            self::RETURNS_CREATE,
        ];
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        return array_values(array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'purchases.')
        ));
    }
}
