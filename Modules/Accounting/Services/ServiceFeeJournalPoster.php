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
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\General\Models\Transaction;

/**
 * Posts one independent «قيد رسوم خدمة» for collected fees that have a fee GL account.
 *
 * Taxable fee VAT stays inside the sales invoice journal (Accounts Routing VAT).
 * This entry posts fee net only:
 *   Dr customer AR (fee_amount)
 *   Cr fee account (fee_amount)
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
     * Fee nets that post separately — strip from sales JE revenue/AR only.
     * Fee tax remains in the sales invoice VAT line.
     *
     * @return array{fee_amount: float, fee_tax: float, gross: float}
     */
    public static function separatelyAccountedTotals(Transaction $transaction): array
    {
        $feeAmount = 0.0;

        $payload = $transaction->service_fees_payload;
        if (! is_array($payload)) {
            return ['fee_amount' => 0.0, 'fee_tax' => 0.0, 'gross' => 0.0];
        }

        foreach ($payload as $feeLine) {
            if (! is_array($feeLine) || ! self::feeLineIsSeparatelyAccounted($feeLine)) {
                continue;
            }

            $feeAmount += (float) ($feeLine['fee_amount'] ?? 0);
        }

        $feeAmount = round($feeAmount, 2);

        return [
            'fee_amount' => $feeAmount,
            // Intentionally 0: taxable fee VAT stays on the sales journal.
            'fee_tax' => 0.0,
            'gross' => $feeAmount,
        ];
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    public static function feeLineIsSeparatelyAccounted(array $feeLine): bool
    {
        return self::canBuildInvoiceJournal($feeLine);
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function canBuildInvoiceJournal(array $feeLine): bool
    {
        $direction = strtoupper((string) ($feeLine['fee_direction'] ?? EstablishmentServiceFee::DIRECTION_COLLECTED));
        if ($direction !== '' && $direction !== EstablishmentServiceFee::DIRECTION_COLLECTED) {
            return false;
        }

        return self::resolvedFeeAccountId($feeLine) > 0;
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function resolvedFeeAccountId(array $feeLine): int
    {
        foreach (['fee_account_id', 'revenue_account_id', 'credit_accounting_account_id'] as $key) {
            $id = (int) ($feeLine[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
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
        $feeAmount = round((float) ($feeLine['fee_amount'] ?? 0), 2);
        $feeAccountId = self::resolvedFeeAccountId($feeLine);

        if ($feeId <= 0 || $feeAmount <= 0 || $feeAccountId <= 0) {
            return null;
        }

        if (self::alreadyPosted($transaction, $feeId)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($transaction, $feeLine, $feeId, $feeAmount, $feeAccountId) {
                FiscalPeriodGatekeeper::assertPostable($transaction->transaction_date ?? now());

                $arAccountId = self::resolveArAccountId($transaction, $feeLine);
                if ($arAccountId <= 0) {
                    throw new \RuntimeException('Customer receivable account is required to post the service fee journal.');
                }

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

                AccountingAccountsTransaction::query()->create([
                    'amount' => $feeAmount,
                    'accounting_account_id' => $arAccountId,
                    'type' => 'debit',
                    'sub_type' => self::SUB_TYPE,
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'transaction_id' => (int) $transaction->id,
                    'transaction_payment_id' => null,
                    'acc_trans_mapping_id' => (int) $mapping->id,
                    'cost_center_id' => $costCenterId,
                    'note' => $note,
                ]);

                AccountingAccountsTransaction::query()->create([
                    'amount' => $feeAmount,
                    'accounting_account_id' => $feeAccountId,
                    'type' => 'credit',
                    'sub_type' => self::SUB_TYPE,
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'transaction_id' => (int) $transaction->id,
                    'transaction_payment_id' => null,
                    'acc_trans_mapping_id' => (int) $mapping->id,
                    'cost_center_id' => $costCenterId,
                    'note' => $note,
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

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function resolveArAccountId(Transaction $transaction, array $feeLine): int
    {
        $override = (int) ($feeLine['debit_accounting_account_id'] ?? 0);
        if ($override > 0) {
            return $override;
        }

        $client = Contact::query()->find($transaction->contact_id);
        $util = app(AccountingUtil::class);

        return $util->resolveCustomerReceivableAccountId($client, 'service fee');
    }

    private static function alreadyPosted(Transaction $transaction, int $feeId): bool
    {
        $legacyMarker = 'service_fee:'.$feeId;
        $idMarker = '[#'.$feeId.']';

        return AccountingAccountsTransaction::query()
            ->where('transaction_id', $transaction->id)
            ->where('sub_type', self::SUB_TYPE)
            ->where(function ($query) use ($legacyMarker, $idMarker) {
                $query->where('note', $legacyMarker)
                    ->orWhere('note', 'like', '%'.$idMarker.'%');
            })
            ->exists();
    }
}
