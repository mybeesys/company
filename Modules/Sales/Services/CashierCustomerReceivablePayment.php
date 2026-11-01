<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\Sales\Support\TransactionPurpose;

/**
 * Cashier-only: a POS payment method whose GL is a customer contact account
 * is a receivable (due) settlement, not a cash collection.
 */
final class CashierCustomerReceivablePayment
{
    public static function invoiceIsDue(Request $request): bool
    {
        $purpose = TransactionPurpose::normalize($request->input('purpose'));
        if (in_array($purpose, [TransactionPurpose::WASTE, TransactionPurpose::INTERNAL_CONSUMPTION], true)) {
            return false;
        }
        if ((int) $request->input('internal_consumption_type_id') > 0) {
            return false;
        }

        return self::allPositivePaymentsUseCustomerAccounts($request);
    }

    public static function accountIdIsCustomerReceivable(?int $accountId): bool
    {
        if ($accountId === null || $accountId <= 0) {
            return false;
        }
        if (! Schema::hasTable('cs_contacts')) {
            return false;
        }

        try {
            return Contact::query()
                ->where('business_type', 'customer')
                ->where('account_id', $accountId)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function contactIdForAccount(int $accountId): ?int
    {
        if ($accountId <= 0 || ! Schema::hasTable('cs_contacts')) {
            return null;
        }

        try {
            $id = Contact::query()
                ->where('business_type', 'customer')
                ->where('account_id', $accountId)
                ->value('id');
        } catch (\Throwable) {
            return null;
        }

        return $id ? (int) $id : null;
    }

    public static function contactIdFromPayments(Request $request): ?int
    {
        foreach (self::positivePaymentAccountIds($request) as $accountId) {
            $contactId = self::contactIdForAccount($accountId);
            if ($contactId) {
                return $contactId;
            }
        }

        return null;
    }

    /**
     * True when every payment with amount uses a GL that belongs to a customer.
     * Mixed cash/bank + customer-account methods are left unchanged.
     */
    public static function allPositivePaymentsUseCustomerAccounts(Request $request): bool
    {
        $accountIds = self::positivePaymentAccountIds($request);
        if ($accountIds === []) {
            return false;
        }

        foreach ($accountIds as $accountId) {
            if (! self::accountIdIsCustomerReceivable($accountId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<int>
     */
    private static function positivePaymentAccountIds(Request $request): array
    {
        $payments = $request->input('payments');
        if (! is_array($payments) || $payments === []) {
            return [];
        }

        $establishmentId = (int) $request->input('establishment_id');
        if ($establishmentId <= 0) {
            return [];
        }

        $accountIds = [];
        foreach ($payments as $payment) {
            $amount = (float) (is_array($payment) ? ($payment['amount'] ?? 0) : 0);
            if ($amount <= 0) {
                continue;
            }

            $methodId = (int) (is_array($payment) ? ($payment['method_id'] ?? 0) : 0);
            if ($methodId === -1) {
                return [];
            }
            if ($methodId <= 0) {
                return [];
            }

            try {
                $accountId = EstablishmentPaymentAccountResolver::resolveAccountIdByMethodId(
                    $establishmentId,
                    $methodId
                );
            } catch (\Throwable) {
                return [];
            }

            if (! $accountId || $accountId <= 0) {
                return [];
            }

            $accountIds[] = (int) $accountId;
        }

        return $accountIds;
    }
}
