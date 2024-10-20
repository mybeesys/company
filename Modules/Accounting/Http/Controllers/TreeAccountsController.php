<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Utils\AccountingUtil;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class TreeAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        // return  view('usermanagement::index');
        $account_types = AccountingAccountTypes::accounting_primary_type();
        $balance_formula = AccountingUtil::balanceFormula('AA');

           $accounts = AccountingAccount::whereNull('parent_account_id')
            ->with([
                'child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.detail_type',
                'detail_type',
                'account_sub_type',
                'child_accounts.account_sub_type',
                'child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.child_accounts.child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
            ])
            ->select([
                DB::raw("(SELECT $balance_formula
                                FROM accounting_accounts_transactions AS AAT
                                JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"),
                'accounting_accounts.*'
            ])->get();




        // $accounts = $query->get();
        $account_GLC = [];
        foreach ($account_types as $k => $v) {
            $account_types[$k] = $v['label'];
            $account_GLC[$k] = $v['GLC'];
        }

        $account_sub_types = AccountingAccountTypes::where('account_type', 'sub_type')->get();
        $account_exist = AccountingAccount::exists();
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        return view('accounting::treeOfAccounts.index', compact('accounts', 'account_category', 'account_main_types', 'account_exist', 'account_types', 'account_GLC', 'account_sub_types'));
    }

    public function createDefaultAccounts()
    {
        $default_accounting_account_types = AccountingUtil::default_accounting_account_types();
        $accountingAccountType = AccountingAccountTypes::all();
        if (count($accountingAccountType) == 0) {
            AccountingAccountTypes::insert($default_accounting_account_types);
        }

        $default_accounts = AccountingUtil::Default_Accounts();


        if (AccountingAccount::doesntExist()) {
            AccountingAccount::insert($default_accounts);
        }

        //redirect back
        $output = [
            'success' => 1,
            'msg' => __('lang_v1.added_success')
        ];
        return redirect()->back()->with('status', $output);;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($account_id)
    {
        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping', 'transaction', 'createdBy'])
            ->where('accounting_account_id', $account->id)->get();


        // ->select(
        //     'operation_date',
        //     'sub_type',
        //     'type',
        //     'amount',
        //     // DB::raw("CONCAT(COALESCE(users.first_name, ''),' ',COALESCE(users.last_name,'')) as added_by"),
        //     'users.name',
        //     // 'transactions.invoice_no',
        //     'accTransMapping.ref_no',
        //     'accTransMapping.note'
        // )
        // ->get();



        // if (!empty($start_date) && !empty($end_date)) {
        //     $transactions->where(function ($query) use ($start_date, $end_date) {
        //         $query->where(function ($query) use ($start_date, $end_date) {
        //             $query->where('accounting_accounts_transactions.sub_type', '!=', 'opening_balance')
        //                 ->whereDate('accounting_accounts_transactions.operation_date', '>=', $start_date)
        //                 ->whereDate('accounting_accounts_transactions.operation_date', '<=', $end_date);
        //         })
        //             ->orWhere(function ($query) use ($start_date, $end_date) {
        //                 $query->where('accounting_accounts_transactions.sub_type', 'opening_balance')
        //                     ->whereYear('accounting_accounts_transactions.operation_date', '>=', date('Y', strtotime($start_date)))
        //                     ->whereYear('accounting_accounts_transactions.operation_date', '<=', date('Y', strtotime($end_date)));
        //             });
        //     });
        // }

        // return  $current_bal = AccountingAccount::leftjoin(
        //     'accounting_accounts_transactions as AAT',
        //     'AAT.accounting_account_id',
        //     '=',
        //     'accounting_accounts.id'
        // )

        //     ->where('accounting_accounts.id', $account->id);
        // ->select([DB::raw($this->accountingUtil->balanceFormula())]);
        // $current_bal = $current_bal->first()->balance;

        return view('accounting::treeOfAccounts.ledger', compact('account', 'account_transactions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $input = $request->only([
                'name_ar',
                'name_en',
                'account_category',
                'account_id',
                'account_type'
            ]);



            $account_account = AccountingAccount::find($input['account_id']);


            $input['account_primary_type'] = $account_account->account_primary_type;
            $input['account_sub_type_id'] = $account_account->account_sub_type_id;
            $input['detail_type_id'] = $account_account->detail_type_id;
            $input['parent_account_id'] = $input['account_id'];
            $input['created_by'] = Auth::user()->id;

            $input['status'] = 'active';
            $input['gl_code'] = AccountingUtil::next_GLC($input['account_id']);
            $account_type = AccountingAccountTypes::find($input['account_sub_type_id'] ?? $input['account_id']);
            $account = AccountingAccount::create($input);
            // return $input;
            // if ($account_type->show_balance == 1 && !empty($request->input('balance'))) {
            //     //Opening balance
            //     $data = [
            //         'amount' => $this->accountingUtil->num_uf($request->input('balance')),
            //         'accounting_account_id' => $account->id,
            //         'created_by' => auth()->user()->id,
            //         'operation_date' => !empty($request->input('balance_as_of')) ?
            //             $this->accountingUtil->uf_date($request->input('balance_as_of')) :
            //             \Carbon::today()->format('Y-m-d')
            //     ];

            //     //Opening balance
            //     $data['type'] = in_array($input['account_primary_type'], ['asset', 'expenses']) ? 'debit' : 'credit';
            //     $data['sub_type'] = 'opening_balance';
            //     $trans = AccountingAccountsTransaction::query()->create($data);
            //     $opBalance = [
            //         'accounts_account_transaction_id' => $trans->id,
            //         'type' => $data['type'] == 'debit' ? 'debit' : 'credit',
            //         'business_id' => $business_id,
            //         'company_id' => $company_id,

            //         'year' => Carbon::today()->format('Y')
            //     ];
            //     OpeningBalance::query()->create($opBalance);
            // }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back();
        }

        return redirect()->back();
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
    public function update(Request $request)
    {

        try {
            DB::beginTransaction();
            $data = $request->only([
                'name_ar',
                'name_en',
                'account_category',
                'account_type'
            ]);

            $account = AccountingAccount::find($request->account_id);
            $account->update($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back();
        }

        return redirect()->back();
    }


    public function ledger(Request $request)
    {

        $account_id = $request->query('account_id');

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping',  'createdBy'])
            ->where('accounting_account_id', $account->id)->get();

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )

            ->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())]);
        $current_bal = $current_bal->first()->balance;
        $previous = AccountingAccount::where('id', '<', $account_id)->orderBy('id', 'desc')->first();

        $next = AccountingAccount::where('id', '>', $account_id)->orderBy('id', 'asc')->first();

        $accountingAccount = AccountingAccount::forDropdown();
        return view('accounting::treeOfAccounts.ledger', compact('account','previous','next','accountingAccount', 'current_bal', 'account_transactions'));
    }

    public function activateDeactivate(Request $request)
    {
        $account = AccountingAccount::find($request->account_id);

        $account->status = $account->status == 'active' ? 'inactive' : 'active';
        $account->save();

        return redirect()->back();
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}