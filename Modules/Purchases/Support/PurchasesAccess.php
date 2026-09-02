<?php

namespace Modules\Purchases\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Support\DashboardAccess;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;

/**
 * Purchases web authorization: EMS dashboard permissions with purchases document-type mapping.
 */
final class PurchasesAccess
{
    /**
     * @param  string|list<string>  $permissions
     */
    public static function can(string|array $permissions, ?Authenticatable $user = null): bool
    {
        return DashboardAccess::allows($user ?? auth()->user(), $permissions);
    }

    /**
     * @param  string|list<string>  $permissions
     */
    public static function authorize(string|array $permissions, ?Authenticatable $user = null): void
    {
        DashboardAccess::authorize($user ?? auth()->user(), $permissions);
    }

    public static function authorizeTransaction(Transaction $transaction, string $action): void
    {
        $permission = PurchasesPermissions::forTransactionType($transaction->type, $action);
        if ($permission === null) {
            return;
        }

        self::authorize($permission);
    }

    public static function canTransaction(Transaction $transaction, string $action): bool
    {
        $permission = PurchasesPermissions::forTransactionType($transaction->type, $action);
        if ($permission === null) {
            return true;
        }

        return self::can($permission);
    }

    public static function authorizeSupplier(?Contact $contact, string $action): void
    {
        if (! $contact || $contact->business_type !== 'supplier') {
            return;
        }

        self::authorize(self::supplierPermission($action));
    }

    public static function canSupplier(?Contact $contact, string $action): bool
    {
        if (! $contact || $contact->business_type !== 'supplier') {
            return true;
        }

        return self::can(self::supplierPermission($action));
    }

    public static function isSupplierReceipt(?TransactionPayments $payment): bool
    {
        if (! $payment) {
            return false;
        }

        $type = $payment->transaction?->type;

        if (in_array($type, ['sell', 'sell-return', 'quotation'], true)) {
            return false;
        }

        if (in_array($type, ['purchases', 'purchase', 'purchases-return', 'purchases-order'], true)) {
            return true;
        }

        return $payment->client?->business_type === 'supplier';
    }

    public static function authorizeReceipt(TransactionPayments $payment, string $action): void
    {
        if (! self::isSupplierReceipt($payment)) {
            return;
        }

        self::authorize(self::voucherPermission($action));
    }

    public static function canReceipt(TransactionPayments $payment, string $action): bool
    {
        if (! self::isSupplierReceipt($payment)) {
            return true;
        }

        return self::can(self::voucherPermission($action));
    }

    public static function canAddPayment(Transaction $transaction): bool
    {
        if (in_array($transaction->type, ['purchases', 'purchase', 'purchases-return'], true)) {
            return self::can(PurchasesPermissions::ADD_PAYMENT);
        }

        return true;
    }

    public static function canShowPayments(Transaction $transaction): bool
    {
        if (in_array($transaction->type, ['purchases', 'purchase', 'purchases-return'], true)) {
            return self::can([PurchasesPermissions::SHOW_PAYMENTS, PurchasesPermissions::ADD_PAYMENT]);
        }

        return true;
    }

    public static function authorizeAddPayment(Transaction $transaction): void
    {
        if (in_array($transaction->type, ['purchases', 'purchase', 'purchases-return'], true)) {
            self::authorize(PurchasesPermissions::ADD_PAYMENT);
        }
    }

    public static function authorizeShowPayments(Transaction $transaction): void
    {
        if (in_array($transaction->type, ['purchases', 'purchase', 'purchases-return'], true)) {
            self::authorize([PurchasesPermissions::SHOW_PAYMENTS, PurchasesPermissions::ADD_PAYMENT]);
        }
    }

    private static function supplierPermission(string $action): string
    {
        return match ($action) {
            'show' => PurchasesPermissions::SUPPLIERS_SHOW,
            'create' => PurchasesPermissions::SUPPLIERS_CREATE,
            'update' => PurchasesPermissions::SUPPLIERS_UPDATE,
            'delete' => PurchasesPermissions::SUPPLIERS_DELETE,
            'activate' => PurchasesPermissions::SUPPLIER_ACTIVATE,
            'deactivate' => PurchasesPermissions::SUPPLIER_DEACTIVATE,
            default => PurchasesPermissions::SUPPLIERS_SHOW,
        };
    }

    private static function voucherPermission(string $action): string
    {
        return match ($action) {
            'show' => PurchasesPermissions::VOUCHERS_SHOW,
            'print' => PurchasesPermissions::VOUCHERS_PRINT,
            'create' => PurchasesPermissions::VOUCHERS_CREATE,
            'update' => PurchasesPermissions::VOUCHERS_UPDATE,
            'delete' => PurchasesPermissions::VOUCHERS_DELETE,
            default => PurchasesPermissions::VOUCHERS_SHOW,
        };
    }
}
