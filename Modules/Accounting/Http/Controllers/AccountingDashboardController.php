<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
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
        $balance_formula = AccountingUtil::balanceFormula();

        $tree_of_account_overview = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
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
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
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
                        $account_types[$k]['balance'] =$value;
                    }
                }
                $values[] = $value;

            }
        };
    
        return view('accounting::dashboard.index', compact(
            'tree_of_account_overview',
            'account_types',
            'total_blance'
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
