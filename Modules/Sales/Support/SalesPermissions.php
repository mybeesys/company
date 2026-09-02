<?php

namespace Modules\Sales\Support;

/**
 * Central Sales EMS permission names (must match dashboard-permissions.php).
 */
final class SalesPermissions
{
    public const ALL_SHOW = 'sales.all.show';

    public const ALL_PRINT = 'sales.all.print';

    public const ALL_CREATE = 'sales.all.create';

    public const ALL_UPDATE = 'sales.all.update';

    public const ALL_DELETE = 'sales.all.delete';

    public const DASHBOARD_SHOW = 'sales.Dashboard.show';

    public const CUSTOMERS_SHOW = 'sales.Customers.show';

    public const CUSTOMERS_CREATE = 'sales.Customers.create';

    public const CUSTOMERS_UPDATE = 'sales.Customers.update';

    public const CUSTOMERS_DELETE = 'sales.Customers.delete';

    public const CUSTOMER_ACTIVATE = 'sales.Active customer.create';

    public const CUSTOMER_DEACTIVATE = 'sales.Deactive customer.create';

    public const QUOTATIONS_SHOW = 'sales.Quotations.show';

    public const QUOTATIONS_PRINT = 'sales.Quotations.print';

    public const QUOTATIONS_CREATE = 'sales.Quotations.create';

    public const QUOTATIONS_UPDATE = 'sales.Quotations.update';

    public const QUOTATIONS_DELETE = 'sales.Quotations.delete';

    public const INVOICES_SHOW = 'sales.Sell invoices.show';

    public const INVOICES_PRINT = 'sales.Sell invoices.print';

    public const INVOICES_CREATE = 'sales.Sell invoices.create';

    public const CONVERT_QUOTATION = 'sales.Convert quotation to invoice.create';

    public const ALLOW_SALE_WITHOUT_STOCK = 'sales.Allow Sale Without Stock.create';

    public const ADD_PAYMENT = 'sales.Add Payment.create';

    public const SHOW_PAYMENTS = 'sales.Show Payments.create';

    public const CREATE_INVOICE_RETURN = 'sales.Create invoices return.create';

    public const RETURNS_SHOW = 'sales.Sell returns.show';

    public const RETURNS_PRINT = 'sales.Sell returns.print';

    public const RETURNS_CREATE = 'sales.Sell returns.create';

    public const REFERENCE_INVOICE_SHOW = 'sales.Reference invoice.show';

    public const RETURN_PAYMENTS = 'sales.Return payments.create';

    public const RECEIPTS_SHOW = 'sales.Customer receipts.show';

    public const RECEIPTS_PRINT = 'sales.Customer receipts.print';

    public const RECEIPTS_CREATE = 'sales.Customer receipts.create';

    public const RECEIPTS_UPDATE = 'sales.Customer receipts.update';

    public const RECEIPTS_DELETE = 'sales.Customer receipts.delete';

    public const COUPONS_SHOW = 'sales.coupons.show';

    public const COUPON_SHOW = 'sales.coupon.show';

    public const COUPON_CREATE = 'sales.coupon.create';

    public const COUPON_UPDATE = 'sales.coupon.update';

    public const COUPON_DELETE = 'sales.coupon.delete';

    /**
     * Map a sales document type + CRUD action to the EMS permission name.
     * Returns null when the type is not a sales document (purchases stay unconstrained here).
     */
    public static function forTransactionType(?string $type, string $action): ?string
    {
        $map = [
            'sell' => [
                'show' => self::INVOICES_SHOW,
                'print' => self::INVOICES_PRINT,
                'create' => self::INVOICES_CREATE,
            ],
            'quotation' => [
                'show' => self::QUOTATIONS_SHOW,
                'print' => self::QUOTATIONS_PRINT,
                'create' => self::QUOTATIONS_CREATE,
                'update' => self::QUOTATIONS_UPDATE,
                'delete' => self::QUOTATIONS_DELETE,
            ],
            'sell-return' => [
                'show' => self::RETURNS_SHOW,
                'print' => self::RETURNS_PRINT,
                'create' => self::RETURNS_CREATE,
            ],
        ];

        return $map[$type ?? ''][$action] ?? null;
    }

    public static function isSalesTransactionType(?string $type): bool
    {
        return self::forTransactionType($type, 'show') !== null;
    }

    /**
     * Permissions that grant access to the sales dashboard (specific + any list show).
     *
     * @return list<string>
     */
    public static function dashboardAccess(): array
    {
        return [
            self::DASHBOARD_SHOW,
            self::INVOICES_SHOW,
            self::QUOTATIONS_SHOW,
            self::RETURNS_SHOW,
            self::CUSTOMERS_SHOW,
            self::RECEIPTS_SHOW,
            self::COUPONS_SHOW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentShowAny(): array
    {
        return [
            self::INVOICES_SHOW,
            self::QUOTATIONS_SHOW,
            self::RETURNS_SHOW,
        ];
    }

    /**
     * @return list<string>
     */
    public static function sellDocumentCreateAny(): array
    {
        return [
            self::INVOICES_CREATE,
            self::QUOTATIONS_CREATE,
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
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'sales.')
        ));
    }
}
