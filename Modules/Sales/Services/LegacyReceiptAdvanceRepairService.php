<?php

namespace Modules\Sales\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Support\AccountingNote;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;

/**
 * Legacy bug: overpayment / "رصيد متبقي" posted a single AR credit (or AP debit)
 * with no cash opposite and no TransactionPayments row.
 *
 * Effect on books:
 * - Customer AR decreased (credit) → looks like money received
 * - Cash/bank never increased → cash "disappeared" from TB
 * - /receipts and /receipt-vouchers stayed empty
 *
 * Repair: create payment row, add missing cash line, pair as receipt/payment voucher,
 * rewrite narration to «سند قبض — رصيد متبقي…».
 */
class LegacyReceiptAdvanceRepairService
{
    /**
     * @return list<array{mapping_id:int, skipped:?string, changes:list<string>, contact:?string, amount:?float}>
     */
    public function preview(?int $cashAccountFallback = null): array
    {
        return $this->run(false, $cashAccountFallback);
    }

    /**
     * @return list<array{mapping_id:int, skipped:?string, changes:list<string>, contact:?string, amount:?float}>
     */
    public function execute(?int $cashAccountFallback = null): array
    {
        return $this->run(true, $cashAccountFallback);
    }

    /**
     * @return list<array{mapping_id:int, skipped:?string, changes:list<string>, contact:?string, amount:?float}>
     */
    private function run(bool $execute, ?int $cashAccountFallback): array
    {
        $results = [];
        $mappingIds = $this->legacyMappingIds();

        foreach ($mappingIds as $mappingId) {
            $mapping = AccountingAccTransMapping::query()->find($mappingId);
            if (! $mapping) {
                continue;
            }
            $results[] = $this->repairMapping($mapping, $execute, $cashAccountFallback);
        }

        return $results;
    }

    /**
     * @return list<int>
     */
    private function legacyMappingIds(): array
    {
        $fromMappings = AccountingAccTransMapping::query()
            ->where(function ($q) {
                $q->where('note', 'like', '%رصيد متبقي للعميل%')
                    ->orWhere('note', 'like', '%رصيد متبقي للمورد%');
            })
            ->pluck('id');

        $fromLines = AccountingAccountsTransaction::query()
            ->whereNull('transaction_payment_id')
            ->where(function ($q) {
                $q->where('note', 'like', '%رصيد متبقي للعميل%')
                    ->orWhere('note', 'like', '%رصيد متبقي للمورد%');
            })
            ->whereNotNull('acc_trans_mapping_id')
            ->pluck('acc_trans_mapping_id');

        return $fromMappings->merge($fromLines)->unique()->sort()->values()->all();
    }

    /**
     * @return array{mapping_id:int, skipped:?string, changes:list<string>, contact:?string, amount:?float}
     */
    private function repairMapping(
        AccountingAccTransMapping $mapping,
        bool $execute,
        ?int $cashAccountFallback
    ): array {
        $result = [
            'mapping_id' => (int) $mapping->id,
            'skipped' => null,
            'changes' => [],
            'contact' => null,
            'amount' => null,
        ];

        $lines = AccountingAccountsTransaction::query()
            ->where('acc_trans_mapping_id', $mapping->id)
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            $result['skipped'] = 'no journal lines';

            return $result;
        }

        if ($lines->contains(fn ($line) => $line->transaction_payment_id)) {
            $result['skipped'] = 'already linked to a payment';

            return $result;
        }

        $blob = trim((string) $mapping->note.' '.$lines->pluck('note')->implode(' '));
        $isSupplier = str_contains($blob, 'رصيد متبقي للمورد');
        $contactLine = $this->findContactLine($lines, $isSupplier);
        if (! $contactLine) {
            $result['skipped'] = 'contact AR/AP line not found';

            return $result;
        }

        $contact = Contact::query()
            ->where('account_id', $contactLine->accounting_account_id)
            ->first();

        if (! $contact) {
            $result['skipped'] = 'no contact for account #'.$contactLine->accounting_account_id;

            return $result;
        }

        $result['contact'] = $contact->name;
        $amount = round((float) $contactLine->amount, 2);
        $result['amount'] = $amount;

        if ($amount <= 0) {
            $result['skipped'] = 'non-positive amount';

            return $result;
        }

        $hasOpposite = $lines->contains(function ($line) use ($contactLine, $isSupplier) {
            if ($line->id === $contactLine->id) {
                return false;
            }

            $expectedType = $isSupplier ? 'credit' : 'debit';

            return $line->type === $expectedType
                && abs((float) $line->amount - (float) $contactLine->amount) < 0.005;
        });

        $cashAccountId = $this->resolveCashAccountId(
            $contact,
            $contactLine,
            $cashAccountFallback
        );

        if (! $hasOpposite && ! $cashAccountId) {
            $result['skipped'] = 'cannot infer cash/bank account (pass --cash-account=ID)';

            return $result;
        }

        $voucherLabel = $isSupplier
            ? __('accounting::lang.payment_voucher')
            : __('accounting::lang.receipt_voucher');
        $advanceLabel = $isSupplier ? 'رصيد متبقي للمورد' : 'رصيد متبقي للعميل';
        $subType = $isSupplier ? 'payment_voucher' : 'receipt_voucher';
        $note = AccountingNote::compose($voucherLabel, $advanceLabel, $contact->name);
        $paidOn = $contactLine->operation_date
            ? date('Y-m-d H:i:s', strtotime((string) $contactLine->operation_date))
            : ($mapping->operation_date
                ? date('Y-m-d H:i:s', strtotime((string) $mapping->operation_date))
                : now()->format('Y-m-d H:i:s'));

        $result['changes'][] = sprintf(
            '%s #%d %s %.2f → add Dr/Cr cash + TransactionPayments (%s, cash_account=%s)',
            $isSupplier ? 'supplier' : 'customer',
            $contact->id,
            $contact->name,
            $amount,
            $subType,
            $cashAccountId ?: 'existing-opposite'
        );
        $result['changes'][] = 'TB: missing cash side will be posted so debit=credit on this voucher';

        if (! $execute) {
            return $result;
        }

        DB::transaction(function () use (
            $mapping,
            $lines,
            $contactLine,
            $contact,
            $isSupplier,
            $amount,
            $cashAccountId,
            $hasOpposite,
            $subType,
            $note,
            $paidOn
        ) {
            $txUtil = new TransactionUtils;
            $payment = TransactionPayments::create([
                'transaction_id' => null,
                'payment_type' => 'debit',
                'amount' => $amount,
                'method' => 'cash',
                'is_return' => 0,
                'note' => $note,
                'paid_on' => $paidOn,
                'created_by' => $contactLine->created_by ?: 1,
                'payment_for' => $contact->id,
                'payment_ref_no' => $txUtil->generateReferenceNumber(
                    $isSupplier ? 'purchase_payment' : 'sell_payment'
                ),
                'account_id' => $cashAccountId
                    ?: $this->oppositeAccountId($lines, $contactLine, $isSupplier),
            ]);

            $mapping->note = $note;
            $mapping->type = 'journal_entry';
            $mapping->save();

            if ($hasOpposite) {
                foreach ($lines as $line) {
                    $line->sub_type = $subType;
                    $line->note = $note;
                    $line->transaction_payment_id = $payment->id;
                    $line->save();
                }

                $debit = $lines->firstWhere('type', 'debit');
                $credit = $lines->firstWhere('type', 'credit');
                if ($debit && $credit) {
                    $debit->transaction_id = $credit->id;
                    $credit->transaction_id = $debit->id;
                    $debit->save();
                    $credit->save();
                }
            } elseif ($isSupplier) {
                // Existing: Dr supplier. Missing: Cr cash.
                $contactLine->type = 'debit';
                $contactLine->sub_type = $subType;
                $contactLine->note = $note;
                $contactLine->transaction_payment_id = $payment->id;
                $contactLine->operation_date = $paidOn;
                $contactLine->save();

                $cashLine = AccountingAccountsTransaction::create([
                    'amount' => $amount,
                    'accounting_account_id' => $cashAccountId,
                    'type' => 'credit',
                    'sub_type' => $subType,
                    'note' => $note,
                    'operation_date' => $paidOn,
                    'created_by' => $contactLine->created_by ?: 1,
                    'transaction_id' => $contactLine->id,
                    'transaction_payment_id' => $payment->id,
                    'acc_trans_mapping_id' => $mapping->id,
                    'cost_center_id' => $contactLine->cost_center_id,
                ]);

                $contactLine->transaction_id = $cashLine->id;
                $contactLine->save();
            } else {
                // Existing: Cr customer AR. Missing: Dr cash (this is where the money went missing).
                $contactLine->type = 'credit';
                $contactLine->sub_type = $subType;
                $contactLine->note = $note;
                $contactLine->transaction_payment_id = $payment->id;
                $contactLine->operation_date = $paidOn;
                $contactLine->save();

                $cashLine = AccountingAccountsTransaction::create([
                    'amount' => $amount,
                    'accounting_account_id' => $cashAccountId,
                    'type' => 'debit',
                    'sub_type' => $subType,
                    'note' => $note,
                    'operation_date' => $paidOn,
                    'created_by' => $contactLine->created_by ?: 1,
                    'transaction_id' => $contactLine->id,
                    'transaction_payment_id' => $payment->id,
                    'acc_trans_mapping_id' => $mapping->id,
                    'cost_center_id' => $contactLine->cost_center_id,
                ]);

                $contactLine->transaction_id = $cashLine->id;
                $contactLine->save();
            }

            AutoJournalGuard::assertBalanced((int) $mapping->id);
        });

        $result['changes'][] = 'repaired';

        return $result;
    }

    private function findContactLine(Collection $lines, bool $isSupplier): ?AccountingAccountsTransaction
    {
        $expectedType = $isSupplier ? 'debit' : 'credit';

        return $lines->first(function ($line) use ($expectedType) {
            if ($line->type !== $expectedType) {
                return false;
            }

            return Contact::query()->where('account_id', $line->accounting_account_id)->exists();
        });
    }

    private function resolveCashAccountId(
        Contact $contact,
        AccountingAccountsTransaction $contactLine,
        ?int $cashAccountFallback
    ): ?int {
        if ($cashAccountFallback && $cashAccountFallback !== (int) $contact->account_id) {
            return $cashAccountFallback;
        }

        $paidOnDate = $contactLine->operation_date
            ? date('Y-m-d', strtotime((string) $contactLine->operation_date))
            : null;

        $query = TransactionPayments::query()
            ->where('payment_for', $contact->id)
            ->whereNotNull('account_id')
            ->where('account_id', '<>', (int) $contact->account_id)
            ->orderByDesc('id');

        if ($paidOnDate) {
            $sameDay = (clone $query)->whereDate('paid_on', $paidOnDate)->value('account_id');
            if ($sameDay) {
                return (int) $sameDay;
            }
        }

        $anyPayment = $query->value('account_id');
        if ($anyPayment) {
            return (int) $anyPayment;
        }

        // Any other contact's recent receipt cash account.
        $anyTenantPayment = TransactionPayments::query()
            ->whereNotNull('account_id')
            ->where('account_id', '<>', (int) $contact->account_id)
            ->where('payment_type', 'debit')
            ->orderByDesc('id')
            ->value('account_id');
        if ($anyTenantPayment) {
            return (int) $anyTenantPayment;
        }

        return $this->defaultCashAccountId((int) $contact->account_id);
    }

    private function defaultCashAccountId(int $excludeAccountId): ?int
    {
        $account = AccountingAccount::query()
            ->where('id', '<>', $excludeAccountId)
            ->where(function ($q) {
                $q->where('gl_code', '1101')
                    ->orWhere('gl_code', 'like', '1101%')
                    ->orWhere('name_ar', 'like', '%صندوق%')
                    ->orWhere('name_ar', 'like', '%خزينة%')
                    ->orWhere('name_en', 'like', '%Cash%');
            })
            ->orderByRaw("CASE WHEN gl_code = '1101' THEN 0 WHEN gl_code LIKE '1101%' THEN 1 WHEN name_ar LIKE '%صندوق%' THEN 2 ELSE 3 END")
            ->orderBy('gl_code')
            ->first();

        return $account?->id ? (int) $account->id : null;
    }

    private function oppositeAccountId(
        Collection $lines,
        AccountingAccountsTransaction $contactLine,
        bool $isSupplier
    ): ?int {
        $expectedType = $isSupplier ? 'credit' : 'debit';
        $opposite = $lines->first(
            fn ($line) => $line->id !== $contactLine->id && $line->type === $expectedType
        );

        return $opposite ? (int) $opposite->accounting_account_id : null;
    }
}
