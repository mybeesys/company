<?php

namespace Modules\Accounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\View\Components\AccountRouting;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Transaction;

class AccountingUtil
{

    public static function balanceFormula(
        $accounting_accounts_alias = 'accounting_accounts',
        $accounting_account_transaction_alias = 'AAT'
    ) {
        return "SUM( IF(
            ($accounting_accounts_alias.account_primary_type='asset' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='liabilities' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='equity' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='income' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='expenses' AND $accounting_account_transaction_alias.type='debit'),
            amount, -1*amount)) as balance";
    }


    // public function saveAccountTransaction($type, $transactionPayment, $transaction)
    // {
    //     if ($transaction->invoice_type == 'cash') {
    //         $account_transaction_data['type'] = 'debit';
    //         $sub_type ='sell_cash';
    //     }
    //     if ($transaction->invoice_type == 'due') {
    //         $account_transaction_data['type'] = 'credit';
    //         $sub_type ='sell_cash';

    //     }

    //     $account_transaction_data = [
    //         'amount' => $transactionPayment->amount,
    //         'accounting_account_id' => $transactionPayment->account_id,
    //         'type' => 'debit',
    //         'sub_type' => $sub_type,
    //         'operation_date' => $transactionPayment->paid_on,
    //         'created_by' => $transactionPayment->created_by,
    //         'transaction_id' => $transactionPayment->transaction_id,
    //         'transaction_payment_id' => $transactionPayment->id,
    //     ];
    //     //If change return then set type as debit
    //     if ($transaction->transaction_type == 'sell' &&  $transactionPayment->is_return == 1) {
    //         $account_transaction_data['type'] = 'debit';
    //     }
    //     if ($transaction->invoice_type == 'cash') {
    //         $account_transaction_data['type'] = 'debit';
    //     }

    //     if ($transaction->transaction_type == 'purchases') {
    //         $account_transaction_data['type'] = 'credit';
    //     }
    //     AccountingAccountsTransaction::create($account_transaction_data);
    //     return true;
    // }

    public function saveAccountTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id = null)
    {
        $sub_type = $transaction->invoice_type == 'cash' ? 'sell_cash' : 'sales_revenue';
        $account_transaction_data = [
            'amount' => $transactionPayment->amount,
            'accounting_account_id' => $transactionPayment->account_id,
            'type' => $transaction->invoice_type == 'cash' ? 'debit' : 'credit',
            'sub_type' => $sub_type,
            'operation_date' => $transactionPayment->paid_on,
            'created_by' => $transactionPayment->created_by,
            'transaction_id' => $transactionPayment->transaction_id,
            'transaction_payment_id' => $transactionPayment->id,
            'acc_trans_mapping_id' => $acc_trans_mapping_id,
        ];

        if ($transaction->transaction_type == 'sell' && $transactionPayment->is_return == 1) {
            $account_transaction_data['type'] = 'debit';
        }

        AccountingAccountsTransaction::create($account_transaction_data);
        return true;
    }

    public function saveAccountRouteTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id = null, $request = null)
    {
        // dd($transactionPayment);
        // $sub_type = $transaction->invoice_type == 'cash' ? 'sell_cash' : 'sales_revenue';
        $account_transaction_data = [
            'amount' => $transactionPayment->amount,
            'accounting_account_id' => $transactionPayment->account_id,
            'type' =>  $type,
            'cost_center_id' => $request->cost_center_id,
            'sub_type' => $transaction->type,
            'operation_date' => $transactionPayment->paid_on,
            'created_by' => $transactionPayment->created_by,
            'transaction_id' => $transactionPayment->transaction_id,
            'transaction_payment_id' => $transactionPayment->id,
            'acc_trans_mapping_id' => $acc_trans_mapping_id,
        ];

        //    dd($account_transaction_data);
        AccountingAccountsTransaction::create($account_transaction_data);
        return true;
    }


    public function accounts_route($transactionPayment, $transaction, $cash_account_id, $due_account_id, $request)
    {

        $route_section = match ($transaction->type) {
            'sell' => 'sales',
            'purchases' => 'purchases',
            default => '',
        };

        $accountsRoute = AccountsRoting::where('section', $route_section)->get();
        // dd($route_section,$accountsRoute);
        if (count($accountsRoute) > 0) {
            $acc_trans_mapping = new AccountingAccTransMapping();

            $ref_number = $this->generateReferenceNumber('journal_entry');
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = '';
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();
            $acc_trans_mapping_id = $acc_trans_mapping->id;
            if ($transaction->type == 'sell') {
                if ($transaction->invoice_type == 'cash') {
                    $transactionPayment->account_id = $cash_account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                } else {
                    $client = Contact::find($transactionPayment->payment_for);
                    if ($client) {
                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    } else {
                        $accountsRoute = AccountsRoting::where('type', 'sales_client')->first();
                        if ($accountsRoute) {
                            if ($accountsRoute->direction == 'auto_assign') {
                                $transactionPayment->account_id = $accountsRoute->account_id;
                                $transactionPayment->amount = $transaction->final_total;
                                $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                            }
                        }
                    }

                    //
                }
            } else if ($transaction->type == 'purchases') {
                if ($transaction->invoice_type == 'cash') {
                    $transactionPayment->account_id = $cash_account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                } else {
                    $client = Contact::find($transactionPayment->payment_for);
                    if ($client) {
                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    } else {
                        $accountsRoute = AccountsRoting::where('type', 'purchases_suppliers')->first();
                        if ($accountsRoute) {
                            if ($accountsRoute->direction == 'auto_assign') {
                                $transactionPayment->account_id = $accountsRoute->account_id;
                                $transactionPayment->amount = $transaction->final_total;
                                $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request, $request);
                            }
                        }
                    }

                    //
                }
            }
            foreach ($accountsRoute as $accountRoute) {
                $transactionPayment->account_id = $accountRoute->account_id;

                [$amount, $type] = $this->determineAmountAndType($accountRoute->type, $transaction);

                $transactionPayment->amount = $amount;
                if ($amount) {
                    $this->saveAccountRouteTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                }
            }
        }


        return true;
    }
    protected function determineAmountAndType($routeType, $transaction)
    {
        return match ($routeType) {
            'sales_sales' => [$transaction->totalAfterDiscount, 'credit'],
            'sales_vat_calculation' => [$transaction->tax_amount, 'credit'],
            'sales_discount_calculation' => [$transaction->discount_amount, 'debit'],

            'purchases_purchases' => [$transaction->totalAfterDiscount, 'debit'],
            'purchases_vat_calculation' => [$transaction->tax_amount, 'debit'],
            'purchases_discount_calculation' => [$transaction->discount_amount, 'credit'],

            default => [0, 'debit'],
        };
    }

    public static function default_accounting_route()
    {
        $vat_acc = AccountingAccount::where('glcode', '522')->first();
        $purchases_acc = AccountingAccount::where('glcode', '513')->first();
        $sales_acc = AccountingAccount::where('glcode', '411')->first();
        $discount_acc = AccountingAccount::where('glcode', '523')->first();

        AccountsRoting::truncate();

        $data = [
            [
                'type' => 'sales_vat_calculation',
                'section' => 'sales',
                'routing_type' => 'liability',
                'account_id' => $vat_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'purchases_purchase',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchases_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'purchases_vat_calculation',
                'section' => 'purchases',
                'routing_type' => 'liability',
                'account_id' => $vat_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'sales_sales',
                'section' => 'sales',
                'routing_type' => 'revenue',
                'account_id' => $sales_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'sales_discount_calculation',
                'section' => 'sales',
                'routing_type' => 'expense',
                'account_id' => $discount_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'purchases_discount_calculation',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $discount_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        AccountsRoting::insert($data);
    }

    public static function default_accounting_account_types()
    {
        return  $account_sub_types = [
            [
                'name_en' => 'Current Assets',
                'name_ar' => 'الأصول المتداولة',
                'gl_code' => '11',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null
            ],
            [
                'name_en' => 'Fixed Assets',
                'name_ar' => 'الأصول الثابتة',
                'gl_code' => '12',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null
            ],
            [
                'name_en' => 'Current Liabilities',
                'name_ar' => 'الخصوم المتداولة',
                'gl_code' => '21',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null
            ],
            [
                'name_en' => 'Long-Term Liabilities',
                'name_ar' => 'الخصوم طويلة الأجل',
                'gl_code' => '22',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null
            ],
            [
                'name_en' => 'Paid-In Capital',
                'name_ar' => 'رأس المال المدفوع',
                'gl_code' => '31',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'equity',
                'parent_id' => null
            ],
            [
                'name_en' => 'Retained Earnings',
                'name_ar' => 'الأرباح المحتجزة',
                'gl_code' => '32',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'equity',
                'parent_id' => null
            ],
            [
                'name_en' => 'Sales',
                'name_ar' => 'المبيعات',
                'gl_code' => '41',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'income',
                'parent_id' => null
            ],
            [
                'name_en' => 'Services',
                'name_ar' => 'الخدمات',
                'gl_code' => '42',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'income',
                'parent_id' => null
            ],
            [
                'name_en' => 'Operating Expenses',
                'name_ar' => 'المصاريف التشغيلية',
                'gl_code' => '51',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null
            ],
            [
                'name_en' => 'Non-Operating Expenses',
                'name_ar' => 'المصاريف غير التشغيلية',
                'gl_code' => '52',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null
            ]
        ];
    }

    public static function Default_Accounts()
    {
        $user_id = Auth::user()->id;

        $current_assets_id = AccountingAccountTypes::where('name_en', 'Current Assets')->first()->id;
        $fixed_assets_id = AccountingAccountTypes::where('name_en', 'Fixed Assets')->first()->id;
        $current_liabilities_id = AccountingAccountTypes::where('name_en', 'Current Liabilities')->first()->id;
        $long_term_liabilities_id = AccountingAccountTypes::where('name_en', 'Long-Term Liabilities')->first()->id;
        $paid_in_capital_id = AccountingAccountTypes::where('name_en', 'Paid-In Capital')->first()->id;
        $sales_id = AccountingAccountTypes::where('name_en', 'Sales')->first()->id;
        $services_id = AccountingAccountTypes::where('name_en', 'Services')->first()->id;
        $operating_expenses_id = AccountingAccountTypes::where('name_en', 'Operating Expenses')->first()->id;
        $non_operating_expenses_id = AccountingAccountTypes::where('name_en', 'Non-Operating Expenses')->first()->id;
        return [
            [
                'name_en' => 'Bank Accounts and Cash on Hand',
                'name_ar' => 'حسابات البنوك والنقد في اليد',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '111',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Accounts Receivable',
                'name_ar' => 'الحسابات المدينة',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '112',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Inventory',
                'name_ar' => 'المخزون',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '113',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Equipment',
                'name_ar' => 'المعدات',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '121',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Buildings',
                'name_ar' => 'المباني',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '122',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Accounts Payable',
                'name_ar' => 'الحسابات الدائنة',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '211',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Short-Term Loans',
                'name_ar' => 'القروض قصيرة الأجل',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '212',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Long-Term Loans',
                'name_ar' => 'القروض طويلة الأجل',
                'account_primary_type' => 'liabilities',
                'account_type' => 'non_current_liabilities',
                'account_sub_type_id' => $long_term_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '221',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Tax Liabilities',
                'name_ar' => 'الالتزامات الضريبية',
                'account_primary_type' => 'liabilities',
                'account_type' => 'non_current_liabilities',
                'account_sub_type_id' => $long_term_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '222',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Salaries',
                'name_ar' => 'الرواتب',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '511',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Rent',
                'name_ar' => 'الإيجار',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '512',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Purchases',
                'name_ar' => 'المشتريات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '513',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Interest Expenses',
                'name_ar' => 'مصاريف الفوائد',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $non_operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '521',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Taxes',
                'name_ar' => 'الضرائب',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $non_operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '522',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Discount',
                'name_ar' => 'الخصم',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $non_operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '523',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Sales Revenue',
                'name_ar' => 'ايراد المبيعات',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                'account_sub_type_id' => $sales_id,
                'detail_type_id' => null,
                'gl_code' => '411',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];
    }

    // public static function next_GLC($parent_account_id)
    // {



    //     // parent_account_id
    //     $last_parent_account = AccountingAccount::where([['parent_account_id', '=', $parent_account_id]])->latest()->first();


    //     if ($last_parent_account) {


    //         $last_code = $last_parent_account ? substr($last_parent_account->gl_code, -strlen($last_parent_account->gl_code)) : "00";

    //         $lastDotPosition = strrpos($last_code, '.');


    //         $numberAfterLastDot = substr($last_code, $lastDotPosition + 1);

    //         $removedNumberString = substr($last_code, 0, $lastDotPosition);
    //         $next_code = $removedNumberString . '.' . $numberAfterLastDot + 1;
    //         return $next_code;
    //     }

    //     $parent_account = AccountingAccount::find($parent_account_id);
    //     $last_code = substr($parent_account->gl_code, -strlen($parent_account->gl_code));


    //     $next_code = $last_code . '.1';


    //     return $next_code;
    // }

    public static function next_GLC($parent_account_id)
    {



        // parent_account_id
        $last_parent_account = AccountingAccount::where([['parent_account_id', '=', $parent_account_id]])->latest()->first();


        if ($last_parent_account) {


            $last_code = $last_parent_account ? substr($last_parent_account->gl_code, -strlen($last_parent_account->gl_code)) : "00";

            $next_code = str_pad((int)$last_code + 1, strlen($last_parent_account->gl_code), "0", STR_PAD_LEFT);
            return $next_code;
        }

        $parent_account = AccountingAccount::find($parent_account_id);
        $last_code = substr($parent_account->gl_code, -strlen($parent_account->gl_code));

        //  $nextNumeric = substr($last_code, -1) + 1;
        $next_code = $last_code . '01';


        return $next_code;
    }

    public static function account_type()
    {
        return [
            'fixed_assets' => __('accounting::lang.account_types.fixed_assets'),
            'current_assets' => __('accounting::lang.account_types.current_assets'),
            'current_liabilities' => __('accounting::lang.account_types.current_liabilities'),
            'non_current_liabilities' => __('accounting::lang.account_types.non_current_liabilities'),
            'equity' => __('accounting::lang.account_types.equity'),
            'income' => __('accounting::lang.account_types.income'),
            'expenses' => __('accounting::lang.account_types.expenses'),
        ];
    }


    public static function account_category()
    {
        return [
            'balance_sheet' => __('accounting::lang.balance_sheet'),
            'income_list' => __('accounting::lang.income_list'),
            'Boxes' => __('accounting::lang.Boxes'),
            'Banks' => __('accounting::lang.Banks'),
            'Cheques' => __('accounting::lang.Cheques'),
            'general' => __('accounting::lang.general'),
            'expenses' => __('accounting::lang.expenses'),
            'Revenues' => __('accounting::lang.Revenues'),
            'Fixed assets' => __('accounting::lang.Fixed assets'),
            'Receivables' => __('accounting::lang.Receivables'),
            'Liabilities' => __('accounting::lang.Liabilities'),
            'taxes' => __('accounting::lang.taxes'),
            'Past due checks' => __('accounting::lang.Past due checks'),
            'Warehouses' => __('accounting::lang.Warehouses'),
            'Revenues received in advance' => __('accounting::lang.Revenues received in advance'),
            'Prepaid expenses' => __('accounting::lang.Prepaid expenses'),

        ];
    }


    public static function generateReferenceNumber($type)
    {

        $AAT = AccountingAccTransMapping::where('type', $type)->latest()->first();
        $currentYear = date('Y');


        if ($AAT) {
            // $AAT =$AAT->accTransMapping;
            $last_ref_no = $AAT->ref_no;


            list($year, $number) = explode('/', $last_ref_no);

            if ($year == $currentYear) {
                $newNumber = str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                $new_ref_no = $currentYear . '/' . $newNumber;
            } else {
                $new_ref_no = $currentYear . '/0001';
            }

            return $new_ref_no;
        }


        return  $new_ref_no = $currentYear . '/0001';
    }


    public function getAgeingReport($type, $group_by)
    {
        $today = Carbon::now()->format('Y-m-d');
        $query = Transaction::query();

        if ($type == 'sell') {
            $query->where('transactions.type', 'sell')
                ->where('transactions.status', 'approved');
        } elseif ($type == 'purchases') {
            $query->where('transactions.type', 'purchases')
                ->where('transactions.status', 'approved');
        }


        $dues = $query->whereIn('transactions.payment_status', ['partial', 'due'])
            ->join('cs_contacts as c', 'c.id', '=', 'transactions.contact_id')
            ->select(
                DB::raw(
                    'DATEDIFF("' . $today . '", transactions.transaction_date) as diff'

                ),
                DB::raw('SUM(transactions.final_total -
                        (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0)
                        FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) )
                        as total_due'),

                'c.name as contact_name',
                'transactions.contact_id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.transaction_date',
                DB::raw('transactions.due_date as due_date')
            )
            ->groupBy([
                'transactions.id',
                'transactions.contact_id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.transaction_date',
                'transactions.due_date',
                'c.name'
            ])
            ->get();

        $report_details = [];
        if ($group_by == 'contact') {
            foreach ($dues as $due) {
                if (!isset($report_details[$due->contact_id])) {
                    $report_details[$due->contact_id] = [
                        'name' => $due->contact_name,
                        '<1' => 0,
                        '1_30' => 0,
                        '31_60' => 0,
                        '61_90' => 0,
                        '>90' => 0,
                        'total_due' => 0,
                    ];
                }

                if ($due->diff < 1) {
                    $report_details[$due->contact_id]['<1'] += $due->total_due;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details[$due->contact_id]['1_30'] += $due->total_due;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details[$due->contact_id]['31_60'] += $due->total_due;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details[$due->contact_id]['61_90'] += $due->total_due;
                } elseif ($due->diff > 90) {
                    $report_details[$due->contact_id]['>90'] += $due->total_due;
                }

                $report_details[$due->contact_id]['total_due'] += $due->total_due;
            }
        } elseif ($group_by == 'due_date') {
            $report_details = [
                'current' => [],
                '1_30' => [],
                '31_60' => [],
                '61_90' => [],
                '>90' => [],
            ];
            foreach ($dues as $due) {
                $temp_array = [
                    'transaction_date' => $due->transaction_date,
                    'due_date' => $due->due_date,
                    'ref_no' => $due->ref_no,
                    'invoice_no' => $due->invoice_no,
                    'contact_name' => $due->contact_name,
                    'due' => $due->total_due,
                ];
                if ($due->diff < 1) {
                    $report_details['current'][] = $temp_array;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details['1_30'][] = $temp_array;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details['31_60'][] = $temp_array;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details['61_90'][] = $temp_array;
                } elseif ($due->diff > 90) {
                    $report_details['>90'][] = $temp_array;
                }
            }
        }

        return $report_details;
    }
}
