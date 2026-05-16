<?php

declare(strict_types=1);

namespace Modules\Expense\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;

/**
 * Hook point for purchase invoices / additional costs (cross-module).
 */
final class ExpenseFromPurchaseLinker
{
    private static function defaultExpenseAccountId(): ?int
    {
        $code = config('expense.default_expense_gl_code');
        $id = AccountingAccount::query()->where('gl_code', $code)->where('status', 'active')->value('id');
        if ($id) {
            return (int) $id;
        }

        return AccountingAccount::query()
            ->whereIn('account_primary_type', ['expenses', 'expense'])
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $meta  e.g. ['invoice_id' => int, 'invoice_additional_cost_id' => int]
     */
    public static function createFromPurchaseAdditionalCost(
        int $expenseCategoryId,
        int $creditAccountingAccountId,
        float $amount,
        string $description,
        string $date,
        array $meta = []
    ): Expense {
        $debitId = self::defaultExpenseAccountId();
        if ($debitId === null) {
            throw new \RuntimeException(__('expense::lang.default_expense_account_missing'));
        }

        return DB::transaction(function () use ($debitId, $creditAccountingAccountId, $expenseCategoryId, $amount, $description, $date, $meta) {
            $expense = Expense::query()->create([
                'debit_accounting_account_id' => $debitId,
                'credit_accounting_account_id' => $creditAccountingAccountId,
                'expense_category_id' => $expenseCategoryId,
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

    public static function categoryIdByName(string $name): ?int
    {
        return ExpenseCategory::query()->where('name', $name)->value('id');
    }
}
