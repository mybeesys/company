<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Expense\Models\Expense;
use Modules\Expense\Services\ExpenseJournalPoster;
use Modules\Expense\Services\ExpenseTaxCalculator;
use Modules\Expense\Support\ExpenseLedgerAccounts;
use Modules\Expense\Support\TreasuryAccounts;
use Modules\General\Models\Tax;

class ExpenseController extends Controller
{
    protected function resolveDefaultExpenseAccountId(): ?int
    {
        $code = config('expense.default_expense_gl_code');
        $id = AccountingAccount::query()->where('gl_code', $code)->where('status', 'active')->value('id');
        if ($id) {
            return (int) $id;
        }

        return ExpenseLedgerAccounts::query()->value('id');
    }

    public function manage(Request $request)
    {
        $expenseAccounts = ExpenseLedgerAccounts::query()->get();
        $treasuryAccounts = TreasuryAccounts::query()->get();

        $query = Expense::query()->with(['debitAccount', 'creditAccount', 'costCenter', 'attachments']);

        if ($request->filled('debit_account_id') && $request->input('debit_account_id') !== 'all') {
            $query->where('debit_accounting_account_id', (int) $request->input('debit_account_id'));
        }

        if ($request->filled('credit_account_ids')) {
            $ids = array_values(array_filter((array) $request->input('credit_account_ids')));
            if ($ids !== []) {
                $query->whereIn('credit_accounting_account_id', $ids);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_until')) {
            $query->whereDate('date', '<=', $request->input('date_until'));
        }

        if ($request->boolean('with_attachments')) {
            $query->has('attachments');
        }

        if ($request->ajax()) {
            return Expense::manageDataTable($query);
        }

        $columns = Expense::manageTableColumns();
        $hasExpenses = Expense::query()->exists();

        $overviewStats = null;
        if ($hasExpenses) {
            $agg = Expense::query()
                ->selectRaw('COUNT(*) as expense_count')
                ->selectRaw('COALESCE(SUM(amount), 0) as gross_total')
                ->selectRaw('COALESCE(SUM(tax), 0) as tax_total')
                ->selectRaw('COALESCE(SUM(CASE WHEN tax > 0 THEN amount - tax ELSE amount END), 0) as net_total')
                ->first();
            $overviewStats = [
                'count' => (int) $agg->expense_count,
                'gross' => (float) $agg->gross_total,
                'tax' => (float) $agg->tax_total,
                'net' => (float) $agg->net_total,
            ];
        }

        return view('expense::manage.index', compact(
            'columns',
            'hasExpenses',
            'expenseAccounts',
            'treasuryAccounts',
            'overviewStats'
        ));
    }

    public function create(Request $request)
    {
        $defaultDebitId = $this->resolveDefaultExpenseAccountId();
        $expenseAccounts = ExpenseLedgerAccounts::query()->get();
        $costCenters = AccountingCostCenter::forDropdown();
        $taxes = Tax::query()->with('sub_taxes')->orderBy('name')->get();
        $treasuryAccounts = TreasuryAccounts::query()->get();

        $canCreate = $expenseAccounts->isNotEmpty()
            && $treasuryAccounts->isNotEmpty()
            && $costCenters->isNotEmpty();

        $duplicateDefaults = null;
        $duplicateFromId = (int) $request->query('duplicate_from', 0);
        if ($duplicateFromId > 0) {
            $src = Expense::query()->find($duplicateFromId);
            if ($src) {
                $duplicateDefaults = $this->duplicateDefaultsFromExpense($src);
            }
        }

        return view('expense::manage.create', compact(
            'expenseAccounts',
            'costCenters',
            'taxes',
            'treasuryAccounts',
            'defaultDebitId',
            'canCreate',
            'duplicateDefaults'
        ));
    }

    /**
     * @return array{
     *     debit_accounting_account_id:int,
     *     credit_accounting_account_id:int,
     *     cost_center_id:?int,
     *     date:string,
     *     amount:float,
     *     tax_id:?int,
     *     amount_includes_tax:bool,
     *     description:string
     * }
     */
    protected function duplicateDefaultsFromExpense(Expense $src): array
    {
        $taxRaw = (float) $src->getRawOriginal('tax');
        $basis = is_array($src->tax_profile_data) ? ($src->tax_profile_data['basis'] ?? null) : null;
        $hasTaxLine = $src->tax_id && $taxRaw > 0;
        $includesTax = $hasTaxLine && $basis === 'inclusive';

        $amountInput = (float) $src->getRawOriginal('amount');
        if ($hasTaxLine && ! $includesTax) {
            $amountInput = round((float) $src->net_amount, 2);
        }

        return [
            'debit_accounting_account_id' => (int) $src->debit_accounting_account_id,
            'credit_accounting_account_id' => (int) $src->credit_accounting_account_id,
            'cost_center_id' => $src->cost_center_id ? (int) $src->cost_center_id : null,
            'date' => now()->format('Y-m-d'),
            'amount' => $amountInput,
            'tax_id' => $src->tax_id ? (int) $src->tax_id : null,
            'amount_includes_tax' => $includesTax,
            'description' => (string) $src->description,
        ];
    }

    public function show(int $id)
    {
        $expense = Expense::query()
            ->with(['creditAccount', 'debitAccount', 'costCenter', 'attachments', 'appliedTax.sub_taxes', 'journalMapping'])
            ->findOrFail($id);

        $totalTaxPercent = '';
        if ($expense->tax_profile_data && isset($expense->tax_profile_data['percent'])) {
            $totalTaxPercent = (string) $expense->tax_profile_data['percent'];
        } elseif ($expense->tax_id) {
            $expense->loadMissing('appliedTax.sub_taxes');
            if ($expense->appliedTax) {
                $totalTaxPercent = (string) ExpenseTaxCalculator::effectivePercent($expense->appliedTax);
            }
        }

        return view('expense::manage.show', compact('expense', 'totalTaxPercent'));
    }

    public function destroy(Request $request, int $id)
    {
        $expense = Expense::query()->with(['attachments'])->findOrFail($id);

        DB::beginTransaction();

        try {
            $mappingId = $expense->acc_trans_mapping_id;
            if ($mappingId) {
                AccountingAccountsTransaction::query()->where('acc_trans_mapping_id', $mappingId)->delete();
                AccountingAccTransMapping::query()->whereKey($mappingId)->delete();
            }

            foreach ($expense->attachments as $att) {
                if ($att->path && Storage::disk('public')->exists($att->path)) {
                    Storage::disk('public')->delete($att->path);
                }
                $att->delete();
            }

            $expense->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['status' => true]);
            }

            return redirect()->route('expenses.manage')->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            if ($request->ajax()) {
                return response()->json(['message' => __('messages.something_went_wrong')], 500);
            }

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function store(Request $request)
    {
        $treasuryIds = TreasuryAccounts::ids();
        $expenseAccountIds = ExpenseLedgerAccounts::ids();

        $validated = $request->validate([
            'debit_accounting_account_id' => ['required', 'integer', Rule::in($expenseAccountIds)],
            'credit_accounting_account_id' => ['required', 'integer', Rule::in($treasuryIds)],
            'cost_center_id' => ['required', 'integer', 'exists:accounting_cost_centers,id'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'amount_includes_tax' => ['sometimes', 'boolean'],
            'description' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $taxId = $validated['tax_id'] ?? null;
        $includesTax = $request->boolean('amount_includes_tax');

        if ($includesTax && ! $taxId) {
            return redirect()->back()->withInput()->with('error', __('expense::lang.tax_required_when_inclusive'));
        }

        $basis = 'none';
        if ($taxId) {
            $basis = $includesTax ? 'inclusive' : 'exclusive';
        }

        $inputAmount = (float) $validated['amount'];
        $tax = 0.0;
        $grossAmount = $inputAmount;
        $taxSnapshot = null;

        if ($basis === 'inclusive' && $taxId) {
            $taxModel = Tax::query()->with('sub_taxes')->findOrFail($taxId);
            $pct = ExpenseTaxCalculator::effectivePercent($taxModel);
            $calc = ExpenseTaxCalculator::extractTaxFromInclusiveTotal($inputAmount, $pct);
            $tax = $calc['tax'];
            $grossAmount = $inputAmount;
            $taxSnapshot = ExpenseTaxCalculator::taxSnapshot($taxModel, 'inclusive');
        }

        if ($basis === 'exclusive' && $taxId) {
            $taxModel = Tax::query()->with('sub_taxes')->findOrFail($taxId);
            $pct = ExpenseTaxCalculator::effectivePercent($taxModel);
            $calc = ExpenseTaxCalculator::computeTaxFromExclusiveNet($inputAmount, $pct);
            $tax = $calc['tax'];
            $grossAmount = $calc['gross'];
            $taxSnapshot = ExpenseTaxCalculator::taxSnapshot($taxModel, 'exclusive');
        }

        DB::beginTransaction();

        try {
            $expense = Expense::query()->create([
                'debit_accounting_account_id' => (int) $validated['debit_accounting_account_id'],
                'credit_accounting_account_id' => (int) $validated['credit_accounting_account_id'],
                'cost_center_id' => (int) $validated['cost_center_id'],
                'tax_id' => $taxId,
                'amount' => $grossAmount,
                'tax' => $tax,
                'date' => $validated['date'],
                'description' => $validated['description'],
                'tax_profile_data' => $taxSnapshot,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if (! $file) {
                        continue;
                    }
                    $path = $file->store('expenses/'.Auth::id(), 'public');
                    $expense->attachments()->create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            $mapping = ExpenseJournalPoster::post($expense);
            $expense->acc_trans_mapping_id = $mapping->id;
            $expense->save();

            DB::commit();

            return redirect()->route('expenses.manage')->with('success', __('messages.add_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('messages.something_went_wrong');

            return redirect()->back()->withInput()->with('error', $message);
        }
    }

    public function edit(int $id)
    {
        $expense = Expense::query()->with(['attachments', 'debitAccount', 'creditAccount'])->findOrFail($id);
        $costCenters = AccountingCostCenter::forDropdown();

        $totalTaxPercent = '';
        if ($expense->tax_profile_data && isset($expense->tax_profile_data['percent'])) {
            $totalTaxPercent = (string) $expense->tax_profile_data['percent'];
        } elseif ($expense->tax_id) {
            $expense->loadMissing('appliedTax.sub_taxes');
            if ($expense->appliedTax) {
                $totalTaxPercent = (string) ExpenseTaxCalculator::effectivePercent($expense->appliedTax);
            }
        }

        return view('expense::manage.edit', compact('expense', 'costCenters', 'totalTaxPercent'));
    }

    public function update(Request $request, int $id)
    {
        $expense = Expense::query()->findOrFail($id);

        $validated = $request->validate([
            'cost_center_id' => ['required', 'integer', 'exists:accounting_cost_centers,id'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        DB::beginTransaction();

        try {
            $expense->update([
                'cost_center_id' => (int) $validated['cost_center_id'],
                'date' => $validated['date'],
                'description' => $validated['description'],
            ]);

            if ($expense->acc_trans_mapping_id) {
                AccountingAccountsTransaction::query()
                    ->where('acc_trans_mapping_id', $expense->acc_trans_mapping_id)
                    ->update(['cost_center_id' => (int) $validated['cost_center_id']]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if (! $file) {
                        continue;
                    }
                    $path = $file->store('expenses/'.Auth::id(), 'public');
                    $expense->attachments()->create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('expenses.manage')->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function attachmentDestroy(int $expenseId, int $attachmentId)
    {
        $expense = Expense::query()->findOrFail($expenseId);
        $att = $expense->attachments()->whereKey($attachmentId)->firstOrFail();

        if ($att->path && Storage::disk('public')->exists($att->path)) {
            Storage::disk('public')->delete($att->path);
        }

        $att->delete();

        return redirect()->back()->with('success', __('messages.deleted_successfully'));
    }
}
