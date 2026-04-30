<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Utils\AccountingUtil;

class AccountingDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $start_date = request()->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $end_date = request()->input('end_date', now()->format('Y-m-d'));
        $choose_cost_center_select = request()->input('choose_cost_center_select', []);

        $balance_formula = AccountingUtil::balanceFormula();

        $tree_of_account_overview = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            function ($join) use ($start_date, $end_date, $choose_cost_center_select) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereBetween('AAT.operation_date', [$start_date, $end_date]);
                if (!empty($choose_cost_center_select)) {
                    $join->whereIn('AAT.cost_center_id', $choose_cost_center_select);
                }
            }
        )
            ->select(
                DB::raw($balance_formula),
                'accounting_accounts.account_primary_type'
            )
            ->groupBy('accounting_accounts.account_primary_type')
            ->get();

        $account_types = AccountingAccountTypes::accounting_primary_type();

        $labels = [];
        $values = [];

        foreach ($account_types as $k =>  $v) {
            $value = 0;

            foreach ($tree_of_account_overview as $overview) {
                if ($overview->account_primary_type == $k && !empty($overview->balance)) {
                    $value = (float)$overview->balance;
                }
            }
            $values[] = abs($value);

            //Suffix CR/DR as per value
            $tmp = $v['label'];
            if ($value < 0) {
                $tmp .= (in_array($v['label'], ['Asset', 'Expenses']) ? ' (CR)' : ' (DR)');
            }
            $labels[] = $tmp;
        }

        foreach ($account_types as $k =>  $v) {
            $sub_types = AccountingAccountTypes::where('account_primary_type', $k)
                ->get();

            $balances = AccountingAccount::leftjoin(
                'accounting_accounts_transactions as AAT',
                function ($join) use ($start_date, $end_date, $choose_cost_center_select) {
                    $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                        ->whereBetween('AAT.operation_date', [$start_date, $end_date]);
                    if (!empty($choose_cost_center_select)) {
                        $join->whereIn('AAT.cost_center_id', $choose_cost_center_select);
                    }
                }
            )

                // ->whereDate('AAT.operation_date', '>=', $start_date)
                // ->whereDate('AAT.operation_date', '<=', $end_date)
                ->select(
                    DB::raw($balance_formula),
                    'accounting_accounts.account_sub_type_id'
                )
                ->groupBy('accounting_accounts.account_sub_type_id')
                ->get();

            $labels = [];
            $values = [];
            $total_blance = $balances
                ->pluck('balance')
                ->filter()
                ->sum();

            foreach ($sub_types as $st) {
                $labels[] = $st->account_type_name;
                $value = 0;

                foreach ($balances as $bal) {
                    if ($bal->account_sub_type_id == $st->id && !empty($bal->balance)) {
                        $value = (float)$bal->balance;
                        $account_types[$k]['balance'] = $value;
                    }
                }
                $values[] = $value;
            }
        };


        // إجمالي الأرصدة
        $total_balance = DB::table('accounting_accounts_transactions')
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE -amount END) as balance')
            ->whereBetween('operation_date', [$start_date, $end_date])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('cost_center_id', $choose_cost_center_select);
            })
            ->first()->balance ?? 0;

        // إجمالي المدين والدائن
        $totals = DB::table('accounting_accounts_transactions')
            ->selectRaw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as total_debit')
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as total_credit')
            ->whereBetween('operation_date', [$start_date, $end_date])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('cost_center_id', $choose_cost_center_select);
            })
            ->first();

        $monthlyData = DB::table('accounting_accounts_transactions')
            ->selectRaw('MONTH(operation_date) as month')
            ->selectRaw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as debit')
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as credit')
            ->whereBetween('operation_date', [
                Carbon::parse($start_date)->startOfYear()->format('Y-m-d'),
                Carbon::parse($end_date)->endOfYear()->format('Y-m-d'),
            ])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('cost_center_id', $choose_cost_center_select);
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // أنواع الحركات
        $transaction_types = DB::table('accounting_accounts_transactions')
            ->select('sub_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('operation_date', [$start_date, $end_date])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('cost_center_id', $choose_cost_center_select);
            })
            ->groupBy('sub_type')
            ->get();

        $recent_transactions = DB::table('accounting_accounts_transactions as ta')
            ->join('accounting_accounts as a', 'ta.accounting_account_id', '=', 'a.id')
            ->leftJoin('accounting_cost_centers as c', 'ta.cost_center_id', '=', 'c.id')
            ->leftJoin('accounting_acc_trans_mappings as tm', 'ta.acc_trans_mapping_id', '=', 'tm.id')
            ->leftJoin('transactions as T', 'ta.transaction_id', '=', 'T.id')
            ->select('ta.*', 'a.name_ar as account_name','a.gl_code as gl_code','a.name_en as account_name_en', 'c.name_ar as cost_center_name','c.name_en as cost_center_name_en','tm.ref_no as refNo','T.ref_no as RefNo')
            ->whereBetween('ta.operation_date', [$start_date, $end_date])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('ta.cost_center_id', $choose_cost_center_select);
            })
            ->orderBy('ta.operation_date', 'desc')
            ->take(10)
            ->get();

        $snapshotBalances = AccountingAccount::leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($start_date, $end_date, $choose_cost_center_select) {
            $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                ->whereBetween('AAT.operation_date', [$start_date, $end_date]);
            if (!empty($choose_cost_center_select)) {
                $join->whereIn('AAT.cost_center_id', $choose_cost_center_select);
            }
        })
            ->select(DB::raw($balance_formula), 'accounting_accounts.account_primary_type')
            ->groupBy('accounting_accounts.account_primary_type')
            ->get();

        $assets_total = (float) ($snapshotBalances->firstWhere('account_primary_type', 'asset')->balance ?? 0);
        $liabilities_total = (float) (
            ($snapshotBalances->firstWhere('account_primary_type', 'liability')->balance ?? 0) +
            ($snapshotBalances->firstWhere('account_primary_type', 'liabilities')->balance ?? 0)
        );
        $equity_total = (float) ($snapshotBalances->firstWhere('account_primary_type', 'equity')->balance ?? 0);

        $ageingFilters = [
            'as_of_date' => $end_date,
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
        $receivables_due = collect((new AccountingUtil())->getAgeingReport('sell', 'contact', $ageingFilters))->sum('total_due');
        $payables_due = collect((new AccountingUtil())->getAgeingReport('purchases', 'contact', $ageingFilters))->sum('total_due');

        $unbalanced_journal_entries = DB::table('accounting_acc_trans_mappings as m')
            ->join('accounting_accounts_transactions as t', 't.acc_trans_mapping_id', '=', 'm.id')
            ->where('m.type', 'journal_entry')
            ->whereBetween('m.operation_date', [$start_date, $end_date])
            ->when(!empty($choose_cost_center_select), function ($query) use ($choose_cost_center_select) {
                return $query->whereIn('t.cost_center_id', $choose_cost_center_select);
            })
            ->select('m.id')
            ->groupBy('m.id')
            ->havingRaw('ABS(SUM(CASE WHEN t.type = "debit" THEN t.amount ELSE 0 END) - SUM(CASE WHEN t.type = "credit" THEN t.amount ELSE 0 END)) > 0.005')
            ->get()
            ->count();

        return view('accounting::dashboard.index_pro', compact(
            'tree_of_account_overview',
            'account_types',
            'total_blance',
            'total_balance',
            'totals',
            'monthlyData',
            'transaction_types',
            'recent_transactions',
            'assets_total',
            'liabilities_total',
            'equity_total',
            'receivables_due',
            'payables_due',
            'unbalanced_journal_entries',
            'start_date',
            'end_date',
            'choose_cost_center_select'

        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)

    {
        //
    }
}
