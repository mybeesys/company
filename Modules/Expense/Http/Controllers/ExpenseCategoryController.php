<?php

namespace Modules\Expense\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseCategory::manageQuery();

        if ($request->ajax()) {
            return ExpenseCategory::manageDataTable($query);
        }

        $columns = ExpenseCategory::manageTableColumns();
        $hasCategories = ExpenseCategory::query()->exists();

        $overviewStats = null;
        if ($hasCategories) {
            $categoryCount = (int) ExpenseCategory::query()->count();
            $expenseAgg = Expense::query()
                ->selectRaw('COUNT(*) as expense_count')
                ->selectRaw('COALESCE(SUM(CASE WHEN tax > 0 THEN amount - tax ELSE amount END), 0) as net_total')
                ->first();
            $usedCategories = (int) ExpenseCategory::query()->has('expenses')->count();

            $overviewStats = [
                'categories' => $categoryCount,
                'expenses' => (int) ($expenseAgg->expense_count ?? 0),
                'net' => (float) ($expenseAgg->net_total ?? 0),
                'used' => $usedCategories,
            ];
        }

        return view('expense::categories.index', compact('columns', 'hasCategories', 'overviewStats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
        ]);

        ExpenseCategory::query()->create($validated);

        if ($request->ajax()) {
            return response()->json(['status' => true, 'message' => __('messages.add_successfully')]);
        }

        return redirect()->route('expenses.categories.index')->with('success', __('messages.add_successfully'));
    }

    public function update(Request $request, int $id)
    {
        $category = ExpenseCategory::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($category->id)],
        ]);

        $category->update($validated);

        if ($request->ajax()) {
            return response()->json(['status' => true, 'message' => __('messages.updated_successfully')]);
        }

        return redirect()->route('expenses.categories.index')->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Request $request, int $id)
    {
        $category = ExpenseCategory::query()->findOrFail($id);

        if ($category->expenses()->exists()) {
            $message = __('expense::lang.category_in_use');

            if ($request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $category->delete();

        if ($request->ajax()) {
            return response()->json(['status' => true, 'message' => __('messages.deleted_successfully')]);
        }

        return redirect()->route('expenses.categories.index')->with('success', __('messages.deleted_successfully'));
    }
}
