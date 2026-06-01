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
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use RuntimeException;

class AccountingUtil
{
    public static function balanceFormula(
        $accounting_accounts_alias = 'accounting_accounts',
        $accounting_account_transaction_alias = 'AAT'
    ) {
        return "SUM( IF(
            ($accounting_accounts_alias.account_primary_type='asset' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type IN ('liability','liabilities') AND $accounting_account_transaction_alias.type='debit')
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
            'type' => $type,
            'cost_center_id' => $request->cost_center_id ?? null,
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

    // public function accounts_route($transactionPayment, $transaction, $cash_account_id, $due_account_id, $request)
    // {

    //     $route_section = match ($transaction->type) {
    //         'sell' => 'sales',
    //         'purchases' => 'purchases',
    //         default => '',
    //     };

    //     $accountsRoute = AccountsRoting::where('section', $route_section)->get();
    //     // dd($route_section,$accountsRoute);
    //     if (count($accountsRoute) > 0) {
    //         $acc_trans_mapping = new AccountingAccTransMapping();

    //         $ref_number = $this->generateReferenceNumber('journal_entry');
    //         $acc_trans_mapping->ref_no = $ref_number;
    //         $acc_trans_mapping->note = '';
    //         $acc_trans_mapping->type = 'journal_entry';
    //         $acc_trans_mapping->created_by = Auth::user()->id;
    //         $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
    //         $acc_trans_mapping->save();
    //         $acc_trans_mapping_id = $acc_trans_mapping->id;
    //         if ($transaction->type == 'sell') {
    //             if ($transaction->invoice_type == 'cash') {
    //                 $transactionPayment->account_id = $cash_account_id;
    //                 $transactionPayment->amount = $transaction->final_total;
    //                 $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //             } else {
    //                 $client = Contact::find($transactionPayment->payment_for);
    //                 if ($client) {
    //                     $transactionPayment->account_id = $client->account_id;
    //                     $transactionPayment->amount = $transaction->final_total;
    //                     $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //                 } else {
    //                     $accountsRoute = AccountsRoting::where('type', 'sales_client')->first();
    //                     if ($accountsRoute) {
    //                         if ($accountsRoute->direction == 'auto_assign') {
    //                             $transactionPayment->account_id = $accountsRoute->account_id;
    //                             $transactionPayment->amount = $transaction->final_total;
    //                             $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //                         }
    //                     }
    //                 }

    //                 //
    //             }
    //         } else if ($transaction->type == 'purchases') {
    //             if ($transaction->invoice_type == 'cash') {
    //                 $transactionPayment->account_id = $cash_account_id;
    //                 $transactionPayment->amount = $transaction->final_total;
    //                 $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //             } else {
    //                 $client = Contact::find($transactionPayment->payment_for);
    //                 if ($client) {
    //                     $transactionPayment->account_id = $client->account_id;
    //                     $transactionPayment->amount = $transaction->final_total;
    //                     $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //                 } else {
    //                     $accountsRoute = AccountsRoting::where('type', 'purchases_suppliers')->first();
    //                     if ($accountsRoute) {
    //                         if ($accountsRoute->direction == 'auto_assign') {
    //                             $transactionPayment->account_id = $accountsRoute->account_id;
    //                             $transactionPayment->amount = $transaction->final_total;
    //                             $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request, $request);
    //                         }
    //                     }
    //                 }

    //                 //
    //             }
    //         }
    //         foreach ($accountsRoute as $accountRoute) {
    //             $transactionPayment->account_id = $accountRoute->account_id;

    //             [$amount, $type] = $this->determineAmountAndType($accountRoute->type, $transaction);

    //             $transactionPayment->amount = $amount;
    //             if ($amount) {
    //                 $this->saveAccountRouteTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
    //             }
    //         }
    //     }

    //     return true;
    // }
    // protected function determineAmountAndType($routeType, $transaction)
    // {
    //     return match ($routeType) {
    //         'sales_sales' => [$transaction->totalAfterDiscount, 'credit'],
    //         'sales_vat_calculation' => [$transaction->tax_amount, 'credit'],
    //         'sales_discount_calculation' => [$transaction->discount_amount, 'debit'],

    //         'purchases_purchases' => [$transaction->totalAfterDiscount, 'debit'],
    //         'purchases_vat_calculation' => [$transaction->tax_amount, 'debit'],
    //         'purchases_discount_calculation' => [$transaction->discount_amount, 'credit'],

    //         default => [0, 'debit'],
    //     };
    // }

    public function accounts_route($transactionPayment, $transaction, $cash_account_id, $due_account_id, $request)
    {
        $netTotalBeforeTax = (float) ($transaction->totalAfterDiscount ?? $transaction->total_after_discount ?? $transaction->total_before_tax ?? 0);

        $route_section = match ($transaction->type) {
            'sell' => 'sales',
            'purchases' => 'purchases',
            'sell-return' => 'sales',
            'purchases-return' => 'purchases',
            default => '',
        };

        $accountsRoute = AccountsRoting::where('section', $route_section)->get();

        if (count($accountsRoute) > 0) {
            $acc_trans_mapping = new AccountingAccTransMapping;
            $ref_number = $this->generateReferenceNumber('journal_entry');
            $acc_trans_mapping->ref_no = $ref_number;
            $sourceTypeAr = match ($transaction->type) {
                'sell' => 'مبيعات',
                'purchases' => 'مشتريات',
                'sell-return' => 'مردود مبيعات',
                'purchases-return' => 'مردود مشتريات',
                default => $transaction->type,
            };
            $acc_trans_mapping->note = $sourceTypeAr;
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = $transaction->created_by;
            $acc_trans_mapping->is_manual = 0;
            $acc_trans_mapping->operation_date = Carbon::parse($transaction->transaction_date ?? now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();
            $acc_trans_mapping_id = $acc_trans_mapping->id;

            if ($transaction->type == 'sell') {
                $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
                $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();
                $sales_discount_allowed = AccountsRoting::where('type', 'sales_discount_allowed')->first();
                $discountAmount = (float) ($transaction->discount_amount ?? 0);
                $salesGrossBeforeDiscount = $netTotalBeforeTax + max(0, $discountAmount);

                if ($transaction->invoice_type == 'cash') {

                    if (! $transactionPayment->payment_for) {
                        $transactionPayment->account_id = $cash_account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        $transactionPayment->account_id = $sales_sales->account_id;
                        $transactionPayment->amount = $salesGrossBeforeDiscount;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        if ($discountAmount > 0 && $sales_discount_allowed && $sales_discount_allowed->account_id) {
                            $transactionPayment->account_id = $sales_discount_allowed->account_id;
                            $transactionPayment->amount = $discountAmount;
                            $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        }
                        $transactionPayment->account_id = $sales_vat_calculation->account_id;
                        $transactionPayment->amount = $transaction->tax_amount;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    } else {
                        $client = Contact::find($transactionPayment->payment_for);
                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $sales_sales->account_id;
                        $transactionPayment->amount = $salesGrossBeforeDiscount;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        if ($discountAmount > 0 && $sales_discount_allowed && $sales_discount_allowed->account_id) {
                            $transactionPayment->account_id = $sales_discount_allowed->account_id;
                            $transactionPayment->amount = $discountAmount;
                            $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        }
                        $transactionPayment->account_id = $sales_vat_calculation->account_id;
                        $transactionPayment->amount = $transaction->tax_amount;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $cash_account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    }
                } elseif ($transaction->invoice_type == 'due') {
                    if (! $sales_sales?->account_id || ! $sales_vat_calculation?->account_id) {
                        throw new RuntimeException('Accounting routing missing for sell (due). Please configure sales_sales and sales_vat_calculation in Accounts Routing.');
                    }
                    $client = Contact::find($transactionPayment->payment_for ?: $transaction->contact_id);
                    if (! $client || ! $client->account_id) {
                        throw new RuntimeException('Customer account is missing for sell (due). Please link an accounting account to the customer.');
                    }
                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $sales_sales->account_id;
                    $transactionPayment->amount = $salesGrossBeforeDiscount;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    if ($discountAmount > 0 && $sales_discount_allowed && $sales_discount_allowed->account_id) {
                        $transactionPayment->account_id = $sales_discount_allowed->account_id;
                        $transactionPayment->amount = $discountAmount;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    }
                    $transactionPayment->account_id = $sales_vat_calculation->account_id;
                    $transactionPayment->amount = $transaction->tax_amount;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                }

                $this->appendPerpetualCogsEntries($transactionPayment, $transaction, $acc_trans_mapping_id, $request);
            } elseif ($transaction->type == 'purchases') {
                $purchases_purchase = AccountsRoting::where('type', 'purchases_purchase')->first();
                $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
                $purchases_vat_calculation = AccountsRoting::where('type', 'purchases_vat_calculation')->first();
                $purchases_earned_discount = AccountsRoting::where('type', 'purchases_earned_discount')->first();
                $discountAmount = (float) ($transaction->discount_amount ?? 0);
                $purchasesGrossBeforeDiscount = $netTotalBeforeTax + max(0, $discountAmount);
                $inventoryAssetAccountId = Setting::isPerpetualInventory()
                    ? PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
                        isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
                    )
                    : null;
                $purchasesTargetAccountId = Setting::isPerpetualInventory()
                    ? ($inventoryAssetAccountId ?: $purchases_purchase?->account_id)
                    : ($purchases_purchase?->account_id);
                if (! $purchasesTargetAccountId) {
                    throw new RuntimeException('Accounting routing missing for purchases. Please configure purchases_purchase route or Inventory account (gl_code 1105).');
                }
                if (! $purchases_vat_calculation || ! $purchases_vat_calculation->account_id) {
                    throw new RuntimeException('Accounting routing missing for purchases VAT. Please configure purchases_vat_calculation route.');
                }
                if ($transaction->invoice_type == 'cash' && ! $cash_account_id) {
                    throw new RuntimeException('Cash account is missing for purchases cash invoice. Please configure cash account.');
                }

                if ($transaction->invoice_type == 'cash') {
                    if (! $transactionPayment->payment_for) {
                        $transactionPayment->account_id = $cash_account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        $transactionPayment->account_id = $purchasesTargetAccountId;
                        $transactionPayment->amount = $purchasesGrossBeforeDiscount;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        if ($discountAmount > 0 && $purchases_earned_discount && $purchases_earned_discount->account_id) {
                            $transactionPayment->account_id = $purchases_earned_discount->account_id;
                            $transactionPayment->amount = $discountAmount;
                            $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        }
                        $transactionPayment->account_id = $purchases_vat_calculation->account_id;
                        $transactionPayment->amount = $transaction->tax_amount;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    } else {
                        $client = Contact::find($transactionPayment->payment_for);
                        if (! $client || ! $client->account_id) {
                            throw new RuntimeException('Supplier account is missing for purchases. Please ensure the supplier has an accounting account linked.');
                        }
                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $purchasesTargetAccountId;
                        $transactionPayment->amount = $purchasesGrossBeforeDiscount;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        if ($discountAmount > 0 && $purchases_earned_discount && $purchases_earned_discount->account_id) {
                            $transactionPayment->account_id = $purchases_earned_discount->account_id;
                            $transactionPayment->amount = $discountAmount;
                            $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                        }
                        $transactionPayment->account_id = $purchases_vat_calculation->account_id;
                        $transactionPayment->amount = $transaction->tax_amount;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $client->account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        $transactionPayment->account_id = $cash_account_id;
                        $transactionPayment->amount = $transaction->final_total;
                        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                        //
                    }
                }
                // else {
                //     $client = Contact::find($transactionPayment->payment_for);
                //     if ($client) {
                //         $transactionPayment->account_id = $client->account_id;
                //         $transactionPayment->amount = $transaction->final_total;
                //         $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                //     } else {
                //         $accountsRoute = AccountsRoting::where('type', 'purchases_suppliers')->first();
                //         if ($accountsRoute && $accountsRoute->direction == 'auto_assign') {
                //             $transactionPayment->account_id = $accountsRoute->account_id;
                //             $transactionPayment->amount = $transaction->final_total;
                //             $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                //         }
                //     }
                // }
            } elseif ($transaction->type == 'sell-return') {
                $sales_sell_return = AccountsRoting::where('type', 'sales_sell_return')->first();
                $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
                $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

                if (! $sales_sell_return || ! $sales_sales || ! $sales_vat_calculation) {
                    throw new RuntimeException('Accounting routing missing for sell-return. Please configure Accounts Routing: sales_sell_return, sales_sales, sales_vat_calculation.');
                }

                if ($transaction->invoice_type == 'cash') {
                    $client = Contact::find($transactionPayment->payment_for);
                    if (! $client || ! $client->account_id) {
                        throw new RuntimeException('Customer account is missing for sell-return. Please ensure the customer has an accounting account linked.');
                    }
                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $sales_sales->account_id;
                    $transactionPayment->amount = $netTotalBeforeTax;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    $transactionPayment->account_id = $sales_vat_calculation->account_id;
                    $transactionPayment->amount = $transaction->tax_amount;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $sales_sell_return->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                } else {
                    $transactionPayment->account_id = $sales_sell_return->account_id;
                    $transactionPayment->amount = $transaction->final_total;

                    $this->saveAccountRouteTransaction(
                        'credit',
                        $transactionPayment,
                        $transaction,
                        $acc_trans_mapping_id,
                        $request
                    );

                    $transactionPayment->account_id = $sales_sales->account_id;
                    $transactionPayment->amount = $netTotalBeforeTax;

                    $this->saveAccountRouteTransaction(
                        'debit',
                        $transactionPayment,
                        $transaction,
                        $acc_trans_mapping_id,
                        $request
                    );

                    $transactionPayment->account_id = $sales_vat_calculation->account_id;
                    $transactionPayment->amount = $transaction->tax_amount;

                    $this->saveAccountRouteTransaction(
                        'debit',
                        $transactionPayment,
                        $transaction,
                        $acc_trans_mapping_id,
                        $request
                    );
                }
            } elseif ($transaction->type == 'purchases-return') {
                $purchases_purchase = AccountsRoting::where('type', 'purchases_purchase')->first();
                $purchases_purchase_return = AccountsRoting::where('type', 'purchases_purchase_return')->first();
                $purchases_vat_calculation = AccountsRoting::where('type', 'purchases_vat_calculation')->first();

                if (! $purchases_purchase || ! $purchases_purchase_return || ! $purchases_vat_calculation) {
                    throw new RuntimeException('Accounting routing missing for purchases-return. Please configure Accounts Routing: purchases_purchase, purchases_purchase_return, purchases_vat_calculation.');
                }

                if ($transaction->invoice_type == 'cash') {
                    $client = Contact::find($transactionPayment->payment_for);
                    if (! $client || ! $client->account_id) {
                        throw new RuntimeException('Supplier account is missing for purchases-return. Please ensure the supplier has an accounting account linked.');
                    }
                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $purchases_purchase->account_id;
                    $transactionPayment->amount = $netTotalBeforeTax;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    $transactionPayment->account_id = $purchases_vat_calculation->account_id;
                    $transactionPayment->amount = $transaction->tax_amount;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    // credit         debit

                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $purchases_purchase_return->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                } else {

                    $client = Contact::find($transactionPayment->payment_for);
                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $purchases_purchase->account_id;
                    $transactionPayment->amount = $netTotalBeforeTax;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                    $transactionPayment->account_id = $purchases_vat_calculation->account_id;
                    $transactionPayment->amount = $transaction->tax_amount;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $client->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);

                    $transactionPayment->account_id = $purchases_purchase_return->account_id;
                    $transactionPayment->amount = $transaction->final_total;
                    $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
                }
            }

            //   dd($transaction);

            // if ($transaction->type == 'sell-return') {
            //     $accountsRoute = AccountsRoting::where('type', 'sales_sell_return')->first();

            // } else if ($transaction->type == 'purchases-return') {
            //     $accountsRoute = AccountsRoting::where('type', 'purchases_purchase_return')->first();
            //     if ($accountsRoute && $accountsRoute->direction == 'auto_assign') {
            //         $transactionPayment->account_id = $accountsRoute->account_id;
            //         $transactionPayment->amount = $transaction->final_total;
            //         $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
            //     }
            // } else {

            //     // foreach ($accountsRoute as $accountRoute) {
            //     //     $transactionPayment->account_id = $accountRoute->account_id;
            //     //     [$amount, $type] = $this->determineAmountAndType($accountRoute->type, $transaction);
            //     //     $transactionPayment->amount = $amount;

            //     //     if ($amount) {
            //     //         $this->saveAccountRouteTransaction($type, $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
            //     //     }
            //     // }
            // }
        }

        if (isset($acc_trans_mapping_id)) {
            AutoJournalGuard::assertBalanced((int) $acc_trans_mapping_id);
        }

        return true;
    }

    private function appendPerpetualCogsEntries($transactionPayment, $transaction, int $accTransMappingId, $request): void
    {
        if (! Setting::isPerpetualInventory() || $transaction->type !== 'sell') {
            return;
        }

        $inventoryAccountId = PerpetualInventoryAccountResolver::resolveInventoryAssetAccountId(
            isset($transaction->establishment_id) ? (int) $transaction->establishment_id : null
        );
        $cogsAccountId = AccountingAccount::where('gl_code', '50101')
            ->orWhere('account_category', 'COGS')
            ->orWhere('account_category', 'cost_of_goods_sold')
            ->value('id');

        if (! $inventoryAccountId || ! $cogsAccountId) {
            return;
        }

        $cogsAmount = (float) TransactionSellLine::query()
            ->join('product_products as p', 'p.id', '=', 'transaction_sell_lines.product_id')
            ->where('transaction_sell_lines.transaction_id', $transaction->id)
            ->sum(DB::raw('COALESCE(transaction_sell_lines.qyt,0) * COALESCE(p.cost,0)'));

        if ($cogsAmount <= 0) {
            return;
        }

        $transactionPayment->account_id = $cogsAccountId;
        $transactionPayment->amount = $cogsAmount;
        $this->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $accTransMappingId, $request);

        $transactionPayment->account_id = $inventoryAccountId;
        $transactionPayment->amount = $cogsAmount;
        $this->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $accTransMappingId, $request);
    }

    protected function determineAmountAndType($routeType, $transaction)
    {
        return match ($routeType) {
            'sales_sales' => [$transaction->totalAfterDiscount, 'credit'],
            'sales_vat_calculation' => [$transaction->tax_amount, 'credit'],
            'sales_discount_calculation' => [$transaction->discount_amount, 'debit'],
            'sales_discount_allowed' => [$transaction->discount_amount, 'debit'],

            'purchases_purchases' => [$transaction->totalAfterDiscount, 'debit'],
            'purchases_vat_calculation' => [$transaction->tax_amount, 'debit'],
            'purchases_discount_calculation' => [$transaction->discount_amount, 'credit'],
            'purchases_earned_discount' => [$transaction->discount_amount, 'credit'],

            'sales_return_sales' => [$transaction->totalAfterDiscount, 'debit'],
            'purchases_return_purchases' => [$transaction->totalAfterDiscount, 'credit'],

            default => [0, 'debit'],
        };
    }

    public static function default_accounting_route()
    {
        $vat_acc = AccountingAccount::where('gl_code', '522')->first();
        $purchases_acc = AccountingAccount::where('gl_code', '513')->first();
        $sales_acc = AccountingAccount::where('gl_code', '4101')->first()
            ?? AccountingAccount::where('gl_code', '411')->first()
            ?? AccountingAccount::where('gl_code', '401')->first();
        $discount_acc = AccountingAccount::where('gl_code', '523')->first();
        $sales_return_acc = AccountingAccount::where('gl_code', '412')->first()
            ?? $sales_acc;
        $periodic_inv_adj = AccountingAccount::where('gl_code', '50105')->first()
            ?? AccountingAccount::where('account_category', 'inventory_adjustment')->first()
            ?? AccountingAccount::where('gl_code', '50101')->first();

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
                'type' => 'sales_discount_allowed',
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
            [
                'type' => 'purchases_earned_discount',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $discount_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'sales_sell_return',
                'section' => 'sales',
                'routing_type' => 'revenue',
                'account_id' => $sales_return_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'purchases_purchase_return',
                'section' => 'purchases',
                'routing_type' => 'expense',
                'account_id' => $purchases_acc?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'periodic_inventory_adjustment',
                'section' => 'periodic_inventory',
                'routing_type' => 'expense',
                'account_id' => $periodic_inv_adj?->id,
                'direction' => 'auto_assign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        AccountsRoting::insert($data);
    }

    public static function default_accounting_account_types()
    {
        return $account_sub_types = [
            [
                'name_en' => 'Current Assets',
                'name_ar' => 'الأصول المتداولة',
                'gl_code' => '11',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Fixed Assets',
                'name_ar' => 'الأصول الثابتة',
                'gl_code' => '12',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'asset',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Current Liabilities',
                'name_ar' => 'خصوم متداولة',
                'gl_code' => '21',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Long-Term Liabilities',
                'name_ar' => 'خصوم طويلة الأجل',
                'gl_code' => '22',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'liabilities',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Equity',
                'name_ar' => 'حقوق الملكية',
                'gl_code' => '31',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'equity',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Revenue',
                'name_ar' => 'الإيرادات',
                'gl_code' => '41',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'income',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Cost Of Sales',
                'name_ar' => 'تكلفة المبيعات',
                'gl_code' => '501',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Payroll & HR',
                'name_ar' => 'رواتب وموارد بشرية',
                'gl_code' => '502',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Office Expenses',
                'name_ar' => 'مصاريف مكاتب',
                'gl_code' => '503',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'IT',
                'name_ar' => 'تقنيات المعلومات',
                'gl_code' => '504',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Travel',
                'name_ar' => 'سفر وتنقل',
                'gl_code' => '505',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Government Fees',
                'name_ar' => 'رسوم حكومية',
                'gl_code' => '506',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Professional Services',
                'name_ar' => 'خدمات مهنية',
                'gl_code' => '507',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Insurance',
                'name_ar' => 'التأمين',
                'gl_code' => '508',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Maintenance',
                'name_ar' => 'صيانة وخدمات',
                'gl_code' => '509',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'PR',
                'name_ar' => 'ضيافة وعلاقات عامة',
                'gl_code' => '510',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Banking',
                'name_ar' => 'مصاريف بنكية',
                'gl_code' => '511',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
            [
                'name_en' => 'Other Expenses',
                'name_ar' => 'مصاريف متنوعة',
                'gl_code' => '512',
                'show_balance' => 1,
                'account_type' => 'sub_type',
                'account_primary_type' => 'expenses',
                'parent_id' => null,
            ],
        ];
    }

    public static function Default_Accounts()
    {
        $user_id = Auth::user()->id;

        $current_assets_id = AccountingAccountTypes::where('name_en', 'Current Assets')->first()->id;
        $fixed_assets_id = AccountingAccountTypes::where('name_en', 'Fixed Assets')->first()->id;
        $current_liabilities_id = AccountingAccountTypes::where('name_en', 'Current Liabilities')->first()->id;
        $long_term_liabilities_id = AccountingAccountTypes::where('name_en', 'Long-Term Liabilities')->first()->id;
        $Equity_id = AccountingAccountTypes::where('name_en', 'Equity')->first()->id;
        $Revenue_id = AccountingAccountTypes::where('name_en', 'Revenue')->first()->id;
        $Cost_Sales_id = AccountingAccountTypes::where('name_en', 'Cost Of Sales')->first()->id;
        $Payroll_id = AccountingAccountTypes::where('name_en', 'Payroll & HR')->first()->id;
        $Office_Expenses_id = AccountingAccountTypes::where('name_en', 'Office Expenses')->first()->id;
        $IT_id = AccountingAccountTypes::where('name_en', 'IT')->first()->id;
        $Travel_id = AccountingAccountTypes::where('name_en', 'Travel')->first()->id;
        $Government_Fees_id = AccountingAccountTypes::where('name_en', 'Government Fees')->first()->id;
        $Professional_Services_id = AccountingAccountTypes::where('name_en', 'Professional Services')->first()->id;
        $Insurance_id = AccountingAccountTypes::where('name_en', 'Insurance')->first()->id;
        $Maintenance_id = AccountingAccountTypes::where('name_en', 'Maintenance')->first()->id;
        $PR_id = AccountingAccountTypes::where('name_en', 'PR')->first()->id;
        $Banking_id = AccountingAccountTypes::where('name_en', 'Banking')->first()->id;
        $Other_Expenses_id = AccountingAccountTypes::where('name_en', 'Other Expenses')->first()->id;

        $rows = [
            [
                'name_en' => 'Cash',
                'name_ar' => 'الصندوق',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Bank Accounts',
                'name_ar' => 'البنوك',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Accounts Receivable',
                'name_ar' => 'المدينون (العملاء)',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1103',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Notes Receivable',
                'name_ar' => 'أوراق القبض',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1104',
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
                'gl_code' => '1105',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Employee Advances',
                'name_ar' => 'سلف وعهد الموظفين',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1106',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Prepaid Expenses',
                'name_ar' => 'مصاريف مدفوعة مقدماً',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1107',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Work in Progress',
                'name_ar' => 'اعمال تحت التنفيذ',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1108',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Construction in Progress',
                'name_ar' => 'مشاريع تحت التنفيذ – مقاولات',
                'account_primary_type' => 'asset',
                'account_type' => 'current_assets',
                'account_sub_type_id' => $current_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1109',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Land',
                'name_ar' => 'أراضي',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1201',
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
                'gl_code' => '1202',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Machinery & Equipment',
                'name_ar' => 'الات ومعدات',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1203',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Furniture & Fixtures',
                'name_ar' => 'أثاث وتجهيزات',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1204',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Vehicles',
                'name_ar' => 'سيارات',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1205',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Intangible Assets',
                'name_ar' => 'أصول غير ملموسة',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1206',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Long-Term Investments',
                'name_ar' => 'استثمارات طويلة الأجل',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1207',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Capital Work in Progress',
                'name_ar' => 'مشاريع تحت التنفيذ – أصول ثابتة',
                'account_primary_type' => 'asset',
                'account_type' => 'fixed_assets',
                'account_sub_type_id' => $fixed_assets_id,
                'detail_type_id' => null,
                'gl_code' => '1208',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Accounts Payable',
                'name_ar' => 'الدائنون – الموردون',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Notes Payable',
                'name_ar' => 'أوراق الدفع',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Accrued Expenses',
                'name_ar' => 'المصروفات المستحقة',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2103',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Short-Term Loans',
                'name_ar' => 'قروض قصيرة الاجل',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2104',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Customers Advance',
                'name_ar' => 'دفعات مقدمة من العملاء',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2105',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Government Payables',
                'name_ar' => 'ذمم حكومية',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2106',
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
                'gl_code' => '2201',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Lease Liabilities',
                'name_ar' => 'التزامات عقود الإيجار',
                'account_primary_type' => 'liabilities',
                'account_type' => 'non_current_liabilities',
                'account_sub_type_id' => $long_term_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '2202',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Capital',
                'name_ar' => 'رأس المال',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                // //'account_category' => '',
                'account_sub_type_id' => $Equity_id,
                'detail_type_id' => null,
                'gl_code' => '3101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'General Reserve',
                'name_ar' => 'الاحتياطي العام',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                // 'account_category' => '',
                'account_sub_type_id' => $Equity_id,
                'detail_type_id' => null,
                'gl_code' => '3102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Retained Earnings',
                'name_ar' => 'الأرباح المرحلة',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                // 'account_category' => '',
                'account_sub_type_id' => $Equity_id,
                'detail_type_id' => null,
                'gl_code' => '3103',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Dividends',
                'name_ar' => 'توزيعات الأرباح',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                // 'account_category' => '',
                'account_sub_type_id' => $Equity_id,
                'detail_type_id' => null,
                'gl_code' => '3104',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Net Income/Loss',
                'name_ar' => 'صافي الربح/الخسارة',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                // 'account_category' => '',
                'account_sub_type_id' => $Equity_id,
                'detail_type_id' => null,
                'gl_code' => '3105',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Operating Revenue',
                'name_ar' => 'إيرادات النشاط الرئيسي',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                // 'account_category' => '',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '4101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Service Revenue',
                'name_ar' => 'إيرادات الخدمات',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                // 'account_category' => '',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '4102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Contract Revenue',
                'name_ar' => 'إيرادات العقود',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                // 'account_category' => '',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '4103',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Other Income',
                'name_ar' => 'إيرادات أخرى',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                // 'account_category' => '',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '4104',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Gain on Asset Disposal',
                'name_ar' => 'أرباح بيع أصل ثابت',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                // 'account_category' => '',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '4105',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Cost of Goods Sold',
                'name_ar' => 'تكلفة البضاعة المباعة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Cost_Sales_id,
                'detail_type_id' => null,
                'gl_code' => '50101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Direct Materials',
                'name_ar' => 'مواد مباشرة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Cost_Sales_id,
                'detail_type_id' => null,
                'gl_code' => '50104',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Direct Labor',
                'name_ar' => 'أجور مباشرة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Cost_Sales_id,
                'detail_type_id' => null,
                'gl_code' => '50102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Direct Operating Expenses',
                'name_ar' => 'مصاريف تشغيل مباشرة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Cost_Sales_id,
                'detail_type_id' => null,
                'gl_code' => '50103',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Management Salaries',
                'name_ar' => 'رواتب الإدارة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50201',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Administrative Salaries',
                'name_ar' => 'رواتب القسم الإداري',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50202',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Employee Allowances',
                'name_ar' => 'بدلات الموظفين',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50203',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Bonuses',
                'name_ar' => 'المكافات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50204',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'End of Service Benefits',
                'name_ar' => 'مستحقات نهاية الخدمة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50205',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Employee Accruals',
                'name_ar' => 'مستحقات الموظفين',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Payroll_id,
                'detail_type_id' => null,
                'gl_code' => '50206',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Office Rent',
                'name_ar' => 'إيجار المكتب',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50301',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Utilities',
                'name_ar' => 'كهرباء ومياه',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50302',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Telephone & Internet',
                'name_ar' => 'هاتف وانترنت',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50303',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Office Supplies',
                'name_ar' => 'أدوات المكتب',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50304',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Printing & Copying',
                'name_ar' => 'طباعة وتصوير',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50305',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Office Hospitality',
                'name_ar' => 'ضيافة مكتبية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Office_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '50306',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Software Subscriptions',
                'name_ar' => 'اشتراكات انظمة برمجية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $IT_id,
                'detail_type_id' => null,
                'gl_code' => '50401',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Software Licenses',
                'name_ar' => 'تراخيص برامج',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $IT_id,
                'detail_type_id' => null,
                'gl_code' => '50402',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'IT Maintenance',
                'name_ar' => 'صيانة اجهزة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $IT_id,
                'detail_type_id' => null,
                'gl_code' => '50403',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'IT Equipment',
                'name_ar' => 'شراء اجهزة كمبيوتر',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $IT_id,
                'detail_type_id' => null,
                'gl_code' => '50404',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Vehicle Fuel',
                'name_ar' => 'وقود سيارات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Travel_id,
                'detail_type_id' => null,
                'gl_code' => '50501',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Vehicle Maintenance',
                'name_ar' => 'صيانة سيارات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Travel_id,
                'detail_type_id' => null,
                'gl_code' => '50502',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Travel Allowances',
                'name_ar' => 'بدلات سفر',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Travel_id,
                'detail_type_id' => null,
                'gl_code' => '50503',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Tickets & Accommodation',
                'name_ar' => 'تذاكر واقامة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Travel_id,
                'detail_type_id' => null,
                'gl_code' => '50504',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Commercial Registration Fees',
                'name_ar' => 'رسوم السجل التجاري',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Government_Fees_id,
                'detail_type_id' => null,
                'gl_code' => '50601',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Municipality Fees',
                'name_ar' => 'رخص بلدية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Government_Fees_id,
                'detail_type_id' => null,
                'gl_code' => '50602',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'GOSI',
                'name_ar' => 'تأمينات اجتماعية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Government_Fees_id,
                'detail_type_id' => null,
                'gl_code' => '50603',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Expat Fees',
                'name_ar' => 'رسوم المقابل المالي',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Government_Fees_id,
                'detail_type_id' => null,
                'gl_code' => '50604',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Government Platform Fees',
                'name_ar' => 'رسوم المنصات الحكومية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Government_Fees_id,
                'detail_type_id' => null,
                'gl_code' => '50605',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Legal Fees',
                'name_ar' => 'أتعاب محامة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Professional_Services_id,
                'detail_type_id' => null,
                'gl_code' => '50701',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Audit Fees',
                'name_ar' => 'أتعاب محاسبية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Professional_Services_id,
                'detail_type_id' => null,
                'gl_code' => '50702',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Business Consulting',
                'name_ar' => 'استشارات اعمال',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Professional_Services_id,
                'detail_type_id' => null,
                'gl_code' => '50703',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Medical Insurance',
                'name_ar' => 'تأمين طبي',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Insurance_id,
                'detail_type_id' => null,
                'gl_code' => '50801',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Property Insurance',
                'name_ar' => 'تأمين ممتلكات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Insurance_id,
                'detail_type_id' => null,
                'gl_code' => '50802',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Vehicle Insurance',
                'name_ar' => 'تأمين مركبات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Insurance_id,
                'detail_type_id' => null,
                'gl_code' => '50803',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Building Maintenance',
                'name_ar' => 'صيانة مباني',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Maintenance_id,
                'detail_type_id' => null,
                'gl_code' => '50901',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Equipment Maintenance',
                'name_ar' => 'صيانة معدات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Maintenance_id,
                'detail_type_id' => null,
                'gl_code' => '50902',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Annual Maintenance Contracts',
                'name_ar' => 'عقود صيانة سنوية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Maintenance_id,
                'detail_type_id' => null,
                'gl_code' => '50903',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Employee Hospitality',
                'name_ar' => 'ضيافة موظفين',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $PR_id,
                'detail_type_id' => null,
                'gl_code' => '51001',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Client Hospitality',
                'name_ar' => 'ضيافة عملاء',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $PR_id,
                'detail_type_id' => null,
                'gl_code' => '51002',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Gifts & Events',
                'name_ar' => 'هداية ومناسبات',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $PR_id,
                'detail_type_id' => null,
                'gl_code' => '51003',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Bank Charges',
                'name_ar' => 'رسوم بنكية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Banking_id,
                'detail_type_id' => null,
                'gl_code' => '51101',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Bank Differences',
                'name_ar' => 'فروقات بنكية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Banking_id,
                'detail_type_id' => null,
                'gl_code' => '51102',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Bad Debts',
                'name_ar' => 'ديون مشكوك فيها',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Other_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '51201',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            [
                'name_en' => 'Memberships',
                'name_ar' => 'اشتراكات مهنية',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Other_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '51202',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Prior Period Adjustments',
                'name_ar' => 'مصاريف سنوات سابقة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Other_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '51203',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            // gl_code 411,412,513,522,523,50105 — يُربط بـ default_accounting_route()
            [
                'name_en' => 'Main sales account',
                'name_ar' => 'مبيعات رئيسية',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '411',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Sales returns',
                'name_ar' => 'مردود المبيعات',
                'account_primary_type' => 'income',
                'account_type' => 'income',
                'account_sub_type_id' => $Revenue_id,
                'detail_type_id' => null,
                'gl_code' => '412',
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
                'account_sub_type_id' => $Cost_Sales_id,
                'detail_type_id' => null,
                'gl_code' => '513',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'VAT payable',
                'name_ar' => 'ضريبة القيمة المضافة مستحقة',
                'account_primary_type' => 'liabilities',
                'account_type' => 'current_liabilities',
                'account_sub_type_id' => $current_liabilities_id,
                'detail_type_id' => null,
                'gl_code' => '522',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Trade discounts',
                'name_ar' => 'خصومات تجارية مسموحة ومكتسبة',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Other_Expenses_id,
                'detail_type_id' => null,
                'gl_code' => '523',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name_en' => 'Periodic inventory adjustment',
                'name_ar' => 'تسوية جرد دوري',
                'account_primary_type' => 'expenses',
                'account_type' => 'expenses',
                'account_sub_type_id' => $Cost_Sales_id,
                'account_category' => 'inventory_adjustment',
                'detail_type_id' => null,
                'gl_code' => '50105',
                'status' => 'active',
                'created_by' => $user_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ];

        return self::normalizeAccountInsertRows($rows);
    }

    /**
     * توحيد مفاتيح كل صف لـ bulk insert (Laravel يعتمد مفاتيح الصف الأول؛ أي عمود إضافي في صف لاحق يسبب 1136).
     */
    protected static function normalizeAccountInsertRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $allKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $allKeys[$key] = true;
            }
        }
        $allKeys = array_keys($allKeys);
        sort($allKeys);

        $normalized = [];
        foreach ($rows as $row) {
            $out = [];
            foreach ($allKeys as $key) {
                $out[$key] = array_key_exists($key, $row) ? $row[$key] : null;
            }
            $normalized[] = $out;
        }

        return $normalized;
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

    /**
     * Segment length when appending a child GL code.
     * Primary (1 digit) → sub-type adds 1 digit (11); deeper levels add 2 digits (01, 02).
     * Expenses under primary 5 use 2 digits at the first level (501, 502).
     */
    public static function glCodeSegmentLengthForParent(string $parentCode, ?string $accountPrimaryType = null): int
    {
        $len = strlen(trim($parentCode));

        if ($len <= 1) {
            return $accountPrimaryType === 'expenses' ? 2 : 1;
        }

        return 2;
    }

    /**
     * Build the next GL code under a parent prefix.
     * Example: 1 → 11 → 1101 → 1102; 2 → 21 → 2101.
     */
    public static function nextGlCodeFromParentCode(
        string $parentCode,
        ?string $lastSiblingCode = null,
        ?string $accountPrimaryType = null
    ): string {
        $parentCode = trim($parentCode);
        $segmentLen = self::glCodeSegmentLengthForParent($parentCode, $accountPrimaryType);

        if ($lastSiblingCode === null || $lastSiblingCode === '' || ! str_starts_with((string) $lastSiblingCode, $parentCode)) {
            return $parentCode.str_pad('1', $segmentLen, '0', STR_PAD_LEFT);
        }

        $suffix = substr((string) $lastSiblingCode, strlen($parentCode));
        if ($suffix === '' || strlen($suffix) < $segmentLen) {
            return $parentCode.str_pad('1', $segmentLen, '0', STR_PAD_LEFT);
        }

        $lastSegment = substr($suffix, -$segmentLen);
        $prefixPart = substr($suffix, 0, -$segmentLen);
        $nextSegment = str_pad((string) ((int) $lastSegment + 1), $segmentLen, '0', STR_PAD_LEFT);

        return $parentCode.$prefixPart.$nextSegment;
    }

    public static function next_GLC($parent_account_id)
    {
        $parent_account = AccountingAccount::findOrFail($parent_account_id);
        $parentCode = (string) $parent_account->gl_code;

        $lastSibling = AccountingAccount::where('parent_account_id', $parent_account_id)
            ->orderByRaw('LENGTH(gl_code) DESC')
            ->orderBy('gl_code', 'desc')
            ->first();

        return self::nextGlCodeFromParentCode(
            $parentCode,
            $lastSibling?->gl_code,
            $parent_account->account_primary_type
        );
    }

    /**
     * Next GL code for a new top-level account under an account sub-type node.
     */
    public static function next_GLC_for_sub_type(int $accountSubTypeId): string
    {
        $subType = AccountingAccountTypes::findOrFail($accountSubTypeId);
        $parentCode = (string) $subType->gl_code;

        $lastAccount = AccountingAccount::where('account_sub_type_id', $accountSubTypeId)
            ->whereNull('parent_account_id')
            ->orderByRaw('LENGTH(gl_code) DESC')
            ->orderBy('gl_code', 'desc')
            ->first();

        return self::nextGlCodeFromParentCode(
            $parentCode,
            $lastAccount?->gl_code,
            $subType->account_primary_type
        );
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
            'analytical_accounts' => __('accounting::lang.account_types.analytical_accounts'),
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

            [$year, $number] = explode('/', $last_ref_no);

            if ($year == $currentYear) {
                $newNumber = str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                $new_ref_no = $currentYear.'/'.$newNumber;
            } else {
                $new_ref_no = $currentYear.'/0001';
            }

            return $new_ref_no;
        }

        return $new_ref_no = $currentYear.'/0001';
    }

    public function getAgeingReport($type, $group_by, array $filters = [])
    {
        $today = $filters['as_of_date'] ?? Carbon::now()->format('Y-m-d');
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $contactId = $filters['contact_id'] ?? null;
        $query = Transaction::query();

        if ($type == 'sell') {
            $query->where('transactions.type', 'sell')
                ->where('transactions.status', 'approved');
        } elseif ($type == 'purchases') {
            $query->where('transactions.type', 'purchases')
                ->where('transactions.status', 'approved');
        }

        $dues = $query->whereIn('transactions.payment_status', ['partial', 'due'])
            ->when($contactId, function ($q) use ($contactId) {
                return $q->where('transactions.contact_id', $contactId);
            })
            ->when($startDate, function ($q) use ($startDate) {
                return $q->whereDate('transactions.transaction_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                return $q->whereDate('transactions.transaction_date', '<=', $endDate);
            })
            ->join('cs_contacts as c', 'c.id', '=', 'transactions.contact_id')
            ->select(
                DB::raw(
                    'DATEDIFF("'.$today.'", transactions.due_date) as diff'

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
                'c.name',
            ])
            ->get();

        $report_details = [];
        if ($group_by == 'contact') {
            foreach ($dues as $due) {
                if (! isset($report_details[$due->contact_id])) {
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

                if ((float) $due->total_due <= 0) {
                    continue;
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
                if ((float) $due->total_due <= 0) {
                    continue;
                }

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
