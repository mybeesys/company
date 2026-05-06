<?php

namespace Modules\Accounting\Utils;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Accounting\Models\AccountingAccountsTransaction;

class StandaloneVoucherHelper
{
    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction} [debit, credit]
     */
    public static function receiptLines(int $lineId): array
    {
        return self::pairedLines($lineId, 'receipt_voucher');
    }

    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction} [debit, credit]
     */
    public static function paymentLines(int $lineId): array
    {
        return self::pairedLines($lineId, 'payment_voucher');
    }

    /**
     * @return array<string, mixed>
     */
    public static function receiptFormPayload(int $lineId): array
    {
        [$debit, $credit] = self::receiptLines($lineId);

        return [
            'account_id' => $debit->accounting_account_id,
            'from_account' => $credit->accounting_account_id,
            'paid_amount' => $debit->amount,
            'pament_on' => self::formatDateInput($debit->operation_date),
            'cost_center_id' => $debit->cost_center_id,
            'additionalNotes' => $debit->note ?? '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentFormPayload(int $lineId): array
    {
        [$debit, $credit] = self::paymentLines($lineId);

        return [
            'account_id' => $credit->accounting_account_id,
            'from_account' => $debit->accounting_account_id,
            'paid_amount' => $debit->amount,
            'pament_on' => self::formatDateInput($debit->operation_date),
            'cost_center_id' => $debit->cost_center_id,
            'additionalNotes' => $debit->note ?? '',
        ];
    }

    /**
     * @return array{0: AccountingAccountsTransaction, 1: AccountingAccountsTransaction}
     */
    private static function pairedLines(int $lineId, string $subType): array
    {
        $line = AccountingAccountsTransaction::query()
            ->where('sub_type', $subType)
            ->findOrFail($lineId);

        $other = self::partnerOrFail($line, $subType);

        $debit = $line->type === 'debit' ? $line : $other;
        $credit = $line->type === 'credit' ? $line : $other;

        if ($debit->type !== 'debit' || $credit->type !== 'credit') {
            throw (new ModelNotFoundException())->setModel(AccountingAccountsTransaction::class, [$lineId]);
        }

        return [$debit, $credit];
    }

    private static function partnerOrFail(AccountingAccountsTransaction $line, string $subType): AccountingAccountsTransaction
    {
        if (! $line->transaction_id) {
            throw (new ModelNotFoundException())->setModel(AccountingAccountsTransaction::class);
        }

        $other = AccountingAccountsTransaction::query()->find($line->transaction_id);
        if (! $other || $other->sub_type !== $subType) {
            throw (new ModelNotFoundException())->setModel(AccountingAccountsTransaction::class);
        }

        return $other;
    }

    private static function formatDateInput($operationDate): string
    {
        if ($operationDate === null || $operationDate === '') {
            return Carbon::now()->format('Y-m-d');
        }

        return Carbon::parse($operationDate)->format('Y-m-d');
    }
}
