<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Tax;
use Modules\General\Models\TransactionPayments;
use Yajra\DataTables\Facades\DataTables;

class AccountingReportsController extends Controller
{

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

        $tax = Tax::first()->amount ?? 0;
        $tax_amount = ($tax * $income_before_tax) / 100;

        return response()->json([
            'gross_profit' => $gross_profit,
            'operation_income' => $operation_income,
            'income_before_tax' => $income_before_tax,
            'tax_amount' => $tax_amount,
            'revenue_net' => $revenue_net,
            'cost_of_revenue' => $cost_of_revenue,
            'total_expense' => $total_expense,
            'total_other_income' => $total_other_income,
            'total_other_expense' => $total_other_expense
        ]);
    }

    public function incomeStatement()
    {
        $start_date = request()->start_date ?? now()->startOfYear()->format('Y-m-d');
        $end_date = request()->end_date ?? now()->addDay(1)->format('Y-m-d');

        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());


        $accounts = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->whereBetween('AAT.operation_date', [$start_date, $end_date])
            ->whereIn('accounting_accounts.account_type', ['income', 'expenses'])
            // $qu->whereIn('accounting_accounts.account_type',['income','expenses']);
            // ->orWhere('accounting_accounts.account_type', '=', 'expenses');
            // })
            ->select(
                DB::raw("SUM(IF(AAT.type = 'credit' , AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit' , AAT.amount, 0)) as debit_balance"),
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type as acc_type'
            )
            ->groupBy(
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type'
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get();

        $data = $this->getIcomeStatementData($accounts);

        return view('accounting::reports.income-statement')
            ->with(compact(
                'accounts',
                'start_date',
                'end_date',
                'data',
                'company'
            ));
    }

    // public function getIcomeStatementData($accounts)
    // {
    //     $total_balances = [];
    //     $revenue_net = 0;
    //     $cost_of_revenue = 0;
    //     $total_expense = 0;
    //     $total_other_income = 0;
    //     $total_other_expense = 0;

    //     foreach ($accounts as $account) {
    //         if (str_starts_with($account->gl_code, '4') || str_starts_with($account->gl_code, '5')) {
    //             $debit_balance = $account->debit_balance;
    //             $credit_balance = $account->credit_balance;

    //             $balance = $credit_balance - $debit_balance;

    //             $total_balances[$account->gl_code] = $balance;
    //         }
    //     }

    //     foreach ($total_balances as $key => $total_balance) {
    //         if (str_starts_with($key, '4')) {
    //             $revenue_net += $total_balance;
    //         } elseif (str_starts_with($key, '51')) {
    //             $cost_of_revenue += abs($total_balance);
    //         } elseif (str_starts_with($key, '52')) {
    //             $total_expense += abs($total_balance);
    //         }
    //     }

    //     $gross_profit = $revenue_net - $cost_of_revenue;
    //     $operation_income = $gross_profit - $total_expense;
    //     $income_before_tax = $operation_income + $total_other_income - $total_other_expense;

    //     $tax = Tax::first()->amount ?? 0;
    //     $tax_amount = ($tax * $income_before_tax) / 100;

    //     return response()->json([
    //         'gross_profit' => $gross_profit,
    //         'operation_income' => $operation_income,
    //         'income_before_tax' => $income_before_tax,
    //         'tax_amount' => $tax_amount,
    //         'revenue_net' => $revenue_net,
    //         'cost_of_revenue' => $cost_of_revenue,
    //         'total_expense' => $total_expense,
    //         'total_other_income' => $total_other_income,
    //         'total_other_expense' => $total_other_expense
    //     ]);
    // }



    public function trialBalance(Request $request)
    {
        // try {

        $account_types = AccountingAccountTypes::accounting_primary_type();
        $accounts_array = [];
        foreach ($account_types as $key => $account_type) {
            $accounts_array[$key] =
                $account_type['label'];
        }


        $with_zero_balances = $request->input('with_zero_balances', 0);

        $aggregated = $request->input('aggregated', 0);

        $choose_accounts_select = $request->input('choose_accounts_select');

        $level_filter = $request->input('level_filter');

        $max_levels = AccountingAccount::pluck('gl_code')->toArray();

        $lengths = array_map(function ($length) {
            return str_replace(".", "", $length);
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

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');
        } else {
            $start_date = now();
            $end_date = now();
        }

        if ($with_zero_balances == 0) {
            // Fetch accounts that have non-zero balances
            $accounts = AccountingAccount::join(
                'accounting_accounts_transactions as AAT',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
                ->where(function ($query) use ($start_date, $end_date) {
                    $query->where(function ($query) use ($start_date, $end_date) {
                        $query->whereDate('AAT.operation_date', '>=', $start_date)
                            ->whereDate('AAT.operation_date', '<=', $end_date);
                    })
                        ->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                                ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                        });
                });
        } elseif ($with_zero_balances == 1) {
            // Fetch all accounts, including zero balance ones
            $accounts = AccountingAccount::leftJoin(
                'accounting_accounts_transactions as AAT',
                function ($join) use ($start_date, $end_date) {
                    $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                        ->where(function ($query) use ($start_date, $end_date) {
                            $query->whereBetween('AAT.operation_date', [$start_date, $end_date]);
                        })
                        ->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                                ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                        });
                }
            );
        } elseif ($with_zero_balances == 2) {
            // Fetch only accounts with zero balances
            $accounts = AccountingAccount::leftJoin(
                'accounting_accounts_transactions as AAT',
                function ($join) use ($start_date, $end_date) {
                    $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                        ->where(function ($query) use ($start_date, $end_date) {
                            $query->whereBetween('AAT.operation_date', [$start_date, $end_date]);
                        })
                        ->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                                ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                        });
                }
            )
                ->havingRaw("SUM(IF(AAT.type = 'credit', AAT.amount, 0)) = 0")
                ->havingRaw("SUM(IF(AAT.type = 'debit', AAT.amount, 0)) = 0")
                ->havingRaw("IFNULL((SELECT AAT.amount FROM accounting_accounts_transactions as AAT
                    WHERE AAT.accounting_account_id = accounting_accounts.id
                    AND AAT.type = 'credit'
                    ORDER BY AAT.operation_date ASC
                    LIMIT 1), 0) = 0")
                ->havingRaw("IFNULL((SELECT AAT.amount FROM accounting_accounts_transactions as AAT
                    WHERE AAT.accounting_account_id = accounting_accounts.id
                    AND AAT.type = 'debit'
                    ORDER BY AAT.operation_date ASC LIMIT 1), 0) = 0");
        }


        $accounts->when($choose_accounts_select, function ($query, $choose_accounts_select) {
            return $query->where(function ($query) use ($choose_accounts_select) {
                foreach ($choose_accounts_select as $type) {
                    $query->orWhere('accounting_accounts.account_primary_type', 'like', $type . '%');
                }
            });
        })
            ->when($level_filter, function ($query, $level_filter) {

                return $query
                    ->whereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) = ?', [$level_filter - 1])
                    ->orwhereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) < ?', [$level_filter - 1]);
            })
            ->select(
                DB::raw("IF($aggregated = 1, accounting_accounts.account_primary_type, accounting_accounts.name_ar) as name"),
                DB::raw("SUM(IF(AAT.type = 'credit', AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit' , AAT.amount, 0)) as debit_balance"),
                DB::raw("(SELECT AAT_IN.amount
                FROM accounting_accounts_transactions as AAT_IN
                WHERE AAT_IN.accounting_account_id = accounting_accounts.id
                AND AAT_IN.type = 'debit'
                ORDER BY AAT_IN.operation_date ASC LIMIT 1) as debit_opening_balance"),
                DB::raw("(SELECT AAT_IN.amount
                FROM accounting_accounts_transactions as AAT_IN
                WHERE AAT_IN.accounting_account_id = accounting_accounts.id
                AND AAT_IN.type = 'credit'
                ORDER BY AAT_IN.operation_date ASC LIMIT 1) as credit_opening_balance"),
                'AAT.sub_type as sub_type',
                'AAT.type as type',
                'accounting_accounts.gl_code',
                'accounting_accounts.id'
            )
            /* ->when($level_filter, function ($query, $level_filter) {
                return $query->havingRaw('code_length <= ?', [$level_filter - 1]);
                }) */
            ->groupBy(
                'name',
                'AAT.sub_type',
                'AAT.type',
                'accounting_accounts.gl_code',
                'accounting_accounts.id'
            )->orderBy('accounting_accounts.gl_code');

        // return $accounts->get();
        if ($aggregated) {
            $aggregatedAccounts = [];
            foreach ($accounts->get() as $account) {

                $groupKey = $account->name;
                if (!isset($aggregatedAccounts[$groupKey])) {
                    $aggregatedAccounts[$groupKey] = (object) [
                        'name' => Lang::has('accounting::lang.' . $groupKey) ? __('accounting::lang.' . $groupKey) : $groupKey,
                        'gl_code' => $account->gl_code[0],
                        'credit_balance' => 0,
                        'debit_balance' => 0,
                        'credit_opening_balance' => 0,
                        'debit_opening_balance' => 0,
                    ];
                }
                $aggregatedAccounts[$groupKey]->credit_balance += $account->credit_balance;
                $aggregatedAccounts[$groupKey]->debit_balance += $account->debit_balance;
                $aggregatedAccounts[$groupKey]->credit_opening_balance += $account->credit_opening_balance;
                $aggregatedAccounts[$groupKey]->debit_opening_balance += $account->debit_opening_balance;
            }
            $accounts = $aggregatedAccounts;
        }

        if (request()->ajax()) {
            $totalDebitOpeningBalance = 0;
            $totalCreditOpeningBalance = 0;
            $totalClosingDebitBalance = 0;
            $totalClosingCreditBalance = 0;
            $totalDebitBalance = 0;
            $totalCreditBalance = 0;

            foreach ($aggregated ? $accounts : $accounts->get() as $account) {
                $totalDebitBalance += $account->debit_balance;
                $totalCreditBalance += $account->credit_balance;
                $totalDebitOpeningBalance += $account->debit_opening_balance;
                $totalCreditOpeningBalance += $account->credit_opening_balance;


                $closing_balance = $this->calculateClosingBalance($account);
                $totalClosingDebitBalance += $closing_balance['closing_debit_balance'];
                $totalClosingCreditBalance += $closing_balance['closing_credit_balance'];
            }

            return DataTables::of($accounts)
                ->editColumn('gl_code', function ($account) {
                    return $account->gl_code;
                })
                ->editColumn('name', function ($account) {
                    return $account->name;
                })
                ->editColumn('debit_balance', function ($account) {
                    return $account->debit_balance ?? 0;
                })
                ->editColumn('debit_opening_balance', function ($account) {
                    return $account->debit_opening_balance ?? 0;
                })
                ->editColumn('credit_opening_balance', function ($account) {
                    return $account->credit_opening_balance ?? 0;
                })

                ->editColumn('credit_balance', function ($account) {
                    return $account->credit_balance ?? 0;
                })
                ->addColumn('closing_debit_balance', function ($account) {
                    $closing_balance = $this->calculateClosingBalance($account);
                    return $closing_balance['closing_debit_balance'] ?? 0;
                })
                ->addColumn('closing_credit_balance', function ($account) {
                    $closing_balance = $this->calculateClosingBalance($account);
                    return $closing_balance['closing_credit_balance'] ?? 0;
                })
                ->addColumn('action', function ($account) use ($aggregated) {
                    if (!$aggregated) {
                        return '<div class="btn-group">
                                <button type="button" class="btn btn-info btn-xs" >' . '
                                    <a class=" btn-modal text-white" data-container="#printledger"
                                        href="' . action('\Modules\Accounting\Http\Controllers\TreeAccountsController@ledgerPrint', [$account->id]) . '"
                                    >
                                        ' . __("accounting::lang.account_statement") . '
                                    </a>
                                </button>
                            </div>';
                    }
                    return '';
                })
                ->with([
                    'totalDebitOpeningBalance' => $totalDebitOpeningBalance,
                    'totalCreditOpeningBalance' => $totalCreditOpeningBalance,
                    'totalDebitBalance' => $totalDebitBalance,
                    'totalCreditBalance' => $totalCreditBalance,
                    'totalClosingDebitBalance' => $totalClosingDebitBalance,
                    'totalClosingCreditBalance' => $totalClosingCreditBalance,
                ])
                ->rawColumns(['action', 'closing_credit_balance', 'closing_debit_balance', 'credit_balance', 'debit_balance', 'name', 'gl_code'])

                ->make(true);
        }

        return view('accounting::reports.trial_balance')
            ->with(compact('levelsArray', 'accounts_array'));
        // } catch (\Exception $e) {
        //     // Log::error('Error in trialBalance method: ' . $e->getMessage());
        //     return redirect()->route('tree-of-accounts')
        //         ->with('message', 'Please create a tree account for the chart of accounts.');
        // }
    }


    private function calculateClosingBalance($account)
    {
        $closing_debit_balance = $account->debit_opening_balance + $account->debit_balance;
        $closing_credit_balance = $account->credit_opening_balance + $account->credit_balance;
        $closing_balance = $closing_credit_balance - $closing_debit_balance;

        return [
            'closing_debit_balance' => $closing_balance < 0 ? abs($closing_balance) : 0,
            'closing_credit_balance' => $closing_balance >= 0 ? $closing_balance : 0,
        ];
    }

    public function balanceSheet()
    {
        $accountingUtil = new AccountingUtil();

        $start_date = request()->start_date ?? now()->startOfYear()->format('Y-m-d');
        $end_date = request()->end_date ?? now()->addDay(1)->format('Y-m-d');

        $balance_formula = $accountingUtil->balanceFormula();

        $assets = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name_ar', 'AATP.name_ar as sub_type')

            ->whereIn('accounting_accounts.account_primary_type', ['asset'])
            ->groupBy('accounting_accounts.name_ar', 'AATP.name_ar')
            ->get();

        $liabilities = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name_ar', 'AATP.name_ar as sub_type')

            ->whereIn('accounting_accounts.account_primary_type', ['liability'])
            ->groupBy('accounting_accounts.name_ar', 'AATP.name_ar')
            ->get();

        $equities = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name_ar', 'AATP.name_ar as sub_type')

            ->whereIn('accounting_accounts.account_primary_type', ['equity'])
            ->groupBy('accounting_accounts.name_ar', 'AATP.name_ar')
            ->get();

        return view('accounting::reports.balance_sheet')
            ->with(compact('assets', 'liabilities', 'equities', 'start_date', 'end_date'));
    }


    public function JournalReport(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $journals = AccountingAccTransMapping::where('type', 'journal_entry')
            ->when($startDate, fn($query) => $query->where('operation_date', '>=', $startDate))
            ->when($endDate, fn($query) => $query->where('operation_date', '<=', $endDate))
            ->with(['transactions' => function ($query) {
                $query->join('accounting_accounts', 'accounting_accounts.id', '=', 'accounting_accounts_transactions.accounting_account_id')
                    ->select('accounting_accounts_transactions.*', 'accounting_accounts.name_ar', 'accounting_accounts.name_en', 'accounting_accounts.gl_code');
            }])
            ->get();

        return view('accounting::reports.journal_report', compact('journals', 'startDate', 'endDate'));
    }


    public function cash_flow(Request $request)
    {

        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        $operatingCashFlows = AccountingAccountsTransaction::whereIn('sub_type', ['sell', 'sell_cash', 'purchases', 'sales_revenue', 'receipt_voucher'])
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->paginate(10);

        $cashInflows = TransactionPayments::where('method', 'cash')
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->sum('amount');

        $cashOutflows = TransactionPayments::where('method', 'cash')
            ->whereBetween('paid_on', [$startDate, $endDate])
            ->where('is_return', 1)
            ->sum('amount');

        $netCashFlow = $cashInflows - $cashOutflows;


        return view('accounting::reports.cash_flow', compact('operatingCashFlows', 'cashInflows', 'cashOutflows', 'netCashFlow', 'startDate', 'endDate')); // compact('operatingCashFlows', 'investingCashFlows', 'financingCashFlows', 'startDate', 'endDate'));
    }


    public function customersSuppliersStatement(Request $request)
    {

        $accountingUtil = new AccountingUtil;

        $contact_id = request()->query('id') ?? Contact::pluck('id')->first();
        $contact = Contact::with(['transactions'])
            ->findOrFail($contact_id);


        $start_date = request()->start_date;
        $end_date =  request()->end_date;

        if ($request->ajax()) {

            $contacts = Contact::where('cs_contacts.id', $contact_id)
                ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
                ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
                ->leftJoin('accounting_acc_trans_mappings as atm', 'aat.acc_trans_mapping_id', '=', 'atm.id')
                ->leftJoin('emp_employees as u', 'aat.created_by', '=', 'u.id')
                ->leftJoin('accounting_cost_centers as cc', 'aat.cost_center_id', '=', 'cc.id')
                ->select(
                    'aat.operation_date',
                    'aat.sub_type',
                    'aat.type',
                    'atm.ref_no',
                    'atm.id as atm_id',
                    'cc.name_ar as cost_center_name',
                    'atm.note',
                    'aat.amount',
                    'u.name as added_by',
                    't.ref_no as invoice_no',
                )
                ->whereDate('aat.operation_date', '>=', $start_date)
                ->whereDate('aat.operation_date', '<=', $end_date)
                ->groupBy(
                    'cs_contacts.id',
                    'aat.operation_date',
                    'aat.sub_type',
                    'aat.type',
                    'atm_id',
                    'atm.ref_no',
                    'cc.name_ar',
                    'atm.note',
                    'aat.amount',
                    't.ref_no',
                    'u.name',
                );


            return DataTables::of($contacts)
                ->editColumn('operation_date', function ($row) {
                    return $row->operation_date;
                })
                ->editColumn('ref_no', function ($row) {
                    $description = '';
                    if ($row->sub_type == 'journal_entry') {
                        $description =  $row->accTransMapping->ref_no;
                    }

                    if ($row->sub_type == 'payment_voucher' || $row->sub_type == 'receipt_voucher') {
                        $description =  $row?->transactionPayments?->payment_ref_no;
                    }

                    if ($row->sub_type == 'sell' || $row->sub_type == 'purchases') {
                        $description = $row->invoice_no;
                    }
                    if ($row->atm_id) {
                        $description = '<a class=" btn-modal"
                      data-container="#printJournalEntry"
                        href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@print', [$row->atm_id]) . '"
                         >
                            ' . $description . '
                        </a>';
                    }
                    return $description;
                })
                ->addColumn('transaction', function ($row) {
                    if (Lang::has('accounting::lang.' . $row->sub_type)) {

                        $description = __('accounting::lang.' . $row->sub_type);
                    } else {
                        $description = $row->sub_type;
                    }
                    return $description;
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return '<span class="debit" data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                    }
                    return '';
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return '<span class="credit"  data-orig-value="' . $row->amount . '">' . $row->amount . '</span>';
                    }
                    return '';
                })
                // ->filterColumn('cost_center_name', function ($query, $keyword) {
                //     $query->whereRaw("LOWER(cc.ar_name) LIKE ?", ["%{$keyword}%"]);
                // })
                // ->filterColumn('added_by', function ($query, $keyword) {
                //     $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                // })
                ->rawColumns(['ref_no', 'credit', 'debit', 'balance', 'action'])
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



        return view('accounting::reports.customers-suppliers-statement')
            ->with(compact('contact', 'contact_dropdown', 'current_bal', 'contact_id', 'total_debit_bal', 'total_credit_bal'));
    }


    public function accountReceivableAgeingReport()
    {
        $accountingUtil = new AccountingUtil;

        $report_details = $accountingUtil->getAgeingReport('sell', 'contact');


        return view('accounting::reports.account_receivable_ageing_report')
            ->with(compact('report_details'));
    }




    public function accountPayableAgeingReport()
    {
        $accountingUtil = new AccountingUtil;

        $report_details = $accountingUtil->getAgeingReport(
            'purchase',
            'contact',
        );

        return view('accounting::reports.account_payable_ageing_report')
            ->with(compact('report_details'));
    }


    public function accountReceivableAgeingDetails()
    {
        $accountingUtil = new AccountingUtil;

        $report_details = $accountingUtil->getAgeingReport(
            'sell',
            'due_date'
        );


        return view('accounting::reports.account_receivable_ageing_details')
            ->with(compact('report_details'));
    }

    public function accountPayableAgeingDetails()
    {
        $accountingUtil = new AccountingUtil;

        $report_details = $accountingUtil->getAgeingReport('purchases', 'due_date');


        return view('accounting::reports.account_payable_ageing_details')
            ->with(compact('report_details'));
    }
}
