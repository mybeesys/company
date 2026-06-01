<?php

namespace Modules\Accounting\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\AgeingDetailsExport;
use Modules\Accounting\classes\AgeingSummaryExport;
use Modules\Accounting\classes\BalanceSheetExport;
use Modules\Accounting\classes\CashFlowExport;
use Modules\Accounting\classes\CustomersSuppliersStatementExport;
use Modules\Accounting\classes\ExpenseReportExport;
use Modules\Accounting\classes\IncomeStatementExport;
use Modules\Accounting\classes\JournalReportExport;
use Modules\Accounting\classes\TrialBalanceExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Expense\Models\Expense;
use Modules\Expense\Support\ExpenseLedgerAccounts;
use Modules\Accounting\Services\CashFlowReportService;
use Modules\Accounting\Services\CustomerSupplierStatementReportService;
use Modules\Accounting\Services\TrialBalanceReportService;
use Modules\Expense\Services\ExpenseReportService;
use Modules\Expense\Support\TreasuryAccounts;
use Modules\General\Models\Actions;
use Modules\General\Models\Tax;
use Mpdf\Mpdf;
use Yajra\DataTables\Facades\DataTables;

class AccountingReportsController extends Controller
{
    public function index()
    {
        return view('accounting::reports.reports');
    }

    public function track(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'action' => 'required|string',
            'name' => 'required|string',
        ]);

        Actions::create([
            'user_id' => Auth::user()->id,
            'type' => $validated['type'],
            'action' => $validated['action'],
            'name' => $validated['name'],
        ]);

        return response()->json(['success' => true]);
    }

    public function getIcomeStatementData($accounts)
    {
        $gross_revenue = 0;
        $sales_returns = 0;
        $cost_of_revenue = 0;
        $total_expense = 0;
        $total_other_income = 0;
        $total_other_expense = 0;

        foreach ($accounts as $account) {
            $debit = (float) $account->debit_balance;
            $credit = (float) $account->credit_balance;

            switch ($account->acc_type) {
                case 'gross_revenue':
                case 'income':
                    $gross_revenue += $credit - $debit;
                    break;

                case 'sales_returns':
                    $sales_returns += $debit - $credit;
                    break;

                case 'cost_of_sales':
                    $cost_of_revenue += $debit - $credit;
                    break;

                case 'expenses':
                case 'operating_expense':
                    $total_expense += $debit - $credit;
                    break;

                case 'other_income':
                    $total_other_income += $credit - $debit;
                    break;

                case 'other_expenses':
                    $total_other_expense += $debit - $credit;
                    break;
            }
        }

        $net_sales = $gross_revenue - $sales_returns;
        $gross_profit = $net_sales - $cost_of_revenue;
        $operation_income = $gross_profit - $total_expense;
        $income_before_tax = $operation_income + $total_other_income - $total_other_expense;

        $taxPercent = (float) (Tax::query()->value('amount') ?? 0);
        $taxableBase = max(0.0, (float) $income_before_tax);
        $tax_amount = ($taxPercent * $taxableBase) / 100;
        $net_profit = $income_before_tax - $tax_amount;

        $total_expenses_all = $cost_of_revenue + $total_expense + $total_other_expense;
        $profit_margin = abs($net_sales) > 0.0001 ? ($net_profit / $net_sales) * 100 : null;

        return [
            'gross_revenue' => $gross_revenue,
            'sales_returns' => $sales_returns,
            'net_sales' => $net_sales,
            'revenue_net' => $net_sales,
            'gross_profit' => $gross_profit,
            'operation_income' => $operation_income,
            'operating_profit' => $operation_income,
            'income_before_tax' => $income_before_tax,
            'tax_amount' => $tax_amount,
            'tax_percent' => $taxPercent,
            'net_profit' => $net_profit,
            'cost_of_revenue' => $cost_of_revenue,
            'total_expense' => $total_expense,
            'total_operating_expenses' => $total_expense,
            'total_other_income' => $total_other_income,
            'total_other_expense' => $total_other_expense,
            'total_expenses_all' => $total_expenses_all,
            'profit_margin' => $profit_margin,
        ];
    }

    public function incomeStatement()
    {
        $start_date = request()->start_date ?? now()->startOfYear()->format('Y-m-d');
        $end_date = request()->end_date ?? now()->format('Y-m-d');
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $choose_cost_center_select = request()->choose_cost_center_select ?? [];
        $compare_mode = request()->get('compare_mode', 'none');
        $hide_zero_lines = (int) request()->get('hide_zero_lines', 1) === 1;

        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select, $hide_zero_lines);
        $compareDataset = null;
        $comparePeriod = null;

        if (in_array($compare_mode, ['previous_period', 'previous_year'], true)) {
            $comparePeriod = $this->resolveIncomeComparePeriod($start_date, $end_date, $compare_mode);
            $compareDataset = $this->buildIncomeStatementDataset(
                $comparePeriod['start'],
                $comparePeriod['end'],
                $choose_cost_center_select,
                $hide_zero_lines
            );
        }

        $data = $incomeDataset['data'];
        $kpiGrowth = $this->buildIncomeStatementKpiGrowth($data, $compareDataset['data'] ?? null);

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.income-statement')
            ->with([
                'accounts' => $incomeDataset['accounts'],
                'grossRevenueAccounts' => $incomeDataset['grossRevenueAccounts'],
                'salesReturnAccounts' => $incomeDataset['salesReturnAccounts'],
                'cogsAccounts' => $incomeDataset['cogsAccounts'],
                'expenseAccounts' => $incomeDataset['expenseAccounts'],
                'otherIncomeAccounts' => $incomeDataset['otherIncomeAccounts'],
                'otherExpenseAccounts' => $incomeDataset['otherExpenseAccounts'],
                'revenueAccounts' => $incomeDataset['grossRevenueAccounts'],
                'start_date' => $start_date,
                'end_date' => $end_date,
                'data' => $data,
                'compareData' => $compareDataset['data'] ?? null,
                'comparePeriod' => $comparePeriod,
                'compare_mode' => $compare_mode,
                'hide_zero_lines' => $hide_zero_lines,
                'kpiGrowth' => $kpiGrowth,
                'company' => $company,
                'costCenters' => $costCenters,
                'choose_cost_center_select' => $choose_cost_center_select,
            ]);
    }

    public function incomeStatementExportPdf(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);

        $hide_zero_lines = (int) $request->input('hide_zero_lines', 1) === 1;
        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select, $hide_zero_lines);
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $html = view('accounting::reports.income-statement-print', [
            'company' => $company,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $incomeDataset['data'],
            'grossRevenueAccounts' => $incomeDataset['grossRevenueAccounts'],
            'salesReturnAccounts' => $incomeDataset['salesReturnAccounts'],
            'cogsAccounts' => $incomeDataset['cogsAccounts'],
            'expenseAccounts' => $incomeDataset['expenseAccounts'],
            'otherIncomeAccounts' => $incomeDataset['otherIncomeAccounts'],
            'otherExpenseAccounts' => $incomeDataset['otherExpenseAccounts'],
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('income-statement.pdf', 'D');
    }

    public function incomeStatementExportExcel(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);

        $hide_zero_lines = (int) $request->input('hide_zero_lines', 1) === 1;
        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select, $hide_zero_lines);
        $rows = $this->buildIncomeStatementExportRows($incomeDataset);

        $filename = 'income-statement-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new IncomeStatementExport($rows, [
                'start_date' => $start_date,
                'end_date' => $end_date,
            ]),
            $filename
        );
    }

    private function buildIncomeStatementDataset(
        string $start_date,
        string $end_date,
        array $choose_cost_center_select,
        bool $hide_zero_lines = true
    ): array {
        $accounts = AccountingAccount::query()
            ->join('accounting_accounts_transactions as AAT', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
            ->leftJoin('accounting_account_types as acc_subtype', 'acc_subtype.id', '=', 'accounting_accounts.account_sub_type_id')
            ->whereBetween('AAT.operation_date', [$start_date, $end_date])
            ->when(! empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                $query->whereIn('AAT.cost_center_id', $choose_cost_center_select);
            })
            ->whereIn('accounting_accounts.account_type', ['income', 'expenses'])
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type',
                'acc_subtype.name_en as account_sub_type_name_en',
                DB::raw("SUM(IF(AAT.type = 'credit' , AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit' , AAT.amount, 0)) as debit_balance"),
            )
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type',
                'acc_subtype.name_en',
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($account) {
                $account->acc_type = $this->resolveIncomeAccountCategory($account);

                return $account;
            });

        $accounts = $this->enrichIncomeAccountsWithAmountsAndDepth($accounts);

        $filterZero = static fn ($account) => ! $hide_zero_lines || abs((float) $account->amount) > 0.0001;

        $grossRevenueAccounts = $accounts->where('acc_type', 'gross_revenue')->filter($filterZero)->values();
        $salesReturnAccounts = $accounts->where('acc_type', 'sales_returns')->filter($filterZero)->values();
        $cogsAccounts = $accounts->where('acc_type', 'cost_of_sales')->filter($filterZero)->values();
        $expenseAccounts = $accounts->whereIn('acc_type', ['expenses', 'operating_expense'])->filter($filterZero)->values();
        $otherIncomeAccounts = $accounts->where('acc_type', 'other_income')->filter($filterZero)->values();
        $otherExpenseAccounts = $accounts->where('acc_type', 'other_expenses')->filter($filterZero)->values();

        $data = $this->getIcomeStatementData($accounts);

        return [
            'accounts' => $accounts,
            'data' => $data,
            'grossRevenueAccounts' => $grossRevenueAccounts,
            'salesReturnAccounts' => $salesReturnAccounts,
            'revenueAccounts' => $grossRevenueAccounts,
            'cogsAccounts' => $cogsAccounts,
            'expenseAccounts' => $expenseAccounts,
            'otherIncomeAccounts' => $otherIncomeAccounts,
            'otherExpenseAccounts' => $otherExpenseAccounts,
        ];
    }

    private function resolveIncomeAccountCategory(object $account): string
    {
        if ($account->account_type === 'income') {
            if ($this->isSalesReturnAccount($account)) {
                return 'sales_returns';
            }
            if ($this->isOtherIncomeAccount($account)) {
                return 'other_income';
            }

            return 'gross_revenue';
        }

        if ($account->account_sub_type_name_en === 'Cost Of Sales') {
            return 'cost_of_sales';
        }

        if ($account->account_sub_type_name_en === 'Other Expenses') {
            return 'other_expenses';
        }

        return 'operating_expense';
    }

    private function isSalesReturnAccount(object $account): bool
    {
        $label = strtolower(trim(($account->name_en ?? '').' '.($account->name_ar ?? '')));

        return str_contains($label, 'sales return')
            || str_contains($label, 'sales returns')
            || str_contains($label, 'مردود');
    }

    private function isOtherIncomeAccount(object $account): bool
    {
        $label = strtolower(trim(($account->name_en ?? '').' '.($account->name_ar ?? '')));

        return str_contains($label, 'other income')
            || str_contains($label, 'إيرادات أخرى')
            || str_contains($label, 'gain on asset');
    }

    private function enrichIncomeAccountsWithAmountsAndDepth(Collection $accounts): Collection
    {
        $byId = $accounts->keyBy('id');
        $childCountByParent = $accounts
            ->pluck('parent_account_id')
            ->filter()
            ->countBy();

        return $accounts->map(function ($account) use ($byId, $childCountByParent) {
            $debit = (float) $account->debit_balance;
            $credit = (float) $account->credit_balance;

            if (in_array($account->acc_type, ['gross_revenue', 'other_income'], true)) {
                $account->amount = $credit - $debit;
            } elseif ($account->acc_type === 'sales_returns') {
                $account->amount = -1 * ($debit - $credit);
            } else {
                $account->amount = $debit - $credit;
            }

            $depth = 0;
            $parentId = $account->parent_account_id;
            $guard = 0;
            while ($parentId && $byId->has($parentId) && $guard < 12) {
                $depth++;
                $parentId = $byId->get($parentId)->parent_account_id;
                $guard++;
            }

            $account->depth = $depth;
            $account->has_children = $childCountByParent->get($account->id, 0) > 0;

            return $account;
        });
    }

    private function resolveIncomeComparePeriod(string $start_date, string $end_date, string $compare_mode): array
    {
        $start = \Carbon\Carbon::parse($start_date);
        $end = \Carbon\Carbon::parse($end_date);

        if ($compare_mode === 'previous_year') {
            return [
                'start' => $start->copy()->subYear()->format('Y-m-d'),
                'end' => $end->copy()->subYear()->format('Y-m-d'),
                'label' => 'previous_year',
            ];
        }

        $days = $start->diffInDays($end) + 1;

        return [
            'start' => $start->copy()->subDays($days)->format('Y-m-d'),
            'end' => $start->copy()->subDay()->format('Y-m-d'),
            'label' => 'previous_period',
        ];
    }

    private function buildIncomeStatementKpiGrowth(array $data, ?array $compareData): array
    {
        if ($compareData === null) {
            return [];
        }

        return [
            'net_sales' => CurrencyHelper::growth_percent($data['net_sales'] ?? 0, $compareData['net_sales'] ?? 0),
            'gross_profit' => CurrencyHelper::growth_percent($data['gross_profit'] ?? 0, $compareData['gross_profit'] ?? 0),
            'operating_profit' => CurrencyHelper::growth_percent($data['operating_profit'] ?? 0, $compareData['operating_profit'] ?? 0),
            'net_profit' => CurrencyHelper::growth_percent($data['net_profit'] ?? 0, $compareData['net_profit'] ?? 0),
            'total_expenses' => CurrencyHelper::growth_percent($data['total_expenses_all'] ?? 0, $compareData['total_expenses_all'] ?? 0),
        ];
    }

    private function buildIncomeStatementExportRows(array $incomeDataset): Collection
    {
        $rows = collect();
        $localeAr = app()->getLocale() === 'ar';
        $name = static fn ($account) => $localeAr ? $account->name_ar : $account->name_en;
        $fmt = static fn ($amount) => number_format((float) $amount, 2, '.', '');
        $data = $incomeDataset['data'];

        $pushAccounts = function (string $section, $accounts) use (&$rows, $name, $fmt) {
            foreach ($accounts as $account) {
                $indent = str_repeat('  ', (int) ($account->depth ?? 0));
                $rows->push([$section.' — '.$indent.$name($account), $fmt($account->amount)]);
            }
        };

        $pushAccounts(__('accounting::lang.income_statement_gross_revenue'), $incomeDataset['grossRevenueAccounts']);
        $pushAccounts(__('accounting::lang.income_statement_sales_returns'), $incomeDataset['salesReturnAccounts']);
        $rows->push([__('accounting::lang.income_statement_net_sales'), $fmt($data['net_sales'] ?? 0)]);
        $pushAccounts(__('accounting::lang.income_statement_cost_of_revenue'), $incomeDataset['cogsAccounts']);
        $rows->push([__('accounting::lang.income_statement_total_cost_of_revenue'), $fmt($data['cost_of_revenue'] ?? 0)]);
        $rows->push([__('report::general.gross_profit'), $fmt($data['gross_profit'] ?? 0)]);
        $pushAccounts(__('accounting::lang.income_statement_operating_expenses'), $incomeDataset['expenseAccounts']);
        $rows->push([__('accounting::lang.income_statement_total_operating_expenses'), $fmt($data['total_expense'] ?? 0)]);
        $rows->push([__('accounting::lang.income_statement_operating_profit'), $fmt($data['operating_profit'] ?? 0)]);
        $pushAccounts(__('accounting::lang.income_statement_other_income'), $incomeDataset['otherIncomeAccounts']);
        $pushAccounts(__('accounting::lang.income_statement_other_expenses'), $incomeDataset['otherExpenseAccounts']);
        $rows->push([__('accounting::lang.income_before_tax'), $fmt($data['income_before_tax'] ?? 0)]);
        $rows->push([
            __('accounting::lang.tax_amount').' ('.number_format((float) ($data['tax_percent'] ?? 0), 0).'%)',
            $fmt($data['tax_amount'] ?? 0),
        ]);
        $rows->push([__('accounting::lang.net_profit'), $fmt($data['net_profit'] ?? 0)]);

        return $rows;
    }

    public function trialBalance(Request $request)
    {
        // try {

        $account_types = AccountingAccountTypes::accounting_primary_type();
        $accounts_array = [];
        foreach ($account_types as $key => $account_type) {
            $accounts_array[$key] =
                $account_type['label'];
        }

        $with_zero_balances = (int) $request->input('with_zero_balances', 0);

        /** @see Qoyod-style default: grouped by primary classification */
        $aggregated = (int) $request->input('aggregated', 1);

        $choose_accounts_select = $request->input('choose_accounts_select');
        $choose_cost_center_select = $request->input('choose_cost_center_select');

        $level_filter = $request->input('level_filter');

        $max_levels = AccountingAccount::pluck('gl_code')->toArray();

        $lengths = array_map(function ($length) {
            return str_replace('.', '', $length);
        }, $max_levels);
        if (empty($max_levels)) {
            // Redirect to the 'chart-of-accounts' route with a flash message
            return redirect()->route('tree-of-accounts')
                ->with('message', 'Please create a tree account for the chart of accounts.');
        }
        $levels = strlen(max($lengths));

        $levelsArray = [];
        for ($i = 1; $i <= $levels; $i++) {
            $levelsArray[$i] = $i;
        }

        $levelsArray = [null => __('all')] + $levelsArray;

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        } else {
            $start_date = now()->startOfYear()->format('Y-m-d');
            $end_date = now()->format('Y-m-d');
        }

        $costCenterIds = array_values(array_filter((array) ($choose_cost_center_select ?? [])));

        $baseRows = $this->queryTrialBalanceAccountRows(
            $start_date,
            $end_date,
            $with_zero_balances,
            $choose_accounts_select,
            $costCenterIds,
            $level_filter
        );

        $accounts = $aggregated
            ? collect($this->aggregateTrialBalanceRows($baseRows))
            : $baseRows;

        if (request()->ajax()) {
            $accountsCollection = $accounts instanceof Collection ? $accounts : collect($accounts);
            $analytics = TrialBalanceReportService::buildAnalytics($accountsCollection, (bool) $aggregated);

            $compareMode = $request->input('compare_mode', 'none');
            $compareAnalytics = null;
            if (in_array($compareMode, ['previous_period', 'previous_year'], true)) {
                $comparePeriod = $this->resolveIncomeComparePeriod($start_date, $end_date, $compareMode);
                $compareRows = $aggregated
                    ? collect($this->aggregateTrialBalanceRows($this->queryTrialBalanceAccountRows(
                        $comparePeriod['start'],
                        $comparePeriod['end'],
                        $with_zero_balances,
                        $choose_accounts_select,
                        $costCenterIds,
                        $level_filter
                    )))
                    : $this->queryTrialBalanceAccountRows(
                        $comparePeriod['start'],
                        $comparePeriod['end'],
                        $with_zero_balances,
                        $choose_accounts_select,
                        $costCenterIds,
                        $level_filter
                    );
                $compareAnalytics = TrialBalanceReportService::buildAnalytics($compareRows, (bool) $aggregated);
            }

            $totalDebitOpeningBalance = $analytics['kpis']['total_debit_opening'];
            $totalCreditOpeningBalance = $analytics['kpis']['total_credit_opening'];
            $totalClosingDebitBalance = $analytics['kpis']['closing_debit'];
            $totalClosingCreditBalance = $analytics['kpis']['closing_credit'];
            $totalDebitBalance = $analytics['kpis']['total_debit_period'];
            $totalCreditBalance = $analytics['kpis']['total_credit_period'];

            $localeAr = app()->getLocale() === 'ar';

            return DataTables::of($accounts)
                ->editColumn('gl_code', function ($account) {
                    return '<span class="tb-gl-code">'.e($account->gl_code ?? '').'</span>';
                })
                ->editColumn('name', function ($account) use ($aggregated) {
                    $depth = (int) ($account->depth ?? 0);
                    $indent = str_repeat('<span class="tb-indent"></span>', max(0, $depth));
                    $typeKey = TrialBalanceReportService::normalizePrimaryType((string) ($account->account_primary_type ?? ''));
                    $typeBadge = $aggregated
                        ? ''
                        : '<span class="badge badge-light-secondary tb-type-badge ms-1">'.e(
                            Lang::has('accounting::lang.'.$typeKey)
                                ? __('accounting::lang.'.$typeKey)
                                : $typeKey
                        ).'</span>';

                    return '<div class="tb-name-cell">'.$indent
                        .'<span class="tb-account-name">'.e($account->name ?? '').'</span>'
                        .$typeBadge.'</div>';
                })
                ->addColumn('period_movement', function ($account) {
                    return $this->roundMoney(
                        abs($this->roundMoney((float) ($account->debit_balance ?? 0) - (float) ($account->credit_balance ?? 0)))
                    );
                })
                ->addColumn('balance_type', function ($account) {
                    $closing = TrialBalanceReportService::closingBalance($account);
                    $label = TrialBalanceReportService::balanceTypeLabel(
                        (float) $closing['closing_debit_balance'],
                        (float) $closing['closing_credit_balance']
                    );
                    $isDebit = str_contains($label, __('accounting::lang.tb_balance_type_debit'));
                    $class = $isDebit ? 'badge-light-primary' : 'badge-light-warning';

                    return '<span class="badge '.$class.'">'.e($label).'</span>';
                })
                ->addColumn('closing_balance', function ($account) {
                    return $this->roundMoney(abs(TrialBalanceReportService::signedClosing($account)));
                })
                ->editColumn('debit_balance', function ($account) {
                    return $this->roundMoney($account->debit_balance ?? 0);
                })
                ->editColumn('debit_opening_balance', function ($account) {
                    return $this->roundMoney($account->debit_opening_balance ?? 0);
                })
                ->editColumn('credit_opening_balance', function ($account) {
                    return $this->roundMoney($account->credit_opening_balance ?? 0);
                })

                ->editColumn('credit_balance', function ($account) {
                    return $this->roundMoney($account->credit_balance ?? 0);
                })
                ->addColumn('closing_debit_balance', function ($account) {
                    $closing_balance = TrialBalanceReportService::closingBalance($account);

                    return $this->roundMoney($closing_balance['closing_debit_balance'] ?? 0);
                })
                ->addColumn('closing_credit_balance', function ($account) {
                    $closing_balance = TrialBalanceReportService::closingBalance($account);

                    return $this->roundMoney($closing_balance['closing_credit_balance'] ?? 0);
                })
                ->addColumn('action', function ($account) use ($aggregated) {
                    if (! $aggregated) {
                        $label = e(__('accounting::lang.account_statement'));
                        $url = e(route('print-ledger', $account->id));

                        return '<a class="btn btn-sm tb-ledger-btn btn-modal d-inline-flex align-items-center gap-1 text-nowrap" '
                            .'data-container="#printledger" href="'.$url.'" title="'.$label.'">'
                            .'<i class="fa-solid fa-file-lines" aria-hidden="true"></i>'
                            .'<span>'.$label.'</span></a>';
                    }

                    return '';
                })
                ->with([
                    'totalDebitOpeningBalance' => $this->roundMoney($totalDebitOpeningBalance),
                    'totalCreditOpeningBalance' => $this->roundMoney($totalCreditOpeningBalance),
                    'totalDebitBalance' => $this->roundMoney($totalDebitBalance),
                    'totalCreditBalance' => $this->roundMoney($totalCreditBalance),
                    'totalClosingDebitBalance' => $this->roundMoney($totalClosingDebitBalance),
                    'totalClosingCreditBalance' => $this->roundMoney($totalClosingCreditBalance),
                    'analytics' => $analytics,
                    'compareAnalytics' => $compareAnalytics,
                    'difference' => $analytics['kpis']['difference'],
                    'isBalanced' => $analytics['kpis']['is_balanced'],
                ])
                ->rawColumns([
                    'action', 'closing_debit_balance', 'closing_credit_balance', 'debit_balance', 'credit_balance',
                    'name', 'gl_code', 'balance_type', 'period_movement', 'closing_balance',
                ])

                ->make(true);
        }

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        return view('accounting::reports.trial_balance')
            ->with(compact('levelsArray', 'accounts_array', 'costCenters', 'company', 'start_date', 'end_date'));
        // } catch (\Exception $e) {
        //     // Log::error('Error in trialBalance method: ' . $e->getMessage());
        //     return redirect()->route('tree-of-accounts')
        //         ->with('message', 'Please create a tree account for the chart of accounts.');
        // }
    }

    public function trialBalanceExportPdf(Request $request)
    {
        $report = $this->getTrialBalanceExportDataset($request);

        $html = view('accounting::reports.trial_balance_print', [
            'rows' => $report['rows'],
            'start_date' => $report['start_date'],
            'end_date' => $report['end_date'],
            'difference' => $report['difference'],
            'balance_status' => $report['balance_status'],
            'totals' => $report['totals'],
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('trial-balance.pdf', 'D');
    }

    public function trialBalanceExportExcel(Request $request)
    {
        $report = $this->getTrialBalanceExportDataset($request);

        $filename = 'trial-balance-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new TrialBalanceExport(
                collect($report['rows']),
                [
                    'start_date' => $report['start_date'],
                    'end_date' => $report['end_date'],
                    'difference' => $report['difference'],
                    'balance_status' => $report['balance_status'],
                ]
            ),
            $filename
        );
    }

    private function getTrialBalanceExportDataset(Request $request): array
    {
        $with_zero_balances = (int) $request->input('with_zero_balances', 0);
        $aggregated = (int) $request->input('aggregated', 1);
        $choose_accounts_select = $request->input('choose_accounts_select');
        $choose_cost_center_select = $request->input('choose_cost_center_select');
        $level_filter = $request->input('level_filter');

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
        } else {
            $start_date = now()->startOfYear()->format('Y-m-d');
            $end_date = now()->format('Y-m-d');
        }

        $costCenterIds = array_values(array_filter((array) ($choose_cost_center_select ?? [])));

        $baseRows = $this->queryTrialBalanceAccountRows(
            $start_date,
            $end_date,
            $with_zero_balances,
            $choose_accounts_select,
            $costCenterIds,
            $level_filter
        );

        $accountsCollection = $aggregated
            ? collect($this->aggregateTrialBalanceRows($baseRows))
            : $baseRows;

        $rows = [];
        $totals = [
            'debit_opening' => 0.0,
            'credit_opening' => 0.0,
            'debit' => 0.0,
            'credit' => 0.0,
            'closing_debit' => 0.0,
            'closing_credit' => 0.0,
        ];

        foreach ($accountsCollection as $account) {
            $closing = $this->calculateClosingBalance($account);

            $totals['debit_opening'] += (float) ($account->debit_opening_balance ?? 0);
            $totals['credit_opening'] += (float) ($account->credit_opening_balance ?? 0);
            $totals['debit'] += (float) ($account->debit_balance ?? 0);
            $totals['credit'] += (float) ($account->credit_balance ?? 0);
            $totals['closing_debit'] += (float) ($closing['closing_debit_balance'] ?? 0);
            $totals['closing_credit'] += (float) ($closing['closing_credit_balance'] ?? 0);

            $rows[] = [
                'gl_code' => $account->gl_code,
                'name' => $account->name,
                'debit_opening_balance' => number_format((float) ($account->debit_opening_balance ?? 0), 2, '.', ''),
                'credit_opening_balance' => number_format((float) ($account->credit_opening_balance ?? 0), 2, '.', ''),
                'debit_balance' => number_format((float) ($account->debit_balance ?? 0), 2, '.', ''),
                'credit_balance' => number_format((float) ($account->credit_balance ?? 0), 2, '.', ''),
                'closing_debit_balance' => number_format((float) ($closing['closing_debit_balance'] ?? 0), 2, '.', ''),
                'closing_credit_balance' => number_format((float) ($closing['closing_credit_balance'] ?? 0), 2, '.', ''),
            ];
        }

        $difference = abs($totals['closing_debit'] - $totals['closing_credit']);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'difference' => $difference,
            'balance_status' => $difference < 0.005 ? __('accounting::lang.balanced') : __('accounting::lang.unbalanced'),
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
    }

    /**
     * تجميع حركات الحساب: افتتاحي قبل بداية الفترة (مثل كشف الحساب)، وحركة الفترة ضمن [start, end].
     */
    protected function trialBalanceTransactionsSubquery(string $startDate, string $endDate, array $costCenterIds): Builder
    {
        $q = DB::table('accounting_accounts_transactions as t')
            ->selectRaw(
                "t.accounting_account_id,
                SUM(CASE WHEN t.type = 'debit' AND DATE(t.operation_date) < DATE(?) THEN t.amount ELSE 0 END) as debit_opening,
                SUM(CASE WHEN t.type = 'credit' AND DATE(t.operation_date) < DATE(?) THEN t.amount ELSE 0 END) as credit_opening,
                SUM(CASE WHEN t.type = 'debit' AND DATE(t.operation_date) >= DATE(?) AND DATE(t.operation_date) <= DATE(?) THEN t.amount ELSE 0 END) as debit_period,
                SUM(CASE WHEN t.type = 'credit' AND DATE(t.operation_date) >= DATE(?) AND DATE(t.operation_date) <= DATE(?) THEN t.amount ELSE 0 END) as credit_period",
                [$startDate, $startDate, $startDate, $endDate, $startDate, $endDate]
            )
            ->groupBy('t.accounting_account_id');

        if ($costCenterIds !== []) {
            $q->whereIn('t.cost_center_id', $costCenterIds);
        }

        return $q;
    }

    /**
     * صفوف ميزان المراجعة — حساب واحد لكل سطر (بدون تكرار بسبب sub_type من الحركة).
     */
    protected function queryTrialBalanceAccountRows(
        string $startDate,
        string $endDate,
        int $withZeroBalances,
        $chooseAccountsSelect,
        array $costCenterIds,
        $levelFilter
    ): Collection {
        $sub = $this->trialBalanceTransactionsSubquery($startDate, $endDate, $costCenterIds);

        $query = AccountingAccount::query()
            ->leftJoinSub($sub, 'tb', 'tb.accounting_account_id', '=', 'accounting_accounts.id')
            ->when($chooseAccountsSelect, function ($q) use ($chooseAccountsSelect) {
                return $q->where(function ($inner) use ($chooseAccountsSelect) {
                    foreach ($chooseAccountsSelect as $type) {
                        $inner->orWhere('accounting_accounts.account_primary_type', 'like', $type.'%');
                    }
                });
            })
            ->when($levelFilter, function ($q) use ($levelFilter) {
                return $q
                    ->whereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) = ?', [$levelFilter - 1])
                    ->orWhereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) < ?', [$levelFilter - 1]);
            })
            ->when($withZeroBalances === 0, function ($q) {
                return $q->whereRaw(
                    '(COALESCE(tb.debit_opening,0)+COALESCE(tb.credit_opening,0)+COALESCE(tb.debit_period,0)+COALESCE(tb.credit_period,0)) > 0.00001'
                );
            })
            ->when($withZeroBalances === 2, function ($q) {
                return $q->whereRaw(
                    'ABS((COALESCE(tb.debit_opening,0)+COALESCE(tb.debit_period,0)) - (COALESCE(tb.credit_opening,0)+COALESCE(tb.credit_period,0))) < 0.00001'
                )->whereRaw(
                    '(COALESCE(tb.debit_opening,0)+COALESCE(tb.credit_opening,0)+COALESCE(tb.debit_period,0)+COALESCE(tb.credit_period,0)) > 0.00001'
                );
            })
            ->select([
                'accounting_accounts.id',
                'accounting_accounts.gl_code',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                DB::raw('COALESCE(tb.debit_opening,0) as debit_opening_balance'),
                DB::raw('COALESCE(tb.credit_opening,0) as credit_opening_balance'),
                DB::raw('COALESCE(tb.debit_period,0) as debit_balance'),
                DB::raw('COALESCE(tb.credit_period,0) as credit_balance'),
            ])
            ->orderBy('accounting_accounts.gl_code');

        return $query->get()->map(function ($row) {
            $o = (object) $row->getAttributes();
            $o->name = app()->getLocale() === 'ar' ? ($o->name_ar ?? '') : ($o->name_en ?? '');
            $o->depth = substr_count((string) ($o->gl_code ?? ''), '.');
            foreach (['debit_opening_balance', 'credit_opening_balance', 'debit_balance', 'credit_balance'] as $key) {
                $o->{$key} = $this->roundMoney($o->{$key} ?? 0);
            }

            return $o;
        });
    }

    /**
     * تجميع ميزان المراجعة حسب التصنيف الرئيسي (أصول، خصوم، …).
     *
     * @return array<int, object>
     */
    protected function aggregateTrialBalanceRows(Collection $rows): array
    {
        $aggregatedAccounts = [];
        foreach ($rows as $account) {
            $groupKey = $account->account_primary_type;
            if (! isset($aggregatedAccounts[$groupKey])) {
                $aggregatedAccounts[$groupKey] = (object) [
                    'name' => Lang::has('accounting::lang.'.$groupKey) ? __('accounting::lang.'.$groupKey) : $groupKey,
                    'gl_code' => is_string($account->gl_code) && $account->gl_code !== '' ? substr($account->gl_code, 0, 1) : '-',
                    'account_primary_type' => $groupKey,
                    'depth' => 0,
                    'credit_balance' => 0.0,
                    'debit_balance' => 0.0,
                    'credit_opening_balance' => 0.0,
                    'debit_opening_balance' => 0.0,
                    'id' => null,
                ];
            }
            $aggregatedAccounts[$groupKey]->credit_balance += (float) ($account->credit_balance ?? 0);
            $aggregatedAccounts[$groupKey]->debit_balance += (float) ($account->debit_balance ?? 0);
            $aggregatedAccounts[$groupKey]->credit_opening_balance += (float) ($account->credit_opening_balance ?? 0);
            $aggregatedAccounts[$groupKey]->debit_opening_balance += (float) ($account->debit_opening_balance ?? 0);
        }

        foreach ($aggregatedAccounts as $acc) {
            $acc->credit_balance = $this->roundMoney($acc->credit_balance);
            $acc->debit_balance = $this->roundMoney($acc->debit_balance);
            $acc->credit_opening_balance = $this->roundMoney($acc->credit_opening_balance);
            $acc->debit_opening_balance = $this->roundMoney($acc->debit_opening_balance);
        }

        return array_values($aggregatedAccounts);
    }

    /**
     * تقريب المبالغ لخانتين عشريتين (تفادي بقايا float مثل 16182.099000000002).
     */
    private function roundMoney($value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    private function calculateClosingBalance($account)
    {
        $debitSide = $this->roundMoney($account->debit_opening_balance ?? 0) + $this->roundMoney($account->debit_balance ?? 0);
        $creditSide = $this->roundMoney($account->credit_opening_balance ?? 0) + $this->roundMoney($account->credit_balance ?? 0);
        $closing_balance = $this->roundMoney($creditSide - $debitSide);

        return [
            'closing_debit_balance' => $closing_balance < 0 ? $this->roundMoney(abs($closing_balance)) : 0.0,
            'closing_credit_balance' => $closing_balance >= 0 ? $closing_balance : 0.0,
        ];
    }

    public function balanceSheet(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $with_zero_balances = (int) $request->input('with_zero_balances', 0);
        $compare_mode = $request->get('compare_mode', 'none');
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $dataset = $this->buildBalanceSheetDataset($start_date, $end_date, $choose_cost_center_select, $with_zero_balances);
        $compareDataset = null;
        $comparePeriod = null;

        if (in_array($compare_mode, ['previous_period', 'previous_year'], true)) {
            $comparePeriod = $this->resolveIncomeComparePeriod($start_date, $end_date, $compare_mode);
            $compareDataset = $this->buildBalanceSheetDataset(
                $comparePeriod['start'],
                $comparePeriod['end'],
                $choose_cost_center_select,
                $with_zero_balances
            );
        }

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.balance_sheet')
            ->with([
                'company' => $company,
                'sections' => $dataset['sections'],
                'metrics' => $dataset['metrics'],
                'assets' => $dataset['assets'],
                'liabilities' => $dataset['liabilities'],
                'equities' => $dataset['equities'],
                'start_date' => $start_date,
                'end_date' => $end_date,
                'costCenters' => $costCenters,
                'choose_cost_center_select' => $choose_cost_center_select,
                'with_zero_balances' => $with_zero_balances,
                'compare_mode' => $compare_mode,
                'comparePeriod' => $comparePeriod,
                'compareMetrics' => $compareDataset['metrics'] ?? null,
                'total_assets' => $dataset['total_assets'],
                'total_liabilities' => $dataset['total_liabilities'],
                'total_equity' => $dataset['total_equity'],
                'total_liab_owners' => $dataset['total_liab_owners'],
                'difference' => $dataset['difference'],
                'balance_status' => $dataset['balance_status'],
            ]);
    }

    public function balanceSheetExportPdf(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $with_zero_balances = (int) $request->input('with_zero_balances', 0);

        $dataset = $this->buildBalanceSheetDataset($start_date, $end_date, $choose_cost_center_select, $with_zero_balances);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $html = view('accounting::reports.balance_sheet_print', array_merge($dataset, [
            'company' => $company,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('balance-sheet.pdf', 'D');
    }

    public function balanceSheetExportExcel(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $with_zero_balances = (int) $request->input('with_zero_balances', 0);
        $dataset = $this->buildBalanceSheetDataset($start_date, $end_date, $choose_cost_center_select, $with_zero_balances);

        $rows = $this->buildBalanceSheetExportRows($dataset);

        return Excel::download(
            new BalanceSheetExport($rows, [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'difference' => $dataset['difference'],
                'balance_status' => $dataset['balance_status'],
            ]),
            'balance-sheet-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    /**
     * الميزانية العمومية: رصيد تراكمي كما في نهاية الفترة (<= end_date)، بنفس منطق طبيعة الحساب في كشف الحساب
     * (أصول = مدين−دائن، خصوم وحقوق = دائن−مدين). تاريخ البداية لا يُخصم من الرصيد.
     */
    private function buildBalanceSheetDataset(string $start_date, string $end_date, array $choose_cost_center_select, int $with_zero_balances): array
    {
        $costCenterIds = array_values(array_filter($choose_cost_center_select));

        $debitMinusCredit = '(
            COALESCE(SUM(CASE WHEN AAT.type = \'debit\' THEN AAT.amount ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN AAT.type = \'credit\' THEN AAT.amount ELSE 0 END), 0)
        )';

        $balanceExpression = "($debitMinusCredit) * CASE
            WHEN accounting_accounts.account_primary_type = 'asset' THEN 1
            WHEN accounting_accounts.account_primary_type IN ('liability', 'liabilities', 'equity') THEN -1
            ELSE 0
        END";

        $accounts = AccountingAccount::query()
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($end_date, $costCenterIds) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereDate('AAT.operation_date', '<=', $end_date);
                if ($costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $costCenterIds);
                }
            })
            ->leftJoin('accounting_account_types as acc_subtype', 'acc_subtype.id', '=', 'accounting_accounts.account_sub_type_id')
            ->whereIn('accounting_accounts.account_primary_type', ['asset', 'liability', 'liabilities', 'equity'])
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.account_type',
                'accounting_accounts.gl_code',
                'acc_subtype.name_en',
                'acc_subtype.name_ar',
            )
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.account_type',
                'accounting_accounts.gl_code',
                'acc_subtype.name_en as account_sub_type_name_en',
                'acc_subtype.name_ar as account_sub_type_name_ar',
                DB::raw($balanceExpression.' as balance')
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($row) {
                $row->balance = $this->roundMoney($row->balance ?? 0);
                $row->bs_bucket = $this->classifyBalanceSheetBucket($row);

                return $row;
            });

        $accounts = $this->enrichBalanceSheetAccounts($accounts);

        if (! $with_zero_balances) {
            $accounts = $accounts->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.0001)->values();
        }

        $assets = $accounts->where('account_primary_type', 'asset')->values();
        $liabilities = $accounts->filter(fn ($account) => in_array($account->account_primary_type, ['liability', 'liabilities'], true))->values();
        $equities = $accounts->where('account_primary_type', 'equity')->values();

        $total_assets = $this->roundMoney($assets->sum('balance'));
        $total_liabilities = $this->roundMoney($liabilities->sum('balance'));
        $total_equity = $this->roundMoney($equities->sum('balance'));
        $total_liab_owners = $this->roundMoney($total_liabilities + $total_equity);
        $difference = $this->roundMoney(abs($total_assets - $total_liab_owners));

        $metrics = $this->calculateBalanceSheetMetrics($accounts, $total_assets, $total_liabilities, $total_equity);
        $sections = $this->buildBalanceSheetSections($accounts);

        return [
            'accounts' => $accounts,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'sections' => $sections,
            'metrics' => $metrics,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'total_liab_owners' => $total_liab_owners,
            'difference' => $difference,
            'balance_status' => $difference < 0.005 ? __('accounting::lang.balanced') : __('accounting::lang.unbalanced'),
        ];
    }

    private function classifyBalanceSheetBucket(object $account): string
    {
        $subtype = strtolower(trim((string) ($account->account_sub_type_name_en ?? '')));
        $type = strtolower(trim((string) ($account->account_type ?? '')));
        $name = strtolower(trim(($account->name_en ?? '').' '.($account->name_ar ?? '')));
        $gl = (string) ($account->gl_code ?? '');
        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));

        if ($primary === 'asset') {
            if (str_contains($name, 'accumulated') || str_contains($name, 'depreciation') || str_contains($name, 'مجمع')) {
                return 'accumulated_depreciation';
            }

            $glSegment = (strlen($gl) >= 5 && str_starts_with($gl, '111')) ? (int) substr($gl, 3, 2) : 0;
            $isCurrent = $subtype === 'current assets'
                || $type === 'current_assets'
                || ($glSegment >= 1 && $glSegment <= 9);

            if ($isCurrent) {
                if (str_contains($name, 'cash') || str_contains($name, 'صندوق') || $gl === '1101') {
                    return 'cash';
                }
                if (str_contains($name, 'bank') || str_contains($name, 'بنك') || $gl === '1102') {
                    return 'banks';
                }
                if (str_contains($name, 'receivable') || str_contains($name, 'مدين') || str_contains($name, 'عميل') || str_contains($name, 'قبض')) {
                    return 'receivables';
                }
                if (str_contains($name, 'inventory') || str_contains($name, 'مخزون') || $gl === '1105') {
                    return 'inventory';
                }
                if (str_contains($name, 'prepaid') || str_contains($name, 'مقدما')) {
                    return 'prepaid';
                }

                return 'other_current_assets';
            }

            $isFixed = $subtype === 'fixed assets'
                || $type === 'fixed_assets'
                || ($glSegment >= 10 && $glSegment <= 99);

            if ($isFixed) {
                if (str_contains($name, 'investment') || str_contains($name, 'استثمار')) {
                    return 'long_term_assets';
                }

                return 'fixed_assets';
            }

            return 'long_term_assets';
        }

        if (in_array($primary, ['liability', 'liabilities'], true)) {
            if ($subtype === 'long-term liabilities' || $type === 'non_current_liabilities' || str_starts_with($gl, '222')) {
                if (str_contains($name, 'loan') || str_contains($name, 'قرض')) {
                    return 'long_term_loans';
                }

                return 'other_long_term_liabilities';
            }

            if (str_contains($name, 'vat') || str_contains($name, 'ضريبة') || str_contains($name, 'قيمة مضافة')) {
                return 'vat';
            }
            if (str_contains($name, 'payable') || str_contains($name, 'مورد') || str_contains($name, 'دائنون')) {
                return 'suppliers';
            }
            if (str_contains($name, 'accrued') || str_contains($name, 'مستحق')) {
                return 'accrued_expenses';
            }
            if ((str_contains($name, 'short') && str_contains($name, 'loan')) || str_contains($name, 'قصيرة')) {
                return 'short_term_loans';
            }

            return 'other_current_liabilities';
        }

        if ($primary === 'equity') {
            if (str_contains($name, 'capital') || str_contains($name, 'رأس المال')) {
                return 'capital';
            }
            if (str_contains($name, 'retained') || str_contains($name, 'مرحلة')) {
                return 'retained_earnings';
            }
            if (str_contains($name, 'net income') || str_contains($name, 'صافي الربح') || str_contains($name, 'خسارة')) {
                return 'current_net_profit';
            }
            if (str_contains($name, 'reserve') || str_contains($name, 'احتياط')) {
                return 'reserves';
            }

            return 'other_equity';
        }

        return 'other';
    }

    private function enrichBalanceSheetAccounts(Collection $accounts): Collection
    {
        $byId = $accounts->keyBy('id');
        $childCountByParent = $accounts->pluck('parent_account_id')->filter()->countBy();

        return $accounts->map(function ($account) use ($byId, $childCountByParent) {
            $depth = 0;
            $parentId = $account->parent_account_id;
            $guard = 0;
            while ($parentId && $byId->has($parentId) && $guard < 12) {
                $depth++;
                $parentId = $byId->get($parentId)->parent_account_id;
                $guard++;
            }

            $account->depth = $depth;
            $account->has_children = $childCountByParent->get($account->id, 0) > 0;

            return $account;
        });
    }

    private function sumBalanceSheetBuckets(Collection $accounts, array $buckets): float
    {
        return $this->roundMoney(
            $accounts->whereIn('bs_bucket', $buckets)->sum('balance')
        );
    }

    private function accountsForBalanceSheetBuckets(Collection $accounts, array $buckets): Collection
    {
        return $accounts->whereIn('bs_bucket', $buckets)->sortBy('gl_code')->values();
    }

    private function buildBalanceSheetSections(Collection $accounts): array
    {
        $sumBuckets = fn (array $buckets) => $this->sumBalanceSheetBuckets($accounts, $buckets);
        $accountsFor = fn (array $buckets) => $this->accountsForBalanceSheetBuckets($accounts, $buckets);

        $currentAssetBuckets = ['cash', 'banks', 'receivables', 'inventory', 'prepaid', 'other_current_assets'];
        $nonCurrentAssetBuckets = ['fixed_assets', 'accumulated_depreciation', 'long_term_assets'];
        $currentLiabBuckets = ['suppliers', 'accrued_expenses', 'short_term_loans', 'vat', 'other_current_liabilities'];
        $longTermLiabBuckets = ['long_term_loans', 'other_long_term_liabilities'];
        $equityBuckets = ['capital', 'retained_earnings', 'current_net_profit', 'reserves', 'other_equity'];

        $assetGroupDefs = [
            ['buckets' => ['cash'], 'label' => 'bs_cash'],
            ['buckets' => ['banks'], 'label' => 'bs_banks'],
            ['buckets' => ['receivables'], 'label' => 'bs_receivables'],
            ['buckets' => ['inventory'], 'label' => 'bs_inventory'],
            ['buckets' => ['prepaid'], 'label' => 'bs_prepaid_expenses'],
            ['buckets' => ['other_current_assets'], 'label' => 'bs_other_current_assets'],
        ];

        $nonCurrentDefs = [
            ['buckets' => ['fixed_assets'], 'label' => 'bs_fixed_assets'],
            ['buckets' => ['accumulated_depreciation'], 'label' => 'bs_accumulated_depreciation'],
            ['buckets' => ['long_term_assets'], 'label' => 'bs_long_term_assets'],
        ];

        $currentLiabDefs = [
            ['buckets' => ['suppliers'], 'label' => 'bs_suppliers'],
            ['buckets' => ['accrued_expenses'], 'label' => 'bs_accrued_expenses'],
            ['buckets' => ['short_term_loans'], 'label' => 'bs_short_term_loans'],
            ['buckets' => ['vat'], 'label' => 'bs_vat_payable'],
            ['buckets' => ['other_current_liabilities'], 'label' => 'bs_other_current_liabilities'],
        ];

        $longTermLiabDefs = [
            ['buckets' => ['long_term_loans'], 'label' => 'bs_long_term_loans'],
            ['buckets' => ['other_long_term_liabilities'], 'label' => 'bs_other_long_term_liabilities'],
        ];

        $equityDefs = [
            ['buckets' => ['capital'], 'label' => 'bs_capital'],
            ['buckets' => ['retained_earnings'], 'label' => 'bs_retained_earnings'],
            ['buckets' => ['current_net_profit'], 'label' => 'bs_current_net_profit'],
            ['buckets' => ['reserves'], 'label' => 'bs_reserves'],
            ['buckets' => ['other_equity'], 'label' => 'bs_other_equity'],
        ];

        $buildGroups = function (array $defs) use ($accountsFor, $sumBuckets) {
            $groups = [];
            foreach ($defs as $def) {
                $accs = $accountsFor($def['buckets']);
                if ($accs->isEmpty()) {
                    continue;
                }
                $groups[] = [
                    'type' => 'accounts',
                    'label' => __('accounting::lang.'.$def['label']),
                    'accounts' => $accs,
                    'total' => $sumBuckets($def['buckets']),
                ];
            }

            return $groups;
        };

        $totalCurrentAssets = $sumBuckets($currentAssetBuckets);
        $totalNonCurrentAssets = $sumBuckets($nonCurrentAssetBuckets);
        $totalCurrentLiab = $sumBuckets($currentLiabBuckets);
        $totalLongTermLiab = $sumBuckets($longTermLiabBuckets);
        $totalEquity = $sumBuckets($equityBuckets);

        return [
            [
                'key' => 'assets',
                'title' => __('accounting::lang.assets'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_current_assets')]],
                    $buildGroups($assetGroupDefs),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_current_assets'), 'amount' => $totalCurrentAssets]],
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_non_current_assets')]],
                    $buildGroups($nonCurrentDefs),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_non_current_assets'), 'amount' => $totalNonCurrentAssets]],
                    [['type' => 'grand', 'label' => __('accounting::lang.total_assets'), 'amount' => $totalCurrentAssets + $totalNonCurrentAssets]],
                ),
                'total' => $totalCurrentAssets + $totalNonCurrentAssets,
            ],
            [
                'key' => 'liabilities',
                'title' => __('accounting::lang.liabilities'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_current_liabilities')]],
                    $buildGroups($currentLiabDefs),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_current_liabilities'), 'amount' => $totalCurrentLiab]],
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_non_current_liabilities')]],
                    $buildGroups($longTermLiabDefs),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_non_current_liabilities'), 'amount' => $totalLongTermLiab]],
                    [['type' => 'grand', 'label' => __('accounting::lang.bs_total_liabilities'), 'amount' => $totalCurrentLiab + $totalLongTermLiab]],
                ),
                'total' => $totalCurrentLiab + $totalLongTermLiab,
            ],
            [
                'key' => 'equity',
                'title' => __('accounting::lang.equity'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.equity')]],
                    $buildGroups($equityDefs),
                    [['type' => 'grand', 'label' => __('accounting::lang.bs_total_equity'), 'amount' => $totalEquity]],
                ),
                'total' => $totalEquity,
            ],
        ];
    }

    private function calculateBalanceSheetMetrics(
        Collection $accounts,
        float $total_assets,
        float $total_liabilities,
        float $total_equity
    ): array {
        $currentAssets = $this->sumBalanceSheetBuckets($accounts, [
            'cash', 'banks', 'receivables', 'inventory', 'prepaid', 'other_current_assets',
        ]);
        $currentLiabilities = $this->sumBalanceSheetBuckets($accounts, [
            'suppliers', 'accrued_expenses', 'short_term_loans', 'vat', 'other_current_liabilities',
        ]);

        $workingCapital = $this->roundMoney($currentAssets - $currentLiabilities);
        $currentRatio = abs($currentLiabilities) > 0.0001 ? round($currentAssets / $currentLiabilities, 2) : null;
        $debtRatio = abs($total_assets) > 0.0001 ? round($total_liabilities / $total_assets, 4) : null;
        $equityRatio = abs($total_assets) > 0.0001 ? round($total_equity / $total_assets, 4) : null;
        $liquidityPercent = $currentRatio !== null ? round($currentRatio * 100, 1) : null;
        $debtPercent = $debtRatio !== null ? round($debtRatio * 100, 1) : null;
        $equityPercent = $equityRatio !== null ? round($equityRatio * 100, 1) : null;

        return [
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'total_equity' => $total_equity,
            'current_assets' => $currentAssets,
            'current_liabilities' => $currentLiabilities,
            'working_capital' => $workingCapital,
            'current_ratio' => $currentRatio,
            'debt_ratio' => $debtRatio,
            'equity_ratio' => $equityRatio,
            'liquidity_percent' => $liquidityPercent,
            'debt_percent' => $debtPercent,
            'equity_percent' => $equityPercent,
        ];
    }

    private function buildBalanceSheetExportRows(array $dataset): Collection
    {
        $rows = collect();
        $localeAr = app()->getLocale() === 'ar';
        $name = static fn ($account) => $localeAr ? $account->name_ar : $account->name_en;
        $fmt = static fn ($amount) => number_format((float) $amount, 2, '.', '');

        foreach ($dataset['sections'] as $section) {
            $rows->push([$section['title'], '', '']);
            foreach ($section['groups'] as $group) {
                if (($group['type'] ?? '') === 'accounts') {
                    foreach ($group['accounts'] as $account) {
                        $indent = str_repeat('  ', (int) ($account->depth ?? 0));
                        $rows->push([$group['label'], $indent.$name($account), $fmt($account->balance)]);
                    }
                } elseif (in_array($group['type'] ?? '', ['subtotal', 'grand'], true)) {
                    $rows->push([$group['label'], '', $fmt($group['amount'] ?? 0)]);
                } elseif (($group['type'] ?? '') === 'subsection') {
                    $rows->push([$group['label'], '', '']);
                }
            }
        }

        $rows->push([
            __('accounting::lang.total_liab_owners'),
            '',
            $fmt($dataset['total_liab_owners'] ?? 0),
        ]);

        return $rows;
    }

    public function JournalReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $refNo = $request->input('ref_no');
        $note = $request->input('note');
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $journalSources = $this->normalizeJournalSources($request);

        $journals = $this->buildJournalReportQuery($request)->get();

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($journals as $journal) {
            $journalDebit = (float) $journal->transactions->where('type', 'debit')->sum('amount');
            $journalCredit = (float) $journal->transactions->where('type', 'credit')->sum('amount');
            $journal->journal_debit = $journalDebit;
            $journal->journal_credit = $journalCredit;
            $journal->journal_diff = abs($journalDebit - $journalCredit);
            $totalDebit += $journalDebit;
            $totalCredit += $journalCredit;
        }
        $difference = abs($totalDebit - $totalCredit);
        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.journal_report', compact(
            'journals',
            'startDate',
            'endDate',
            'totalDebit',
            'totalCredit',
            'difference',
            'costCenters',
            'choose_cost_center_select',
            'refNo',
            'note',
            'journalSources'
        ));
    }

    public function journalReportExportPdf(Request $request)
    {
        $report = $this->getJournalReportDataset($request);
        $html = view('accounting::reports.journal_report_print', $report)->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('journal-report.pdf', 'D');
    }

    public function journalReportExportExcel(Request $request)
    {
        $report = $this->getJournalReportDataset($request);
        $rows = collect();
        foreach ($report['journals'] as $journal) {
            foreach ($journal->transactions as $transaction) {
                $rows->push([
                    $journal->ref_no,
                    $journal->operation_date
                        ? \Illuminate\Support\Carbon::parse($journal->operation_date)->format('Y-m-d')
                        : '',
                    app()->getLocale() == 'ar' ? $transaction->name_ar : $transaction->name_en,
                    $transaction->gl_code,
                    $transaction->note ?? '',
                    $transaction->type === 'debit' ? number_format((float) $transaction->amount, 2, '.', '') : '0.00',
                    $transaction->type === 'credit' ? number_format((float) $transaction->amount, 2, '.', '') : '0.00',
                ]);
            }
        }

        return Excel::download(
            new JournalReportExport($rows, [
                'start_date' => $report['startDate'],
                'end_date' => $report['endDate'],
                'total_debit' => $report['totalDebit'],
                'total_credit' => $report['totalCredit'],
                'difference' => $report['difference'],
            ]),
            'journal-report-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function getJournalReportDataset(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $refNo = $request->input('ref_no');
        $note = $request->input('note');
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $journalSources = $this->normalizeJournalSources($request);

        $journals = $this->buildJournalReportQuery($request)->get();

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($journals as $journal) {
            $totalDebit += (float) $journal->transactions->where('type', 'debit')->sum('amount');
            $totalCredit += (float) $journal->transactions->where('type', 'credit')->sum('amount');
        }

        return [
            'journals' => $journals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => abs($totalDebit - $totalCredit),
            'journalSources' => $journalSources,
        ];
    }

    /**
     * Base query for journal report (web, PDF, Excel). Applies date/ref/note/cost-center and optional source filter.
     */
    private function buildJournalReportQuery(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $refNo = $request->input('ref_no');
        $note = $request->input('note');
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);

        $query = AccountingAccTransMapping::query()
            ->where('type', 'journal_entry')
            ->when($startDate, fn ($q) => $q->whereDate('operation_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('operation_date', '<=', $endDate))
            ->when($refNo, fn ($q) => $q->where('ref_no', 'like', '%'.$refNo.'%'))
            ->when($note, fn ($q) => $q->where('note', 'like', '%'.$note.'%'))
            ->with(['transactions' => function ($query) {
                $query->join('accounting_accounts', 'accounting_accounts.id', '=', 'accounting_accounts_transactions.accounting_account_id')
                    ->select('accounting_accounts_transactions.*', 'accounting_accounts.name_ar', 'accounting_accounts.name_en', 'accounting_accounts.gl_code')
                    ->when(request()->filled('choose_cost_center_select'), function ($q) {
                        $q->whereIn('accounting_accounts_transactions.cost_center_id', request()->input('choose_cost_center_select', []));
                    });
            }])
            ->whereHas('transactions', function ($query) use ($choose_cost_center_select) {
                if (! empty($choose_cost_center_select)) {
                    $query->whereIn('cost_center_id', $choose_cost_center_select);
                }
            });

        $this->applyJournalReportSourceFilter($query, $this->normalizeJournalSources($request));

        return $query->orderByDesc('operation_date');
    }

    /**
     * @return list<string>
     */
    private function normalizeJournalSources(Request $request): array
    {
        $allowed = ['sales', 'purchases', 'receipt_voucher', 'payment_voucher', 'manual_journal'];
        $raw = $request->input('journal_source');
        if ($raw === null || $raw === '' || $raw === 'all') {
            return [];
        }
        if (is_string($raw)) {
            $raw = [$raw];
        }
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (! is_string($item)) {
                continue;
            }
            $item = trim($item);
            if ($item !== '' && in_array($item, $allowed, true)) {
                $out[$item] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * @param  list<string>  $journalSources  Empty = no filter (all sources).
     */
    private function applyJournalReportSourceFilter($query, array $journalSources): void
    {
        if ($journalSources === []) {
            return;
        }

        $query->where(function ($outer) use ($journalSources) {
            foreach ($journalSources as $key) {
                $outer->orWhere(function ($sub) use ($key) {
                    match ($key) {
                        'sales' => $sub->whereHas('transactions', function ($q) {
                            $q->whereIn('sub_type', ['sell', 'sell-return', 'sell_cash', 'sales_revenue']);
                        }),
                        'purchases' => $sub->whereHas('transactions', function ($q) {
                            $q->whereIn('sub_type', ['purchases', 'purchases-return']);
                        }),
                        'receipt_voucher' => $sub->whereHas('transactions', function ($q) {
                            $q->where('sub_type', 'receipt_voucher');
                        }),
                        'payment_voucher' => $sub->whereHas('transactions', function ($q) {
                            $q->where('sub_type', 'payment_voucher');
                        }),
                        'manual_journal' => $sub->where('is_manual', 1),
                        default => null,
                    };
                });
            }
        });
    }

    public function cash_flow(Request $request)
    {
        $report = CashFlowReportService::dataset($request);
        $costCenters = AccountingCostCenter::where('is_main', 0)->get();
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $detailPaginator = $this->paginateReportRows($report['detailRows'], 20);

        return view('accounting::reports.cash_flow', array_merge($report, [
            'company' => $company,
            'costCenters' => $costCenters,
            'choose_cost_center_select' => $report['costCenterIds'],
            'movement_type' => $report['movementType'],
            'selected_sub_types' => $report['selectedSubTypes'],
            'activity_section' => $report['activitySection'],
            'availableSubTypes' => $report['availableSubTypes'],
            'detailPaginator' => $detailPaginator,
            'compare_mode' => $report['compareMode'],
            'period_group' => $report['periodGroup'],
        ]));
    }

    public function cashFlowExportPdf(Request $request)
    {
        $report = CashFlowReportService::dataset($request);
        $report['company'] = DB::connection('mysql')->table('companies')->find(get_company_id());

        $html = view('accounting::reports.cash_flow_print', $report)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('cash-flow.pdf', 'D');
    }

    public function cashFlowExportExcel(Request $request)
    {
        $report = CashFlowReportService::dataset($request);

        $exportRows = collect($report['detailRows'])->map(function ($row) {
            return [
                $row['section'],
                $row['operation_date'],
                $row['ref_no'],
                $row['transaction_type'],
                $row['movement_type'],
                $row['cost_center'],
                number_format((float) $row['amount'], 2, '.', ''),
            ];
        });

        return Excel::download(
            new CashFlowExport($exportRows, [
                'start_date' => $report['startDate'],
                'end_date' => $report['endDate'],
                'cash_inflows' => $report['cashInflows'],
                'cash_outflows' => $report['cashOutflows'],
                'net_cash_flow' => $report['netCashFlow'],
            ]),
            'cash-flow-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function buildCashFlowDataset(string $startDate, string $endDate, array $choose_cost_center_select, ?string $movement_type, array $selected_sub_types, ?string $activity_section): array
    {
        $defaultSubTypes = ['sell', 'sell_cash', 'purchases', 'sales_revenue', 'receipt_voucher', 'payment_voucher', 'journal_entry'];
        $effectiveSubTypes = ! empty($selected_sub_types) ? $selected_sub_types : $defaultSubTypes;

        $baseQuery = AccountingAccountsTransaction::with(['accTransMapping', 'costCenter'])
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->whereIn('sub_type', $effectiveSubTypes)
            ->when(! empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('cost_center_id', $choose_cost_center_select);
            })
            ->when(! empty($movement_type), function ($query) use ($movement_type) {
                return $query->where('type', $movement_type);
            })
            ->when(! empty($activity_section), function ($query) use ($activity_section) {
                $sectionSubTypes = $this->getCashFlowSectionSubTypes($activity_section);
                if (! empty($sectionSubTypes)) {
                    $query->whereIn('sub_type', $sectionSubTypes);
                }
            })
            ->orderBy('operation_date', 'desc');

        $cashInflows = (clone $baseQuery)->where('type', 'credit')->sum('amount');
        $cashOutflows = (clone $baseQuery)->where('type', 'debit')->sum('amount');
        $netCashFlow = (float) $cashInflows - (float) $cashOutflows;

        $allRows = (clone $baseQuery)->get();
        $rows = $allRows->map(function ($flow) {
            $sectionKey = $this->resolveCashFlowSection($flow->sub_type);

            return [
                'section' => __('accounting::lang.'.$sectionKey.'_activities'),
                'operation_date' => $flow->operation_date,
                'ref_no' => $flow->accTransMapping?->ref_no ?? '--',
                'transaction_type' => Lang::has('accounting::lang.'.$flow->sub_type) ? __('accounting::lang.'.$flow->sub_type) : $flow->sub_type,
                'movement_type' => $flow->type === 'debit' ? __('accounting::lang.debit') : __('accounting::lang.credit'),
                'cost_center' => $flow->costCenter ? ((app()->getLocale() === 'ar' ? $flow->costCenter->name_ar : $flow->costCenter->name_en) ?? $flow->costCenter->name_ar ?? $flow->costCenter->name_en) : '--',
                'amount' => (float) $flow->amount,
            ];
        })->values()->all();

        $sectionSummaries = [
            'operating' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'investing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'financing' => ['inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
        ];

        foreach ($allRows as $flow) {
            $section = $this->resolveCashFlowSection($flow->sub_type);
            if ($flow->type === 'credit') {
                $sectionSummaries[$section]['inflows'] += (float) $flow->amount;
            } else {
                $sectionSummaries[$section]['outflows'] += (float) $flow->amount;
            }
            $sectionSummaries[$section]['net'] = $sectionSummaries[$section]['inflows'] - $sectionSummaries[$section]['outflows'];
        }

        $availableSubTypes = AccountingAccountsTransaction::query()
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->distinct()
            ->pluck('sub_type')
            ->filter()
            ->values();

        return [
            'query' => $baseQuery,
            'cashInflows' => (float) $cashInflows,
            'cashOutflows' => (float) $cashOutflows,
            'netCashFlow' => (float) $netCashFlow,
            'availableSubTypes' => $availableSubTypes,
            'sectionSummaries' => $sectionSummaries,
            'rows' => $rows,
        ];
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function paginateReportRows(array $rows, int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $page = max(1, (int) request()->get('page', 1));
        $collection = collect($rows);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values()->all(),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function resolveCashFlowSection(?string $subType): string
    {
        return CashFlowReportService::resolveSection($subType);
    }

    private function getCashFlowSectionSubTypes(string $section): array
    {
        return CashFlowReportService::getSectionSubTypes($section);
    }

    public function customersSuppliersStatement(Request $request)
    {
        $contactId = $request->query('id') ?? Contact::query()->value('id');
        if (! $contactId) {
            return redirect()->route('accounting-reports')->with('error', __('messages.no_data_found'));
        }

        $report = CustomerSupplierStatementReportService::dataset($request);
        $costCenters = AccountingCostCenter::where('is_main', 0)->get();
        $contactDropdown = Contact::orderBy('name')->get(['id', 'name', 'commercial_register', 'tax_number']);
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $linePaginator = $this->paginateReportRows($report['lines'], 25);

        return view('accounting::reports.customers-suppliers-statement', array_merge($report, [
            'company' => $company,
            'costCenters' => $costCenters,
            'contact_dropdown' => $contactDropdown,
            'choose_cost_center_select' => $report['costCenterIds'],
            'establishment_ids' => $report['establishmentIds'],
            'created_by' => $report['userId'],
            'entry_type' => $report['entryType'],
            'sub_type' => $report['subType'],
            'ref_no' => $report['refNo'],
            'unsettled_only' => $report['unsettledOnly'],
            'compare_mode' => $report['compareMode'],
            'period_group' => $report['periodGroup'],
            'available_sub_types' => $report['availableSubTypes'],
            'linePaginator' => $linePaginator,
            'contact_id' => $report['contactId'],
            'start_date' => $report['startDate'],
            'end_date' => $report['endDate'],
            'current_bal' => $report['currentBalance'],
            'period_debit' => $report['periodDebit'],
            'period_credit' => $report['periodCredit'],
            'net_movement' => $report['closingBalance'] - $report['openingBalance'],
        ]));
    }

    public function customersSuppliersStatementExportPdf(Request $request)
    {
        $report = $this->getCustomerSupplierStatementExportDataset($request);
        $html = view('accounting::reports.customers-suppliers-statement-print', $report)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 10,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('customers-suppliers-statement.pdf', 'D');
    }

    public function customersSuppliersStatementExportExcel(Request $request)
    {
        $report = $this->getCustomerSupplierStatementExportDataset($request);

        $rows = collect($report['rows'])->map(function ($row) {
            return [
                $row['operation_date'],
                $row['ref_no'],
                $row['transaction'],
                $row['description'],
                $row['establishment'],
                $row['cost_center'],
                number_format((float) $row['debit'], 2, '.', ''),
                number_format((float) $row['credit'], 2, '.', ''),
                number_format((float) $row['running_balance'], 2, '.', ''),
                $row['added_by'],
            ];
        });

        return Excel::download(
            new CustomersSuppliersStatementExport($rows, [
                'contact_name' => $report['contact']->name,
                'start_date' => $report['start_date'],
                'end_date' => $report['end_date'],
                'current_balance' => $report['current_bal'],
                'opening_balance' => $report['openingBalance'],
                'closing_balance' => $report['closingBalance'],
                'period_debit' => $report['periodDebit'],
                'period_credit' => $report['periodCredit'],
            ]),
            'customers-suppliers-statement-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function getCustomerSupplierStatementExportDataset(Request $request): array
    {
        $report = CustomerSupplierStatementReportService::dataset($request);
        $report['company'] = DB::connection('mysql')->table('companies')->find(get_company_id());
        $report['start_date'] = $report['startDate'];
        $report['end_date'] = $report['endDate'];
        $report['current_bal'] = $report['currentBalance'];
        $report['rows'] = collect($report['lines'])->map(function (array $line) {
            return [
                'row_type' => $line['row_type'] ?? 'movement',
                'operation_date' => $line['operation_date'] ?? '—',
                'ref_no' => $line['ref_no'],
                'transaction' => $line['transaction_type'],
                'description' => $line['description'],
                'establishment' => $line['establishment_name'],
                'cost_center' => $line['cost_center'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'running_balance' => $line['running_balance'],
                'added_by' => $line['added_by'],
            ];
        })->values()->all();

        return $report;
    }

    public function accountReceivableAgeingReport()
    {
        $accountingUtil = new AccountingUtil;
        $filters = $this->ageingFilters(request());

        $report_details = $accountingUtil->getAgeingReport('sell', 'contact', $filters);

        $contacts = Contact::select('id', 'name')->orderBy('name')->get();

        return view('accounting::reports.account_receivable_ageing_report')
            ->with(compact('report_details', 'contacts', 'filters'));
    }

    public function accountPayableAgeingReport()
    {
        $accountingUtil = new AccountingUtil;
        $filters = $this->ageingFilters(request());

        $report_details = $accountingUtil->getAgeingReport(
            'purchase',
            'contact',
            $filters
        );
        $contacts = Contact::select('id', 'name')->orderBy('name')->get();

        return view('accounting::reports.account_payable_ageing_report')
            ->with(compact('report_details', 'contacts', 'filters'));
    }

    public function accountReceivableAgeingDetails()
    {
        $accountingUtil = new AccountingUtil;
        $filters = $this->ageingFilters(request());

        $report_details = $accountingUtil->getAgeingReport(
            'sell',
            'due_date',
            $filters
        );
        $contacts = Contact::select('id', 'name')->orderBy('name')->get();

        return view('accounting::reports.account_receivable_ageing_details')
            ->with(compact('report_details', 'contacts', 'filters'));
    }

    public function accountPayableAgeingDetails()
    {
        $accountingUtil = new AccountingUtil;
        $filters = $this->ageingFilters(request());

        $report_details = $accountingUtil->getAgeingReport('purchases', 'due_date', $filters);
        $contacts = Contact::select('id', 'name')->orderBy('name')->get();

        return view('accounting::reports.account_payable_ageing_details')
            ->with(compact('report_details', 'contacts', 'filters'));
    }

    public function accountReceivableAgeingReportExportPdf(Request $request)
    {
        return $this->exportAgeingSummaryPdf($request, 'sell', __('accounting::lang.account_recievable_ageing_report'), __('general::general.customer_name'));
    }

    public function accountReceivableAgeingReportExportExcel(Request $request)
    {
        return $this->exportAgeingSummaryExcel($request, 'sell', __('accounting::lang.account_recievable_ageing_report'), __('general::general.customer_name'), 'account-receivable-ageing-report');
    }

    public function accountPayableAgeingReportExportPdf(Request $request)
    {
        return $this->exportAgeingSummaryPdf($request, 'purchases', __('accounting::lang.account_payable_ageing_report'), __('clientsandsuppliers::fields.supplier_name'));
    }

    public function accountPayableAgeingReportExportExcel(Request $request)
    {
        return $this->exportAgeingSummaryExcel($request, 'purchases', __('accounting::lang.account_payable_ageing_report'), __('clientsandsuppliers::fields.supplier_name'), 'account-payable-ageing-report');
    }

    public function accountReceivableAgeingDetailsExportPdf(Request $request)
    {
        return $this->exportAgeingDetailsPdf($request, 'sell', __('accounting::lang.account_receivable_ageing_details'), __('report::general.customer'), __('accounting::lang.invoice'));
    }

    public function accountReceivableAgeingDetailsExportExcel(Request $request)
    {
        return $this->exportAgeingDetailsExcel($request, 'sell', __('accounting::lang.account_receivable_ageing_details'), __('report::general.customer'), __('accounting::lang.invoice'), 'account-receivable-ageing-details');
    }

    public function accountPayableAgeingDetailsExportPdf(Request $request)
    {
        return $this->exportAgeingDetailsPdf($request, 'purchases', __('accounting::lang.account_payable_ageing_details'), __('sales::fields.supplier'), __('menuItemLang.purchases'));
    }

    public function accountPayableAgeingDetailsExportExcel(Request $request)
    {
        return $this->exportAgeingDetailsExcel($request, 'purchases', __('accounting::lang.account_payable_ageing_details'), __('sales::fields.supplier'), __('menuItemLang.purchases'), 'account-payable-ageing-details');
    }

    private function ageingFilters(Request $request): array
    {
        return [
            'as_of_date' => $request->input('as_of_date', now()->toDateString()),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'contact_id' => $request->input('contact_id'),
        ];
    }

    private function exportAgeingSummaryPdf(Request $request, string $type, string $title, string $nameHeader)
    {
        $filters = $this->ageingFilters($request);
        $rows = array_values((new AccountingUtil)->getAgeingReport($type, 'contact', $filters));
        $html = view('accounting::reports.ageing_summary_print', [
            'title' => $title,
            'as_of_date' => $filters['as_of_date'],
            'name_header' => $nameHeader,
            'rows' => $rows,
        ])->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('ageing-summary.pdf', 'D');
    }

    private function exportAgeingSummaryExcel(Request $request, string $type, string $title, string $nameHeader, string $prefix)
    {
        $filters = $this->ageingFilters($request);
        $rows = collect(array_values((new AccountingUtil)->getAgeingReport($type, 'contact', $filters)))
            ->map(function ($row) {
                return [
                    $row['name'],
                    number_format((float) $row['<1'], 2, '.', ''),
                    number_format((float) $row['1_30'], 2, '.', ''),
                    number_format((float) $row['31_60'], 2, '.', ''),
                    number_format((float) $row['61_90'], 2, '.', ''),
                    number_format((float) $row['>90'], 2, '.', ''),
                    number_format((float) $row['total_due'], 2, '.', ''),
                ];
            });

        return Excel::download(
            new AgeingSummaryExport($rows, [
                'title' => $title,
                'as_of_date' => $filters['as_of_date'],
                'name_header' => $nameHeader,
            ]),
            $prefix.'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function exportAgeingDetailsPdf(Request $request, string $type, string $title, string $contactHeader, string $transactionLabel)
    {
        $filters = $this->ageingFilters($request);
        $rows = $this->flattenAgeingDetails((new AccountingUtil)->getAgeingReport($type, 'due_date', $filters), $transactionLabel);
        $html = view('accounting::reports.ageing_details_print', [
            'title' => $title,
            'as_of_date' => $filters['as_of_date'],
            'contact_header' => $contactHeader,
            'rows' => $rows,
        ])->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('ageing-details.pdf', 'D');
    }

    private function exportAgeingDetailsExcel(Request $request, string $type, string $title, string $contactHeader, string $transactionLabel, string $prefix)
    {
        $filters = $this->ageingFilters($request);
        $rows = collect($this->flattenAgeingDetails((new AccountingUtil)->getAgeingReport($type, 'due_date', $filters), $transactionLabel))
            ->map(function ($row) {
                return [
                    $row['bucket'],
                    $row['transaction_date'],
                    $row['transaction_type'],
                    $row['ref_no'],
                    $row['contact_name'],
                    $row['due_date'],
                    number_format((float) $row['due'], 2, '.', ''),
                ];
            });

        return Excel::download(
            new AgeingDetailsExport($rows, [
                'title' => $title,
                'as_of_date' => $filters['as_of_date'],
                'contact_header' => $contactHeader,
            ]),
            $prefix.'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function expenseReport(Request $request)
    {
        $report = ExpenseReportService::dataset($request);
        $expenseAccounts = ExpenseReportService::reportableAccountsQuery()->with('account_sub_type')->get();
        $treasuryAccounts = TreasuryAccounts::query()->get();
        $costCenters = AccountingCostCenter::forDropdown();
        $taxes = Tax::query()->orderBy('name')->get();
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $expenseCreators = \Modules\Accounting\Models\AccountingAccountsTransaction::query()
            ->whereIn('accounting_account_id', ExpenseReportService::reportableAccountIds())
            ->whereDate('operation_date', '>=', $report['startDate'])
            ->whereDate('operation_date', '<=', $report['endDate'])
            ->with('createdBy')
            ->get()
            ->pluck('createdBy')
            ->filter()
            ->unique('id')
            ->sortBy(app()->getLocale() === 'ar' ? 'name' : 'name_en');

        return view('accounting::reports.expense_report', array_merge($report, [
            'company' => $company,
            'expenseAccounts' => $expenseAccounts,
            'treasuryAccounts' => $treasuryAccounts,
            'costCenters' => $costCenters,
            'taxes' => $taxes,
            'expenseCreators' => $expenseCreators,
            'expenseCategories' => ExpenseReportService::CATEGORY_KEYS,
            'debitAccountIds' => array_values(array_filter(array_map('intval', (array) $request->input('debit_account_ids', [])))),
            'creditAccountIds' => array_values(array_filter(array_map('intval', (array) $request->input('credit_account_ids', [])))),
            'costCenterIds' => array_values(array_filter(array_map('intval', (array) $request->input('cost_center_ids', [])))),
            'createdByIds' => array_values(array_filter(array_map('intval', (array) $request->input('created_by_ids', [])))),
            'selectedCategories' => array_values(array_intersect(
                (array) $request->input('expense_categories', []),
                ExpenseReportService::CATEGORY_KEYS
            )),
            'taxId' => $request->input('tax_id', 'all'),
            'keyword' => trim((string) $request->input('q', '')),
            'withAttachments' => $request->boolean('with_attachments'),
        ]));
    }

    public function expenseReportExportPdf(Request $request)
    {
        $report = ExpenseReportService::dataset($request);
        $report['company'] = DB::connection('mysql')->table('companies')->find(get_company_id());
        $html = view('accounting::reports.expense_report_print', $report)->render();
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 10,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('expense-report.pdf', 'D');
    }

    public function expenseReportExportExcel(Request $request)
    {
        $report = ExpenseReportService::dataset($request);
        $localeAr = app()->getLocale() === 'ar';
        $rows = collect();
        foreach ($report['expenses'] as $line) {
            $debit = $line->debitAccount;
            $debitLabel = $debit
                ? (($localeAr ? $debit->name_ar : $debit->name_en).' ('.$debit->gl_code.')')
                : '';
            $credit = $line->creditAccount;
            $creditLabel = $credit
                ? (($localeAr ? $credit->name_ar : $credit->name_en).' ('.$credit->gl_code.')')
                : '';
            $cc = $line->costCenter;
            $ccLabel = $cc ? ($localeAr ? $cc->name_ar : $cc->name_en) : '';
            $rows->push([
                $line->date->format('Y-m-d'),
                $line->line_id,
                $debitLabel,
                $creditLabel,
                $ccLabel,
                $line->description ?? '',
                number_format($line->net, 2, '.', ''),
                number_format($line->tax, 2, '.', ''),
                number_format($line->total, 2, '.', ''),
                (string) ($line->source_label ?? ''),
            ]);
        }

        return Excel::download(
            new ExpenseReportExport($rows, [
                'start_date' => $report['startDate'],
                'end_date' => $report['endDate'],
                'count' => $report['summary']['count'],
                'net' => $report['summary']['net'],
                'tax' => $report['summary']['tax'],
                'gross' => $report['summary']['gross'],
            ]),
            'expense-report-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function flattenAgeingDetails(array $reportDetails, string $transactionLabel): array
    {
        $bucketLabels = [
            'current' => __('accounting::lang.current'),
            '1_30' => __('accounting::lang.days_past_due', ['days' => '1 - 30']),
            '31_60' => __('accounting::lang.days_past_due', ['days' => '31 - 60']),
            '61_90' => __('accounting::lang.days_past_due', ['days' => '61 - 90']),
            '>90' => __('accounting::lang.91_and_over_past_due'),
        ];
        $rows = [];
        foreach ($reportDetails as $bucketKey => $bucketRows) {
            foreach ($bucketRows as $row) {
                $rows[] = [
                    'bucket' => $bucketLabels[$bucketKey] ?? $bucketKey,
                    'transaction_date' => $row['transaction_date'],
                    'transaction_type' => $transactionLabel,
                    'ref_no' => $row['ref_no'] ?? $row['invoice_no'] ?? '--',
                    'contact_name' => $row['contact_name'],
                    'due_date' => $row['due_date'],
                    'due' => (float) $row['due'],
                ];
            }
        }

        return $rows;
    }
}
