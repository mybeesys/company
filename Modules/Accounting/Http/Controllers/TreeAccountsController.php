<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\LedgerExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Utils\AccountingUtil;
use Mpdf\Mpdf;
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

        AccountingUtil::default_accounting_route();
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


        return view('accounting::treeOfAccounts.ledger', compact('account', 'account_transactions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // try {
        DB::beginTransaction();

        $input = $request->only([
            'name_ar',
            'name_en',
            // 'account_category',
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
        return redirect()->back();
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return redirect()->back();
        // }

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
                'gl_code',
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
        $account_id = $request->query('account_id') ?? optional(AccountingAccount::orderBy('id')->first())->id;

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findOrFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping',  'createdBy', 'transaction'])
            // ->leftjoin('transactions as T', 'accounting_accounts_transactions.transaction_id', '=', 'T.id')
            ->where('accounting_account_id', $account->id)->paginate(10);

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
        return view('accounting::treeOfAccounts.ledger', compact('account', 'previous', 'next', 'accountingAccount', 'current_bal', 'account_transactions'));
    }


    public function ledgerPrint(Request $request, $id)
    {

        $account_id = $id;

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
        return view('accounting::treeOfAccounts.print-ledger', compact('account', 'current_bal', 'account_transactions'));
    }


    public function ledgerExportPdf($id)
    {
        $account_id = $id;

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping',  'createdBy'])
            ->where('accounting_account_id', $account->id)->get();

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())]);
        $current_bal = $current_bal->first()->balance;

        $html = view('accounting::treeOfAccounts.print-ledger', compact('account', 'current_bal', 'account_transactions'))->render();


        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        $filename = __('accounting::lang.ledger') . ' ' . (App::getLocale() == 'ar' ? $account->name_ar : $account->name_en) . '- (' . str_replace(['/', '\\'], ' - ', $account->gl_code) . ')' . '.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function ledgerExportExcel($id)
    {
        $account_id = $id;

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping',  'createdBy'])
            ->where('accounting_account_id', $account->id)->get();

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())]);
        $current_bal = $current_bal->first()->balance;

        $account['transactions'] = $account_transactions;
        $account['current_bal'] = $current_bal;

        $filename = __('accounting::lang.ledger') . ' ' . (App::getLocale() == 'ar' ? $account->name_ar : $account->name_en) . '- (' . str_replace(['/', '\\'], ' - ', $account->gl_code) . ')' . '.xlsx';

        return Excel::download(new LedgerExport($account), $filename);
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
    public function accountsDropdown()
    {
        //  AccountingAccount::forDropdown();
        if (request()->ajax()) {
            $q = request()->input('q', '');

            $accounts = AccountingAccount::forDropdown($q);
            $accounts_array = [];
            foreach ($accounts as $account) {
                if (app()->getLocale() == 'ar') {
                    $text = $account->name_ar . ' - <small class="text-muted">' . __('accounting::lang.' . $account->account_primary_type) . '</small>';
                    $html = $account->name_ar . ' - <small class="text-muted">' . __('accounting::lang.' . $account->account_primary_type) . '</small>';
                } else {
                    $text = $account->name_en . ' - <small class="text-muted">' . __('accounting::lang.' . $account->account_primary_type) . '</small>';
                    $html = $account->name_en . ' - <small class="text-muted">' . __('accounting::lang.' . $account->account_primary_type) . '</small>';
                }

                $accounts_array[] = [
                    'id' => $account->id,
                    'text' => $text,
                    'html' => $html,
                ];
            }
        }


        return $accounts_array;
    }
}
