<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\Sales\Services\InvoiceServiceFeeAmounts;

/**
 * Posts one independent service-fee journal per memo:
 *  COLLECTED: Dr AR / Cr fee revenue (fee net). Fee VAT stays on the sales invoice VAT line.
 *  PAID: Dr expense (+ input VAT if taxable) / Cr the payment-method settlement GL.
 * Paid fees never change the customer invoice total.
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
            if (! is_array($feeLine) || InvoiceServiceFeeAmounts::isPaid($feeLine) || ! self::feeLineIsSeparatelyAccounted($feeLine)) {
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
        return self::resolvedFeeAccountId($feeLine) > 0;
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function resolvedFeeAccountId(array $feeLine): int
    {
        foreach (['fee_account_id', 'revenue_account_id', 'credit_accounting_account_id', 'expense_account_id'] as $key) {
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

        if (InvoiceServiceFeeAmounts::isPaid($feeLine)) {
            return self::postPaidFeeLine($transaction, $feeLine, $feeId, $feeAmount, $feeAccountId);
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
     * Merchant-borne fee: does not hit the customer invoice.
     * Dr expense [+ input VAT] / Cr payment-method clearing (cash/bank/settlement).
     *
     * @param  array<string, mixed>  $feeLine
     */
    private static function postPaidFeeLine(
        Transaction $transaction,
        array $feeLine,
        int $feeId,
        float $feeAmount,
        int $expenseAccountId
    ): ?AccountingAccTransMapping {
        $feeTax = round((float) ($feeLine['tax_amount'] ?? 0), 2);
        $taxable = (bool) ($feeLine['taxable'] ?? false);
        $inputVatId = $taxable && $feeTax > 0 ? self::resolveInputVatAccountId($feeLine) : 0;
        $settlementId = self::resolveSettlementAccountId($transaction, $feeLine);
        if ($settlementId <= 0) {
            throw new \RuntimeException(app()->getLocale() === 'ar'
                ? 'لا يمكن ترحيل رسم مدفوع بدون حساب طريقة الدفع / التسوية.'
                : 'Paid service fee cannot be posted without a payment-method / settlement account.');
        }

        $expenseAmount = $feeAmount;
        $vatAmount = 0.0;
        $creditAmount = $feeAmount;
        if ($taxable && $feeTax > 0) {
            if ($inputVatId > 0) {
                $vatAmount = $feeTax;
                $creditAmount = round($feeAmount + $feeTax, 2);
            } else {
                $expenseAmount = round($feeAmount + $feeTax, 2);
                $creditAmount = $expenseAmount;
            }
        }

        try {
            return DB::transaction(function () use (
                $transaction,
                $feeLine,
                $feeId,
                $expenseAccountId,
                $expenseAmount,
                $inputVatId,
                $vatAmount,
                $settlementId,
                $creditAmount
            ) {
                FiscalPeriodGatekeeper::assertPostable($transaction->transaction_date ?? now());

                $note = self::feeNote($transaction, $feeLine, $feeId);
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
                $base = [
                    'sub_type' => self::SUB_TYPE,
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'transaction_id' => (int) $transaction->id,
                    'transaction_payment_id' => null,
                    'acc_trans_mapping_id' => (int) $mapping->id,
                    'cost_center_id' => $costCenterId,
                    'note' => $note,
                ];

                AccountingAccountsTransaction::query()->create(array_merge($base, [
                    'amount' => $expenseAmount,
                    'accounting_account_id' => $expenseAccountId,
                    'type' => 'debit',
                ]));

                if ($vatAmount > 0 && $inputVatId > 0) {
                    AccountingAccountsTransaction::query()->create(array_merge($base, [
                        'amount' => $vatAmount,
                        'accounting_account_id' => $inputVatId,
                        'type' => 'debit',
                    ]));
                }

                AccountingAccountsTransaction::query()->create(array_merge($base, [
                    'amount' => $creditAmount,
                    'accounting_account_id' => $settlementId,
                    'type' => 'credit',
                ]));

                AutoJournalGuard::assertBalanced((int) $mapping->id);

                return $mapping;
            });
        } catch (\Throwable $e) {
            Log::error('Paid service fee journal posting failed', [
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
    private static function feeNote(Transaction $transaction, array $feeLine, int $feeId): string
    {
        $feeName = trim((string) (
            app()->getLocale() === 'ar'
                ? ($feeLine['name_ar'] ?? $feeLine['name'] ?? $feeLine['name_en'] ?? '')
                : ($feeLine['name_en'] ?? $feeLine['name'] ?? $feeLine['name_ar'] ?? '')
        ));
        $invoiceRef = (string) ($transaction->ref_no ?? $transaction->invoice_no ?? $transaction->id);
        $noteLabel = app()->getLocale() === 'ar' ? self::NOTE_AR : self::NOTE_EN;

        return $noteLabel
            .($feeName !== '' ? ' — '.$feeName : '')
            .' — '.$invoiceRef
            .' [#'.$feeId.']';
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function resolveInputVatAccountId(array $feeLine): int
    {
        foreach (['input_vat_account_id'] as $key) {
            $id = (int) ($feeLine[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return (int) (AccountsRoting::query()->where('type', 'purchases_vat_calculation')->value('account_id') ?? 0);
    }

    /**
     * @param  array<string, mixed>  $feeLine
     */
    private static function resolveSettlementAccountId(Transaction $transaction, array $feeLine): int
    {
        foreach (['settlement_account_id', 'debit_accounting_account_id'] as $key) {
            $id = (int) ($feeLine[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        $payment = TransactionPayments::query()
            ->where('transaction_id', $transaction->id)
            ->whereNotNull('account_id')
            ->orderBy('id')
            ->first();
        if ($payment && (int) $payment->account_id > 0) {
            return (int) $payment->account_id;
        }

        $establishmentId = (int) ($transaction->establishment_id ?? 0);
        $methodId = (int) ($payment->payment_method_id ?? $payment->method_id ?? 0);
        if ($establishmentId > 0 && $methodId > 0) {
            $resolved = EstablishmentPaymentAccountResolver::resolveForCashierPayment($establishmentId, $methodId);
            if ($resolved['ok'] ?? false) {
                return (int) $resolved['account_id'];
            }
        }

        return 0;
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
