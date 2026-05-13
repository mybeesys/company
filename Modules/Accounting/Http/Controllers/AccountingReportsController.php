<?php

namespace Modules\Accounting\Http\Controllers;

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
        $revenue_net = 0;
        $cost_of_revenue = 0;
        $total_expense = 0;
        $total_other_income = 0;
        $total_other_expense = 0;

        foreach ($accounts as $account) {
            $debit = $account->debit_balance;
            $credit = $account->credit_balance;

            $balance = 0;

            switch ($account->acc_type) {
                case 'income':
                    $balance = $credit - $debit;
                    $revenue_net += $balance;
                    break;

                case 'cost_of_sales':
                    $balance = $debit - $credit;
                    $cost_of_revenue += $balance;
                    break;

                case 'expenses':
                    $balance = $debit - $credit;
                    $total_expense += $balance;
                    break;

                case 'other_income':
                    $balance = $credit - $debit;
                    $total_other_income += $balance;
                    break;

                case 'other_expenses':
                    $balance = $debit - $credit;
                    $total_other_expense += $balance;
                    break;
            }
        }

        $gross_profit = $revenue_net - $cost_of_revenue;
        $operation_income = $gross_profit - $total_expense;
        $income_before_tax = $operation_income + $total_other_income - $total_other_expense;

        // ضريبة على الربح فقط (لا تُحسب على خسارة الفترة) — أنسب لنموذج نسبة من الربح المحاسبي
        $taxPercent = (float) (Tax::query()->value('amount') ?? 0);
        $taxableBase = max(0.0, (float) $income_before_tax);
        $tax_amount = ($taxPercent * $taxableBase) / 100;

        return [
            'gross_profit' => $gross_profit,
            'operation_income' => $operation_income,
            'operating_profit' => $operation_income,
            'income_before_tax' => $income_before_tax,
            'tax_amount' => $tax_amount,
            'revenue_net' => $revenue_net,
            'cost_of_revenue' => $cost_of_revenue,
            'total_expense' => $total_expense,
            'total_operating_expenses' => $total_expense,
            'total_other_income' => $total_other_income,
            'total_other_expense' => $total_other_expense,
        ];
    }

    public function incomeStatement()
    {
        $start_date = request()->start_date ?? now()->startOfYear()->format('Y-m-d');
        $end_date = request()->end_date ?? now()->format('Y-m-d');
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $choose_cost_center_select = request()->choose_cost_center_select ?? [];

        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select);
        $accounts = $incomeDataset['accounts'];
        $data = $incomeDataset['data'];
        $revenueAccounts = $incomeDataset['revenueAccounts'];
        $cogsAccounts = $incomeDataset['cogsAccounts'];
        $expenseAccounts = $incomeDataset['expenseAccounts'];

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.income-statement')
            ->with(compact(
                'accounts',
                'revenueAccounts',
                'cogsAccounts',
                'expenseAccounts',
                'start_date',
                'end_date',
                'data',
                'company',
                'costCenters',
                'choose_cost_center_select'
            ));
    }

    public function incomeStatementExportPdf(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);

        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select);

        $html = view('accounting::reports.income-statement-print', [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $incomeDataset['data'],
            'revenueAccounts' => $incomeDataset['revenueAccounts'],
            'cogsAccounts' => $incomeDataset['cogsAccounts'],
            'expenseAccounts' => $incomeDataset['expenseAccounts'],
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

        $incomeDataset = $this->buildIncomeStatementDataset($start_date, $end_date, $choose_cost_center_select);

        $rows = collect();
        foreach ($incomeDataset['revenueAccounts'] as $account) {
            $rows->push([
                __('accounting::lang.Revenues').' — '.(app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en),
                number_format((float) $account->amount, 2, '.', ''),
            ]);
        }
        $rows->push([__('accounting::lang.total').' '.__('accounting::lang.Revenues'), number_format((float) ($incomeDataset['data']['revenue_net'] ?? 0), 2, '.', '')]);

        foreach ($incomeDataset['cogsAccounts'] as $account) {
            $rows->push([
                __('accounting::lang.income_statement_cost_of_revenue').' — '.(app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en),
                number_format((float) $account->amount, 2, '.', ''),
            ]);
        }
        if ($incomeDataset['cogsAccounts']->isNotEmpty()) {
            $rows->push([__('accounting::lang.income_statement_total_cost_of_revenue'), number_format((float) ($incomeDataset['data']['cost_of_revenue'] ?? 0), 2, '.', '')]);
        }
        $rows->push([__('report::general.gross_profit'), number_format((float) ($incomeDataset['data']['gross_profit'] ?? 0), 2, '.', '')]);

        foreach ($incomeDataset['expenseAccounts'] as $account) {
            $rows->push([
                __('accounting::lang.income_statement_operating_expenses').' — '.(app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en),
                number_format((float) $account->amount, 2, '.', ''),
            ]);
        }
        $rows->push([__('accounting::lang.income_statement_total_operating_expenses'), number_format((float) ($incomeDataset['data']['total_expense'] ?? 0), 2, '.', '')]);
        $rows->push([__('accounting::lang.income_statement_operating_profit'), number_format((float) ($incomeDataset['data']['operating_profit'] ?? $incomeDataset['data']['operation_income'] ?? 0), 2, '.', '')]);
        $rows->push([__('accounting::lang.income_before_tax'), number_format((float) ($incomeDataset['data']['income_before_tax'] ?? 0), 2, '.', '')]);
        $rows->push([__('accounting::lang.tax_amount'), number_format((float) ($incomeDataset['data']['tax_amount'] ?? 0), 2, '.', '')]);
        $rows->push([__('accounting::lang.net_profit'), number_format((float) (($incomeDataset['data']['income_before_tax'] ?? 0) - ($incomeDataset['data']['tax_amount'] ?? 0)), 2, '.', '')]);

        $filename = 'income-statement-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new IncomeStatementExport($rows, [
                'start_date' => $start_date,
                'end_date' => $end_date,
            ]),
            $filename
        );
    }

    private function buildIncomeStatementDataset(string $start_date, string $end_date, array $choose_cost_center_select): array
    {
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
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type',
                'acc_subtype.name_en',
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($account) {
                $isCogs = $account->account_type === 'expenses'
                    && ($account->account_sub_type_name_en === 'Cost Of Sales');
                $account->acc_type = $isCogs ? 'cost_of_sales' : $account->account_type;

                return $account;
            });

        $data = $this->getIcomeStatementData($accounts);
        $revenueAccounts = $accounts
            ->where('acc_type', 'income')
            ->map(function ($account) {
                $account->amount = (float) $account->credit_balance - (float) $account->debit_balance;

                return $account;
            })
            ->filter(fn ($account) => abs((float) $account->amount) > 0.0001)
            ->values();

        $cogsAccounts = $accounts
            ->where('acc_type', 'cost_of_sales')
            ->map(function ($account) {
                $account->amount = (float) $account->debit_balance - (float) $account->credit_balance;

                return $account;
            })
            ->filter(fn ($account) => abs((float) $account->amount) > 0.0001)
            ->values();

        $expenseAccounts = $accounts
            ->where('acc_type', 'expenses')
            ->map(function ($account) {
                $account->amount = (float) $account->debit_balance - (float) $account->credit_balance;

                return $account;
            })
            ->filter(fn ($account) => abs((float) $account->amount) > 0.0001)
            ->values();

        return [
            'accounts' => $accounts,
            'data' => $data,
            'revenueAccounts' => $revenueAccounts,
            'cogsAccounts' => $cogsAccounts,
            'expenseAccounts' => $expenseAccounts,
        ];
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
            $totalDebitOpeningBalance = 0;
            $totalCreditOpeningBalance = 0;
            $totalClosingDebitBalance = 0;
            $totalClosingCreditBalance = 0;
            $totalDebitBalance = 0;
            $totalCreditBalance = 0;

            foreach ($accounts as $account) {
                $totalDebitBalance += $account->debit_balance;
                $totalCreditBalance += $account->credit_balance;
                $totalDebitOpeningBalance += $account->debit_opening_balance;
                $totalCreditOpeningBalance += $account->credit_opening_balance;

                $closing_balance = $this->calculateClosingBalance($account);
                $totalClosingDebitBalance += $closing_balance['closing_debit_balance'];
                $totalClosingCreditBalance += $closing_balance['closing_credit_balance'];
            }

            // dd($accounts->get());
            return DataTables::of($accounts)
                ->editColumn('gl_code', function ($account) {
                    return $account->gl_code;
                })

                ->editColumn('name', function ($account) {
                    return $account->name;
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
                    $closing_balance = $this->calculateClosingBalance($account);

                    return $this->roundMoney($closing_balance['closing_debit_balance'] ?? 0);
                })
                ->addColumn('closing_credit_balance', function ($account) {
                    $closing_balance = $this->calculateClosingBalance($account);

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
                ])
                ->rawColumns(['action', 'closing_debit_balance', 'closing_credit_balance', 'debit_balance', 'credit_balance', 'name', 'gl_code'])

                ->make(true);
        }

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.trial_balance')
            ->with(compact('levelsArray', 'accounts_array', 'costCenters'));
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

        $dataset = $this->buildBalanceSheetDataset($start_date, $end_date, $choose_cost_center_select, $with_zero_balances);
        $assets = $dataset['assets'];
        $liabilities = $dataset['liabilities'];
        $equities = $dataset['equities'];
        $total_assets = $dataset['total_assets'];
        $total_liab_owners = $dataset['total_liab_owners'];
        $difference = $dataset['difference'];
        $balance_status = $dataset['balance_status'];
        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.balance_sheet')
            ->with(compact(
                'assets',
                'liabilities',
                'equities',
                'start_date',
                'end_date',
                'costCenters',
                'choose_cost_center_select',
                'with_zero_balances',
                'total_assets',
                'total_liab_owners',
                'difference',
                'balance_status'
            ));
    }

    public function balanceSheetExportPdf(Request $request)
    {
        $start_date = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $with_zero_balances = (int) $request->input('with_zero_balances', 0);

        $dataset = $this->buildBalanceSheetDataset($start_date, $end_date, $choose_cost_center_select, $with_zero_balances);

        $html = view('accounting::reports.balance_sheet_print', array_merge($dataset, [
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

        $rows = collect();
        foreach ($dataset['assets'] as $asset) {
            $rows->push([__('accounting::lang.assets'), app()->getLocale() == 'ar' ? $asset->name_ar : $asset->name_en, number_format((float) $asset->balance, 2, '.', '')]);
        }
        foreach ($dataset['liabilities'] as $liability) {
            $rows->push([__('accounting::lang.liabilities'), app()->getLocale() == 'ar' ? $liability->name_ar : $liability->name_en, number_format((float) $liability->balance, 2, '.', '')]);
        }
        foreach ($dataset['equities'] as $equity) {
            $rows->push([__('accounting::lang.equity'), app()->getLocale() == 'ar' ? $equity->name_ar : $equity->name_en, number_format((float) $equity->balance, 2, '.', '')]);
        }
        $rows->push([__('accounting::lang.total_assets'), '-', number_format((float) $dataset['total_assets'], 2, '.', '')]);
        $rows->push([__('accounting::lang.total_liab_owners'), '-', number_format((float) $dataset['total_liab_owners'], 2, '.', '')]);

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

        $baseQuery = AccountingAccount::query()
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($end_date, $costCenterIds) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereDate('AAT.operation_date', '<=', $end_date);
                if ($costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $costCenterIds);
                }
            })
            ->whereIn('accounting_accounts.account_primary_type', ['asset', 'liability', 'liabilities', 'equity'])
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.gl_code'
            )
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.gl_code',
                DB::raw($balanceExpression.' as balance')
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($row) {
                $row->balance = $this->roundMoney($row->balance ?? 0);

                return $row;
            });

        $filtered = $with_zero_balances
            ? $baseQuery
            : $baseQuery->filter(fn ($account) => abs((float) ($account->balance ?? 0)) > 0.0001)->values();

        $assets = $filtered->where('account_primary_type', 'asset')->values();
        $liabilities = $filtered->filter(fn ($account) => in_array($account->account_primary_type, ['liability', 'liabilities'], true))->values();
        $equities = $filtered->where('account_primary_type', 'equity')->values();

        $total_assets = $this->roundMoney($assets->sum('balance'));
        $total_liab_owners = $this->roundMoney(
            $this->roundMoney($liabilities->sum('balance')) + $this->roundMoney($equities->sum('balance'))
        );
        $difference = $this->roundMoney(abs($total_assets - $total_liab_owners));

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'total_assets' => $total_assets,
            'total_liab_owners' => $total_liab_owners,
            'difference' => $difference,
            'balance_status' => $difference < 0.005 ? __('accounting::lang.balanced') : __('accounting::lang.unbalanced'),
        ];
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
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $movement_type = $request->input('movement_type');
        $selected_sub_types = $request->input('sub_types', []);
        $activity_section = $request->input('activity_section');

        $dataset = $this->buildCashFlowDataset($startDate, $endDate, $choose_cost_center_select, $movement_type, $selected_sub_types, $activity_section);

        $operatingCashFlows = $dataset['query']->paginate(15);
        $operatingCashFlows->getCollection()->transform(function ($flow) {
            $flow->section_key = $this->resolveCashFlowSection($flow->sub_type);

            return $flow;
        });
        $cashInflows = $dataset['cashInflows'];
        $cashOutflows = $dataset['cashOutflows'];
        $netCashFlow = $dataset['netCashFlow'];
        $availableSubTypes = $dataset['availableSubTypes'];
        $sectionSummaries = $dataset['sectionSummaries'];
        $rows = $dataset['rows'];

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.cash_flow', compact(
            'operatingCashFlows',
            'cashInflows',
            'cashOutflows',
            'netCashFlow',
            'startDate',
            'endDate',
            'costCenters',
            'choose_cost_center_select',
            'movement_type',
            'selected_sub_types',
            'activity_section',
            'availableSubTypes',
            'sectionSummaries',
            'rows'
        ));
    }

    public function cashFlowExportPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $movement_type = $request->input('movement_type');
        $selected_sub_types = $request->input('sub_types', []);
        $activity_section = $request->input('activity_section');

        $dataset = $this->buildCashFlowDataset($startDate, $endDate, $choose_cost_center_select, $movement_type, $selected_sub_types, $activity_section);

        $html = view('accounting::reports.cash_flow_print', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'rows' => $dataset['rows'],
            'cashInflows' => $dataset['cashInflows'],
            'cashOutflows' => $dataset['cashOutflows'],
            'netCashFlow' => $dataset['netCashFlow'],
            'sectionSummaries' => $dataset['sectionSummaries'],
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

        return $mpdf->Output('cash-flow.pdf', 'D');
    }

    public function cashFlowExportExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $choose_cost_center_select = $request->input('choose_cost_center_select', []);
        $movement_type = $request->input('movement_type');
        $selected_sub_types = $request->input('sub_types', []);
        $activity_section = $request->input('activity_section');

        $dataset = $this->buildCashFlowDataset($startDate, $endDate, $choose_cost_center_select, $movement_type, $selected_sub_types, $activity_section);

        $exportRows = collect($dataset['rows'])->map(function ($row) {
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
                'start_date' => $startDate,
                'end_date' => $endDate,
                'cash_inflows' => $dataset['cashInflows'],
                'cash_outflows' => $dataset['cashOutflows'],
                'net_cash_flow' => $dataset['netCashFlow'],
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

    private function resolveCashFlowSection(?string $subType): string
    {
        $subType = (string) $subType;
        if (in_array($subType, $this->getCashFlowSectionSubTypes('investing'), true)) {
            return 'investing';
        }
        if (in_array($subType, $this->getCashFlowSectionSubTypes('financing'), true)) {
            return 'financing';
        }

        return 'operating';
    }

    private function getCashFlowSectionSubTypes(string $section): array
    {
        $map = [
            'operating' => ['sell', 'sell_cash', 'purchases', 'sales_revenue', 'receipt_voucher', 'payment_voucher', 'expense', 'expense_refund'],
            'investing' => ['asset_sale', 'asset_purchase', 'fixed_asset', 'capital_expenditure', 'periodic_inventory'],
            'financing' => ['loan', 'loan_received', 'loan_payment', 'equity', 'capital', 'owner_withdrawal', 'owner_injection'],
        ];

        return $map[$section] ?? [];
    }

    public function customersSuppliersStatement(Request $request)
    {
        $accountingUtil = new AccountingUtil;
        $contact_id = $request->query('id') ?? Contact::query()->value('id');
        if (! $contact_id) {
            return redirect()->route('accounting-reports')->with('error', __('messages.no_data_found'));
        }
        $contact = Contact::with(['transactions'])
            ->findOrFail($contact_id);

        $start_date = $request->query('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $end_date = $request->query('end_date') ?? now()->endOfMonth()->format('Y-m-d');
        $choose_cost_center_select = $request->query('choose_cost_center_select') ?? [];
        $entry_type = $request->query('entry_type');
        $balance_side = $request->query('balance_side');
        $sub_type = $request->query('sub_type');
        $ref_no = $request->query('ref_no');

        $statementQuery = $this->buildCustomerSupplierStatementQuery(
            (int) $contact_id,
            $start_date,
            $end_date,
            $choose_cost_center_select,
            $entry_type ?: $balance_side,
            $sub_type,
            $ref_no
        );

        if ($request->ajax()) {
            $period_debit = (clone $statementQuery)->where('aat.type', 'debit')->sum('aat.amount');
            $period_credit = (clone $statementQuery)->where('aat.type', 'credit')->sum('aat.amount');

            return DataTables::of($statementQuery)
                ->editColumn('operation_date', function ($row) {
                    return $row->operation_date;
                })

                ->editColumn('cost_center_name', function ($row) {
                    return app()->getLocale() === 'ar'
                        ? ($row->cost_center_name_ar ?? $row->cost_center_name_en ?? '--')
                        : ($row->cost_center_name_en ?? $row->cost_center_name_ar ?? '--');
                })

                ->editColumn('ref_no', function ($row) {
                    $description = $row->atm_ref_no ?: ($row->invoice_no ?: ($row->payment_ref_no ?: '--'));
                    if (! empty($row->atm_id)) {
                        $description = '<a class=" btn-modal"
                      data-container="#printJournalEntry"
                        href="'.action('\Modules\Accounting\Http\Controllers\JournalEntryController@print', [$row->atm_id]).'"
                         >
                            '.$description.'
                        </a>';
                    }

                    return $description;
                })
                ->addColumn('transaction', function ($row) {
                    if (Lang::has('accounting::lang.'.$row->sub_type)) {

                        $description = __('accounting::lang.'.$row->sub_type);
                    } else {
                        $description = $row->sub_type;
                    }

                    return $description;
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return '<span class="debit" data-orig-value="'.$row->amount.'">'.number_format((float) $row->amount, 2, '.', '').'</span>';
                    }

                    return '';
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return '<span class="credit"  data-orig-value="'.$row->amount.'">'.number_format((float) $row->amount, 2, '.', '').'</span>';
                    }

                    return '';
                })
                ->rawColumns(['ref_no', 'debit', 'credit', 'cost_center_name', 'balance', 'action'])
                ->with([
                    'period_debit' => (float) $period_debit,
                    'period_credit' => (float) $period_credit,
                ])
                ->make(true);
        }

        $contact_dropdown = Contact::all();

        $current_bal = Contact::where('cs_contacts.id', $contact_id)
            ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
            ->select([DB::raw($accountingUtil->balanceFormula())]);

        $current_bal = $current_bal?->first()->balance;

        $total_debit_bal = Contact::join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )

            ->where('cs_contacts.id', $contact_id)
            ->select(DB::raw("SUM(IF((AAT.type = 'debit'), AAT.amount, 0)) as balance"))
            ->first();
        $total_debit_bal = $total_debit_bal->balance;

        $total_credit_bal = Contact::join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )

            ->where('cs_contacts.id', $contact_id)
            ->select(DB::raw("SUM(IF((AAT.type = 'credit'), AAT.amount, 0)) as balance"))
            ->first();

        $total_credit_bal = $total_credit_bal->balance;

        $period_debit = (clone $statementQuery)->where('aat.type', 'debit')->sum('aat.amount');
        $period_credit = (clone $statementQuery)->where('aat.type', 'credit')->sum('aat.amount');
        $net_movement = (float) $period_debit - (float) $period_credit;

        $available_sub_types = Contact::where('cs_contacts.id', $contact_id)
            ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
            ->whereDate('aat.operation_date', '>=', $start_date)
            ->whereDate('aat.operation_date', '<=', $end_date)
            ->when(! empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('aat.cost_center_id', $choose_cost_center_select);
            })
            ->distinct()
            ->pluck('aat.sub_type');

        $costCenters = AccountingCostCenter::where('is_main', 0)->get();

        return view('accounting::reports.customers-suppliers-statement')
            ->with(compact(
                'contact',
                'contact_dropdown',
                'costCenters',
                'choose_cost_center_select',
                'current_bal',
                'contact_id',
                'total_debit_bal',
                'total_credit_bal',
                'entry_type',
                'balance_side',
                'sub_type',
                'ref_no',
                'available_sub_types',
                'period_debit',
                'period_credit',
                'net_movement'
            ));
    }

    public function customersSuppliersStatementExportPdf(Request $request)
    {
        $report = $this->getCustomerSupplierStatementExportDataset($request);
        $html = view('accounting::reports.customers-suppliers-statement-print', $report)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('customers-suppliers-statement.pdf', 'D');
    }

    public function customersSuppliersStatementExportExcel(Request $request)
    {
        $report = $this->getCustomerSupplierStatementExportDataset($request);

        $rows = collect($report['rows'])->map(function ($row) {
            return [
                $row['ref_no'],
                $row['operation_date'],
                $row['transaction'],
                $row['cost_center'],
                $row['note'],
                $row['added_by'],
                number_format((float) $row['debit'], 2, '.', ''),
                number_format((float) $row['credit'], 2, '.', ''),
            ];
        });

        return Excel::download(
            new CustomersSuppliersStatementExport($rows, [
                'contact_name' => $report['contact']->name,
                'start_date' => $report['start_date'],
                'end_date' => $report['end_date'],
                'current_balance' => $report['current_bal'],
                'period_debit' => $report['period_debit'],
                'period_credit' => $report['period_credit'],
            ]),
            'customers-suppliers-statement-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    private function getCustomerSupplierStatementExportDataset(Request $request): array
    {
        $contact_id = (int) ($request->query('id') ?? Contact::query()->value('id'));
        $contact = Contact::findOrFail($contact_id);
        $start_date = $request->query('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $end_date = $request->query('end_date') ?? now()->endOfMonth()->format('Y-m-d');
        $choose_cost_center_select = $request->query('choose_cost_center_select') ?? [];
        $entry_type = $request->query('entry_type');
        $balance_side = $request->query('balance_side');
        $sub_type = $request->query('sub_type');
        $ref_no = $request->query('ref_no');

        $query = $this->buildCustomerSupplierStatementQuery(
            $contact_id,
            $start_date,
            $end_date,
            $choose_cost_center_select,
            $entry_type ?: $balance_side,
            $sub_type,
            $ref_no
        );

        $rows = (clone $query)->get()->map(function ($row) {
            $displayRef = $row->atm_ref_no ?: ($row->invoice_no ?: ($row->payment_ref_no ?: '--'));

            return [
                'ref_no' => $displayRef,
                'operation_date' => $row->operation_date,
                'transaction' => Lang::has('accounting::lang.'.$row->sub_type) ? __('accounting::lang.'.$row->sub_type) : $row->sub_type,
                'cost_center' => app()->getLocale() === 'ar'
                    ? ($row->cost_center_name_ar ?? $row->cost_center_name_en ?? '--')
                    : ($row->cost_center_name_en ?? $row->cost_center_name_ar ?? '--'),
                'note' => $row->note ?? '--',
                'added_by' => $row->added_by ?? '--',
                'debit' => $row->type === 'debit' ? (float) $row->amount : 0.0,
                'credit' => $row->type === 'credit' ? (float) $row->amount : 0.0,
            ];
        })->values()->all();

        $period_debit = (clone $query)->where('aat.type', 'debit')->sum('aat.amount');
        $period_credit = (clone $query)->where('aat.type', 'credit')->sum('aat.amount');

        $current_bal = Contact::where('cs_contacts.id', $contact_id)
            ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin('accounting_accounts as accounting_accounts', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
            ->select([DB::raw((new AccountingUtil)->balanceFormula())])
            ->first()?->balance ?? 0;

        return compact('contact', 'rows', 'start_date', 'end_date', 'current_bal', 'period_debit', 'period_credit');
    }

    private function buildCustomerSupplierStatementQuery(
        int $contact_id,
        string $start_date,
        string $end_date,
        array $choose_cost_center_select,
        ?string $entry_type,
        ?string $sub_type,
        ?string $ref_no
    ) {
        return Contact::where('cs_contacts.id', $contact_id)
            ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
            ->leftJoin('accounting_acc_trans_mappings as atm', 'aat.acc_trans_mapping_id', '=', 'atm.id')
            ->leftJoin('transaction_payments as tp', 'aat.transaction_payment_id', '=', 'tp.id')
            ->leftJoin('emp_employees as u', 'aat.created_by', '=', 'u.id')
            ->leftJoin('accounting_cost_centers as cc', 'aat.cost_center_id', '=', 'cc.id')
            ->whereDate('aat.operation_date', '>=', $start_date)
            ->whereDate('aat.operation_date', '<=', $end_date)
            ->when(! empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('aat.cost_center_id', $choose_cost_center_select);
            })
            ->when(! empty($entry_type), function ($query) use ($entry_type) {
                return $query->where('aat.type', $entry_type);
            })
            ->when(! empty($sub_type), function ($query) use ($sub_type) {
                return $query->where('aat.sub_type', $sub_type);
            })
            ->when(! empty($ref_no), function ($query) use ($ref_no) {
                return $query->where(function ($q) use ($ref_no) {
                    $q->where('atm.ref_no', 'like', '%'.$ref_no.'%')
                        ->orWhere('t.ref_no', 'like', '%'.$ref_no.'%')
                        ->orWhere('tp.payment_ref_no', 'like', '%'.$ref_no.'%');
                });
            })
            ->select(
                'aat.id',
                'aat.operation_date',
                'aat.sub_type',
                'aat.type',
                'aat.cost_center_id',
                'atm.ref_no as atm_ref_no',
                'tp.payment_ref_no',
                'atm.id as atm_id',
                'cc.name_ar as cost_center_name_ar',
                'cc.name_en as cost_center_name_en',
                'atm.note',
                'aat.amount',
                'u.name as added_by',
                't.ref_no as invoice_no'
            )
            ->orderByDesc('aat.operation_date')
            ->orderByDesc('aat.id');
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
