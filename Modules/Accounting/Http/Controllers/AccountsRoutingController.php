<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;

class AccountsRoutingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $accounts =  AccountingAccount::forDropdown();
        if (count($accounts) == 0) {
            return redirect()->route('tree-of-accounts')->with('error', __('accounting::lang.no_accounts'));
        }
        $options = [
            'auto_assign' => 'تعيين تلقائي',
            'no_routing' => 'بلا توجيه',
            // 'assign_to_each' => 'تعيين لكل منها',
        ];
        $accountsRoting = AccountsRoting::all();

        return view('accounting::AccountsRouting.index', compact('accounts', 'accountsRoting', 'options'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounting::create');
    }



    public function store(Request $request)
    {
        $data = $request->all();

        $directions = [];

        $mapping = [
            // 'sales_client' => 'liability',
            'sales_sales' => 'revenue',
            'sales_vat_calculation' => 'liability',
            // 'sales_total_amount' => 'asset',
            // 'sales_amount_before_vat' => 'asset',
            'sales_discount_calculation' => 'expense',
            'sales_sell_return' => 'expense',
            'purchases_vat_calculation' => 'liability',
            // 'purchases_total_amount' => 'asset',
            // 'purchases_amount_before_vat' => 'asset',
            'purchases_discount_calculation' => 'expense',
            // 'purchases_suppliers' => 'liability',
            'purchases_purchase' => 'expense',
            'purchases_purchase_return' => 'expense',
        ];


        foreach ($data as $key => $value) {
            if ($value !== null && str_contains($key, '_account')) {
                $type = str_replace('_account', '', $key);
                $accountId = $value;

                $directionType = $mapping[$type] ?? null;
                if (!$directionType) {
                    continue;
                }

                $isSales = strpos($key, 'sales_') !== false;
                $isPurchases = strpos($key, 'purchases_') !== false;

                $directions[$type] = [
                    'type' => $type,
                    'routing_type' => $directionType,
                    'direction' => 'auto_assign',
                    'section' => $isSales ? 'sales' : 'purchases',
                    'account_id' => $accountId,
                ];
            }
        }


        $formattedDirections = array_values($directions);


        try {
            DB::beginTransaction();
            foreach ($formattedDirections as $direction) {
                if (isset($direction['type'], $direction['routing_type'], $direction['direction'], $direction['account_id'])) {

                    AccountsRoting::updateOrCreate(
                        [
                            'type' => $direction['type'],
                            'section' => $direction['section']
                        ],
                        [
                            'routing_type' => $direction['routing_type'],
                            'direction' => 'auto_assign',
                            'account_id' => $direction['account_id']
                        ]
                    );
                }
            }

            DB::commit();
            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
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
