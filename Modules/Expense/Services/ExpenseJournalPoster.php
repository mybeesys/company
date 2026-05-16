<?php

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Expense\Models\Expense;
use Modules\Expense\Support\VatLedgerAccount;

final class ExpenseJournalPoster
{
    /**
     * Post Filament-style entries: Dr expense (gross), Cr treasury (gross); if tax: Dr VAT, Cr expense (tax).
     */
    public static function post(Expense $expense): AccountingAccTransMapping
    {
        $gross = (float) $expense->getRawOriginal('amount');
        $tax = (float) $expense->getRawOriginal('tax');

        $expenseAccountId = (int) $expense->debit_accounting_account_id;
        $treasuryAccountId = (int) $expense->credit_accounting_account_id;

        $configuredGl = (string) config('expense.default_vat_gl_code');
        $vatAccount = VatLedgerAccount::resolve();
        if ($tax > 0 && ! $vatAccount) {
            throw new \RuntimeException(__('expense::lang.vat_account_missing').' ('.$configuredGl.')');
        }

        return DB::transaction(function () use ($expense, $gross, $tax, $expenseAccountId, $treasuryAccountId, $vatAccount) {
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

            $lines = [
                [
                    'accounting_account_id' => $expenseAccountId,
                    'amount' => $gross,
                    'type' => 'debit',
                    'sub_type' => 'expense',
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'acc_trans_mapping_id' => $mapping->id,
                    'note' => $expense->description,
                ],
                [
                    'accounting_account_id' => $treasuryAccountId,
                    'amount' => $gross,
                    'type' => 'credit',
                    'sub_type' => 'expense',
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'acc_trans_mapping_id' => $mapping->id,
                    'note' => $expense->description,
                ],
            ];

            if ($tax > 0 && $vatAccount) {
                $lines[] = [
                    'accounting_account_id' => $vatAccount->id,
                    'amount' => $tax,
                    'type' => 'debit',
                    'sub_type' => 'expense',
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'acc_trans_mapping_id' => $mapping->id,
                    'note' => 'VAT — Expense #'.$expense->id,
                ];
                $lines[] = [
                    'accounting_account_id' => $expenseAccountId,
                    'amount' => $tax,
                    'type' => 'credit',
                    'sub_type' => 'expense',
                    'operation_date' => $opDate,
                    'created_by' => $userId,
                    'acc_trans_mapping_id' => $mapping->id,
                    'note' => 'VAT — Expense #'.$expense->id,
                ];
            }

            foreach ($lines as $row) {
                AccountingAccountsTransaction::query()->create($row);
            }

            return $mapping;
        });
    }
}
