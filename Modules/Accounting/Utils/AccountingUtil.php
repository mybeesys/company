<?php

namespace Modules\Accounting\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;

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

    public static function default_accounting_account_types()
    {
        return  $account_sub_types = [
            [
                'name_en' => 'Current Assets',
                'name_ar' => 'الأصول المتداولة',
                'gl_code' => '1.1',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null
            ],
            [
                'name_en' => 'Fixed Assets',
                'name_ar' => 'الأصول الثابتة',
                'gl_code' => '1.2',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null
            ],
            [
                'name_en' => 'Current Liabilities',
                'name_ar' => 'الخصوم المتداولة',
                'gl_code' => '2.1',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null
            ],
            [
                'name_en' => 'Long-Term Liabilities',
                'name_ar' => 'الخصوم طويلة الأجل',
                'gl_code' => '2.2',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null
            ],
            [
                'name_en' => 'Paid-In Capital',
                'name_ar' => 'رأس المال المدفوع',
                'gl_code' => '3.1',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'equity',
                'parent_id' => null
            ],
            [
                'name_en' => 'Retained Earnings',
                'name_ar' => 'الأرباح المحتجزة',
                'gl_code' => '3.2',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'equity',
                'parent_id' => null
            ],
            [
                'name_en' => 'Sales',
                'name_ar' => 'المبيعات',
                'gl_code' => '4.1',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'income',
                'parent_id' => null
            ],
            [
                'name_en' => 'Services',
                'name_ar' => 'الخدمات',
                'gl_code' => '4.2',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'income',
                'parent_id' => null
            ],
            [
                'name_en' => 'Operating Expenses',
                'name_ar' => 'المصاريف التشغيلية',
                'gl_code' => '5.1',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null
            ],
            [
                'name_en' => 'Non-Operating Expenses',
                'name_ar' => 'المصاريف غير التشغيلية',
                'gl_code' => '5.2',
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
                'gl_code' => '1.1.1',
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
                'gl_code' => '1.1.2',
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
                'gl_code' => '1.1.3',
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
                'gl_code' => '1.2.1',
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
                'gl_code' => '1.2.2',
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
                'gl_code' => '2.1.1',
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
                'gl_code' => '2.1.2',
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
                'gl_code' => '2.2.1',
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
                'gl_code' => '2.2.2',
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
                'gl_code' => '5.1.1',
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
                'gl_code' => '5.1.2',
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
                'gl_code' => '5.1.3',
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
                'gl_code' => '5.2.1',
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
                'gl_code' => '5.2.2',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];
    }

    public static function next_GLC($parent_account_id)
    {



        // parent_account_id
        $last_parent_account = AccountingAccount::where([['parent_account_id', '=', $parent_account_id]])->latest()->first();


        if ($last_parent_account) {


            $last_code = $last_parent_account ? substr($last_parent_account->gl_code, -strlen($last_parent_account->gl_code)) : "00";

            $lastDotPosition = strrpos($last_code, '.');


            $numberAfterLastDot = substr($last_code, $lastDotPosition + 1);

            $removedNumberString = substr($last_code, 0, $lastDotPosition);
            $next_code = $removedNumberString . '.' . $numberAfterLastDot + 1;
            return $next_code;
        }

        $parent_account = AccountingAccount::find($parent_account_id);
        $last_code = substr($parent_account->gl_code, -strlen($parent_account->gl_code));


        $next_code = $last_code . '.1';


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

        $AAT = AccountingAccountsTransaction::where('sub_type', $type)->latest()->first()->accTransMapping;
        if ($AAT) {
            $last_ref_no = $AAT->ref_no;

            $currentYear = date('Y');

            list($year, $number) = explode('/', $last_ref_no);

            if ($year == $currentYear) {
                $newNumber = str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                $new_ref_no = $currentYear . '/' . $newNumber;
            } else {
                $new_ref_no = $currentYear . '/0001';
            }

            return $new_ref_no;
        }
        return false;
    }
}
