<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\General\Models\Transaction;

/**
 * Posts one independent «قيد رسوم خدمة» journal per applied service fee
 * that has both debit and credit accounts configured.
 */
final class ServiceFeeJournalPoster
{
    public const SUB_TYPE = 'service_fee';

    public const NOTE_AR = 'قيد رسوم خدمة';

    public const NOTE_EN = 'Service fee entry';

    /**
     * @return list<AccountingAccTransMapping>
     */
    public static function postForTransaction(Transaction $transaction): array
    {
        if (! in_array((string) $transaction->type, ['sell'], true)) {
            return [];
        }

        $payload = $transaction->service_fees_payload;
        if (! is_array($payload) || $payload === []) {
            return [];
        }

        $posted = [];

        foreach ($payload as $feeLine) {
            if (! is_array($feeLine)) {
                continue;
            }

            $mapping = self::postFeeLine($transaction, $feeLine);
            if ($mapping) {
                $posted[] = $mapping;
            }
        }

        return $posted;
    }

    /**
     * Totals for fees that will be posted separately (strip from sales JE).
     *
     * @return array{fee_amount: float, fee_tax: float, gross: float}
     */
    public static function separatelyAccountedTotals(Transaction $transaction): array
    {
        $feeAmount = 0.0;
        $feeTax = 0.0;

        $payload = $transaction->service_fees_payload;
        if (! is_array($payload)) {
            return ['fee_amount' => 0.0, 'fee_tax' => 0.0, 'gross' => 0.0];
        }

        foreach ($payload as $feeLine) {
            if (! is_array($feeLine) || ! self::feeLineIsSeparatelyAccounted($feeLine)) {
                continue;
            }

            $feeAmount += (float) ($feeLine['fee_amount'] ?? 0);
            $feeTax += (float) ($feeLine['tax_amount'] ?? 0);
        }

        $feeAmount = round($feeAmount, 2);
        $feeTax = round($feeTax, 2);

        return [
            'fee_amount' => $feeAmount,
            'fee_tax' => $feeTax,
            'gross' => round($feeAmount + $feeTax, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    public static function feeLineIsSeparatelyAccounted(array $feeLine): bool
    {
        $debitId = (int) ($feeLine['debit_accounting_account_id'] ?? 0);
        $creditId = (int) ($feeLine['credit_accounting_account_id'] ?? 0);

        if ($debitId > 0 && $creditId > 0) {
            return true;
        }

        return (bool) ($feeLine['has_journal_accounts'] ?? false)
            && $debitId > 0
            && $creditId > 0;
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function postFeeLine(Transaction $transaction, array $feeLine): ?AccountingAccTransMapping
    {
        if (! self::feeLineIsSeparatelyAccounted($feeLine)) {
            return null;
        }

        $feeId = (int) ($feeLine['id'] ?? 0);
        $debitId = (int) ($feeLine['debit_accounting_account_id'] ?? 0);
        $creditId = (int) ($feeLine['credit_accounting_account_id'] ?? 0);
        $gross = round(
            (float) ($feeLine['fee_amount'] ?? 0) + (float) ($feeLine['tax_amount'] ?? 0),
            2
        );

        if ($feeId <= 0 || $debitId <= 0 || $creditId <= 0 || $gross <= 0) {
            return null;
        }

        if (self::alreadyPosted($transaction, $feeId)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($transaction, $feeLine, $feeId, $debitId, $creditId, $gross) {
                FiscalPeriodGatekeeper::assertPostable($transaction->transaction_date ?? now());

                $feeName = trim((string) (
                    app()->getLocale() === 'ar'
                        ? ($feeLine['name_ar'] ?? $feeLine['name'] ?? $feeLine['name_en'] ?? '')
                        : ($feeLine['name_en'] ?? $feeLine['name'] ?? $feeLine['name_ar'] ?? '')
                ));
                $invoiceRef = (string) ($transaction->ref_no ?? $transaction->invoice_no ?? $transaction->id);
                $noteLabel = app()->getLocale() === 'ar' ? self::NOTE_AR : self::NOTE_EN;
                $note = $noteLabel
                    .($feeName !== '' ? ' — '.$feeName : '')
                    .' — '.$invoiceRef
                    .' [#'.$feeId.']';

                $mapping = new AccountingAccTransMapping;
                $mapping->ref_no = AccountingUtil::generateReferenceNumber('journal_entry');
                $mapping->type = 'journal_entry';
                $mapping->is_manual = 0;
                $mapping->created_by = Auth::id() ?? $transaction->created_by ?? 1;
                $mapping->operation_date = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
                $mapping->note = $note;
                $mapping->save();

                $opDate = $mapping->operation_date;
                $userId = (int) ($mapping->created_by ?? 1);
                $costCenterId = $transaction->cost_center ? (int) $transaction->cost_center : null;
                $lineNote = 'service_fee:'.$feeId;

                AccountingAccountsTransaction::query()->create([
                    'amount' => $gross,
                    'accounting_account_id' => $debitId,
                    'type' => 'debit',
                    'sub_type' => self::SUB_TYPE,
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'transaction_id' => (int) $transaction->id,
                    'transaction_payment_id' => null,
                    'acc_trans_mapping_id' => (int) $mapping->id,
                    'cost_center_id' => $costCenterId,
                    'note' => $lineNote,
                ]);

                AccountingAccountsTransaction::query()->create([
                    'amount' => $gross,
                    'accounting_account_id' => $creditId,
                    'type' => 'credit',
                    'sub_type' => self::SUB_TYPE,
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'transaction_id' => (int) $transaction->id,
                    'transaction_payment_id' => null,
                    'acc_trans_mapping_id' => (int) $mapping->id,
                    'cost_center_id' => $costCenterId,
                    'note' => $lineNote,
                ]);

                AutoJournalGuard::assertBalanced((int) $mapping->id);

                return $mapping;
            });
        } catch (\Throwable $e) {
            Log::error('Service fee journal posting failed', [
                'transaction_id' => $transaction->id,
                'fee_id' => $feeId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private static function alreadyPosted(Transaction $transaction, int $feeId): bool
    {
        return AccountingAccountsTransaction::query()
            ->where('transaction_id', $transaction->id)
            ->where('sub_type', self::SUB_TYPE)
            ->where('note', 'service_fee:'.$feeId)
            ->exists();
    }
}
