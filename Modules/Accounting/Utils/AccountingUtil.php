<?php

namespace Modules\Accounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\View\Components\AccountRouting;

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

    public function saveAccountTransaction($type, $transactionPayment, $transaction,$acc_trans_mapping_id=null)
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
            'acc_trans_mapping_id'=>$acc_trans_mapping_id,
        ];

        if ($transaction->transaction_type == 'sell' && $transactionPayment->is_return == 1) {
            $account_transaction_data['type'] = 'debit';
        }

        AccountingAccountsTransaction::create($account_transaction_data);
        return true;
    }

    public function saveAccountRouteTransaction($type, $transactionPayment, $transaction,$acc_trans_mapping_id=null)
    {
        $sub_type = $transaction->invoice_type == 'cash' ? 'sell_cash' : 'sales_revenue';
        $account_transaction_data = [
            'amount' => $transactionPayment->amount,
            'accounting_account_id' => $transactionPayment->account_id,
            'type' =>  $type,
            'sub_type' => $sub_type,
            'operation_date' => $transactionPayment->paid_on,
            'created_by' => $transactionPayment->created_by,
            'transaction_id' => $transactionPayment->transaction_id,
            'transaction_payment_id' => $transactionPayment->id,
            'acc_trans_mapping_id'=>$acc_trans_mapping_id,
        ];


        AccountingAccountsTransaction::create($account_transaction_data);
        return true;
    }

    public function accounts_route($transactionPayment, $transaction)
    {
        // dd($transaction);

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
            foreach ($accountsRoute as $accountRoute) {
                $transactionPayment->account_id = $accountRoute->account_id;

                [$amount, $type] = $this->determineAmountAndType($accountRoute->type, $transaction);

                $transactionPayment->amount = $amount;
                $this->saveAccountRouteTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id);
            }
        }


        return true;
    }
    protected function determineAmountAndType($routeType, $transaction)
    {
        return match ($routeType) {
            'sales_sales' => [$transaction->final_total, 'credit'],
            'sales_vat_calculation' => [$transaction->tax_amount, 'debit'],
            'sales_total_amount' => [$transaction->final_total, 'credit'],
            'sales_amount_before_vat' => [$transaction->totalAfterDiscount, 'debit'],
            'sales_discount_calculation' => [$transaction->discount_amount, 'debit'],

            'purchases_purchases' => [$transaction->final_total, 'debit'],
            'purchases_vat_calculation' => [$transaction->tax_amount, 'debit'],
            'purchases_total_amount' => [$transaction->final_total, 'debit'],
            'purchases_amount_before_vat' => [$transaction->totalAfterDiscount, 'debit'],
            'purchases_discount_calculation' => [$transaction->discount_amount, 'credit'],

            default => [0, 'debit'],
        };
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
                'account_sub_type_id' => $non_operating_expenses_id,
                'detail_type_id' => null,
                'gl_code' => '522',
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
            'normal' => __('accounting::lang.normal'),
            'customer_receivables_main_account' => __('accounting::lang.customer_receivables_main_account'),
            'suppliers_receivables_main_account' => __('accounting::lang.suppliers_receivables_main_account'),
            'main_account_employee_receivables' => __('accounting::lang.main_account_employee_receivables'),
            'main_account_requests_approvals' => __('accounting::lang.main_account_requests_approvals'),
            'main_account_other_receivables' => __('accounting::lang.main_account_other_receivables'),
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
}