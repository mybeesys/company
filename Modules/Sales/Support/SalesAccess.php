<?php

namespace Modules\Sales\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Support\DashboardAccess;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;

/**
 * Sales web authorization: EMS dashboard permissions with sales document-type mapping.
 */
final class SalesAccess
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
        $permission = SalesPermissions::forTransactionType($transaction->type, $action);
        if ($permission === null) {
            return;
        }

        self::authorize($permission);
    }

    public static function canTransaction(Transaction $transaction, string $action): bool
    {
        $permission = SalesPermissions::forTransactionType($transaction->type, $action);
        if ($permission === null) {
            return true;
        }

        return self::can($permission);
    }

    public static function authorizeCustomer(?Contact $contact, string $action): void
    {
        if (! $contact || $contact->business_type !== 'customer') {
            return;
        }

        self::authorize(self::customerPermission($action));
    }

    public static function canCustomer(?Contact $contact, string $action): bool
    {
        if (! $contact || $contact->business_type !== 'customer') {
            return true;
        }

        return self::can(self::customerPermission($action));
    }

    public static function isCustomerReceipt(?TransactionPayments $payment): bool
    {
        if (! $payment) {
            return false;
        }

        $type = $payment->transaction?->type;

        if (in_array($type, ['purchases', 'purchase', 'purchases-return', 'purchases-order'], true)) {
            return false;
        }

        if (in_array($type, ['sell', 'sell-return'], true)) {
            return true;
        }

        return $payment->client?->business_type === 'customer'
            || $payment->payment_type === 'debit';
    }

    public static function authorizeReceipt(TransactionPayments $payment, string $action): void
    {
        if (! self::isCustomerReceipt($payment)) {
            return;
        }

        self::authorize(self::receiptPermission($action));
    }

    public static function canReceipt(TransactionPayments $payment, string $action): bool
    {
        if (! self::isCustomerReceipt($payment)) {
            return true;
        }

        return self::can(self::receiptPermission($action));
    }

    public static function canAddPayment(Transaction $transaction): bool
    {
        if ($transaction->type === 'sell') {
            return self::can(SalesPermissions::ADD_PAYMENT);
        }

        if ($transaction->type === 'sell-return') {
            return self::can(SalesPermissions::RETURN_PAYMENTS);
        }

        return true;
    }

    public static function canShowPayments(Transaction $transaction): bool
    {
        if ($transaction->type === 'sell') {
            return self::can([SalesPermissions::SHOW_PAYMENTS, SalesPermissions::ADD_PAYMENT]);
        }

        if ($transaction->type === 'sell-return') {
            return self::can([SalesPermissions::SHOW_PAYMENTS, SalesPermissions::RETURN_PAYMENTS]);
        }

        return true;
    }

    public static function authorizeAddPayment(Transaction $transaction): void
    {
        if ($transaction->type === 'sell') {
            self::authorize(SalesPermissions::ADD_PAYMENT);

            return;
        }

        if ($transaction->type === 'sell-return') {
            self::authorize(SalesPermissions::RETURN_PAYMENTS);
        }
    }

    public static function authorizeShowPayments(Transaction $transaction): void
    {
        if ($transaction->type === 'sell') {
            self::authorize([SalesPermissions::SHOW_PAYMENTS, SalesPermissions::ADD_PAYMENT]);

            return;
        }

        if ($transaction->type === 'sell-return') {
            self::authorize([SalesPermissions::SHOW_PAYMENTS, SalesPermissions::RETURN_PAYMENTS]);
        }
    }

    public static function allowsSaleWithoutStock(?Authenticatable $user = null): bool
    {
        return self::can(SalesPermissions::ALLOW_SALE_WITHOUT_STOCK, $user);
    }

    private static function customerPermission(string $action): string
    {
        return match ($action) {
            'show' => SalesPermissions::CUSTOMERS_SHOW,
            'create' => SalesPermissions::CUSTOMERS_CREATE,
            'update' => SalesPermissions::CUSTOMERS_UPDATE,
            'delete' => SalesPermissions::CUSTOMERS_DELETE,
            'activate' => SalesPermissions::CUSTOMER_ACTIVATE,
            'deactivate' => SalesPermissions::CUSTOMER_DEACTIVATE,
            default => SalesPermissions::CUSTOMERS_SHOW,
        };
    }

    private static function receiptPermission(string $action): string
    {
        return match ($action) {
            'show' => SalesPermissions::RECEIPTS_SHOW,
            'print' => SalesPermissions::RECEIPTS_PRINT,
            'create' => SalesPermissions::RECEIPTS_CREATE,
            'update' => SalesPermissions::RECEIPTS_UPDATE,
            'delete' => SalesPermissions::RECEIPTS_DELETE,
            default => SalesPermissions::RECEIPTS_SHOW,
        };
    }
}
