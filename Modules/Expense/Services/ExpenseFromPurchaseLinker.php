<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\DB;
use Modules\Expense\Models\Expense;
use Modules\Expense\Support\ExpenseLedgerAccounts;

/**
 * Hook point for purchase invoices / additional costs (cross-module).
 */
final class ExpenseFromPurchaseLinker
{
    /**
     * @param  array<string, mixed>  $meta  e.g. ['invoice_id' => int, 'invoice_additional_cost_id' => int]
     */
    public static function createFromPurchaseAdditionalCost(
        int $debitAccountingAccountId,
        int $creditAccountingAccountId,
        int $costCenterId,
        float $amount,
        string $description,
        string $date,
        array $meta = []
    ): Expense {
        $allowedDebit = ExpenseLedgerAccounts::ids();
        if (! in_array($debitAccountingAccountId, $allowedDebit, true)) {
            throw new \InvalidArgumentException('Invalid expense account for purchase additional cost.');
        }

        return DB::transaction(function () use ($debitAccountingAccountId, $creditAccountingAccountId, $costCenterId, $amount, $description, $date, $meta) {
            $expense = Expense::query()->create([
                'debit_accounting_account_id' => $debitAccountingAccountId,
                'credit_accounting_account_id' => $creditAccountingAccountId,
                'cost_center_id' => $costCenterId,
                'tax_id' => null,
                'amount' => $amount,
                'tax' => 0,
                'date' => $date,
                'description' => $description,
                'meta' => $meta,
                'tax_profile_data' => null,
            ]);

            $mapping = ExpenseJournalPoster::post($expense);
            $expense->acc_trans_mapping_id = $mapping->id;
            $expense->save();

            return $expense->fresh();
        });
    }
}
