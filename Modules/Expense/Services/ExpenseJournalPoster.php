<?php

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Expense\Models\Expense;
use Modules\Expense\Support\VatLedgerAccount;

final class ExpenseJournalPoster
{
    /**
     * Without tax: Dr expense (net), Cr treasury (gross).
     * With tax: Dr expense (net), Dr VAT input, Cr treasury (gross).
     */
    public static function post(Expense $expense): AccountingAccTransMapping
    {
        $gross = (float) $expense->getRawOriginal('amount');
        $tax = (float) $expense->getRawOriginal('tax');
        $net = $tax > 0 ? round($gross - $tax, 6) : $gross;

        $expenseAccountId = (int) $expense->debit_accounting_account_id;
        $treasuryAccountId = (int) $expense->credit_accounting_account_id;
        $costCenterId = $expense->cost_center_id ? (int) $expense->cost_center_id : null;

        $vatAccount = null;
        if ($tax > 0) {
            $vatAccount = VatLedgerAccount::resolve();
            if (! $vatAccount) {
                throw new \RuntimeException(__('expense::lang.vat_account_missing'));
            }
        }

        return DB::transaction(function () use ($expense, $gross, $tax, $net, $expenseAccountId, $treasuryAccountId, $costCenterId, $vatAccount) {
            FiscalPeriodGatekeeper::assertPostable($expense->date);

            $mapping = new AccountingAccTransMapping;
            $mapping->ref_no = AccountingUtil::generateReferenceNumber('journal_entry');
            $mapping->type = 'journal_entry';
            $mapping->is_manual = 0;
            $mapping->created_by = Auth::id();
            $mapping->operation_date = $expense->date->format('Y-m-d H:i:s');
            $mapping->note = '[Expense #'.$expense->id.'] '.mb_substr((string) $expense->description, 0, 500);
            $mapping->save();

            $opDate = $mapping->operation_date;
            $userId = Auth::id();
            $base = [
                'operation_date' => $opDate,
                'created_by' => $userId,
                'acc_trans_mapping_id' => $mapping->id,
                'cost_center_id' => $costCenterId,
            ];

            $lines = [
                array_merge($base, [
                    'accounting_account_id' => $expenseAccountId,
                    'amount' => $net,
                    'type' => 'debit',
                    'sub_type' => 'expense',
                    'note' => $expense->description,
                ]),
                array_merge($base, [
                    'accounting_account_id' => $treasuryAccountId,
                    'amount' => $gross,
                    'type' => 'credit',
                    'sub_type' => 'expense',
                    'note' => $expense->description,
                ]),
            ];

            if ($tax > 0 && $vatAccount) {
                $lines[] = array_merge($base, [
                    'accounting_account_id' => $vatAccount->id,
                    'amount' => $tax,
                    'type' => 'debit',
                    'sub_type' => 'expense',
                    'note' => 'VAT — Expense #'.$expense->id,
                ]);
            }

            foreach ($lines as $row) {
                AccountingAccountsTransaction::query()->create($row);
            }

            return $mapping;
        });
    }
}
