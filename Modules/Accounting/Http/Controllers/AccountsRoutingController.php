<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountsRoting;
use Modules\General\Models\Setting;

class AccountsRoutingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (count(AccountingAccount::forDropdown()) === 0) {
            return redirect()->route('tree-of-accounts')->with('error', __('accounting::lang.no_accounts'));
        }

        return redirect()->route('accounting-settings', ['tab' => 'accounts-routing']);
    }

    /**
     * Data required for accounts routing settings (standalone or embedded tab).
     *
     * @return array{accounts: mixed, accountsRoting: mixed, options: array<string, string>, isPeriodicInventoryPolicy: bool, defaultDiscountAccountId: int|string|null, hasAccounts: bool}
     */
    public static function routingSettingsData(): array
    {
        $accounts = AccountingAccount::forDropdown();
        $defaultDiscountAccountId = AccountingAccount::where('gl_code', '523')->value('id');
        $options = [
            'auto_assign' => 'تعيين تلقائي',
            'no_routing' => 'بلا توجيه',
        ];

        return [
            'accounts' => $accounts,
            'accountsRoting' => AccountsRoting::all(),
            'options' => $options,
            'isPeriodicInventoryPolicy' => Setting::isPeriodicInventory(),
            'defaultDiscountAccountId' => $defaultDiscountAccountId,
            'hasAccounts' => count($accounts) > 0,
        ];
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
            'sales_discount_allowed' => 'expense',
            'sales_sell_return' => 'revenue',
            'purchases_vat_calculation' => 'liability',
            // 'purchases_total_amount' => 'asset',
            // 'purchases_amount_before_vat' => 'asset',
            'purchases_discount_calculation' => 'expense',
            'purchases_earned_discount' => 'expense',
            // 'purchases_suppliers' => 'liability',
            'purchases_purchase' => 'expense',
            'purchases_purchase_return' => 'expense',
            'periodic_inventory_adjustment' => 'expense',
        ];

        foreach ($data as $key => $value) {
            if ($value === null || ! str_contains((string) $key, '_account')) {
                continue;
            }
            $type = str_replace('_account', '', (string) $key);
            $directionType = $mapping[$type] ?? null;
            if (! $directionType) {
                continue;
            }
            $accountId = $value;
            $section = 'sales';
            if (str_starts_with((string) $key, 'purchases_')) {
                $section = 'purchases';
            } elseif (str_starts_with((string) $key, 'periodic_inventory_')) {
                $section = 'periodic_inventory';
            }

            $directions[$type] = [
                'type' => $type,
                'routing_type' => $directionType,
                'direction' => 'auto_assign',
                'section' => $section,
                'account_id' => $accountId,
            ];
        }

        $formattedDirections = array_values($directions);

        try {
            DB::beginTransaction();
            foreach ($formattedDirections as $direction) {
                if (isset($direction['type'], $direction['routing_type'], $direction['direction'], $direction['account_id'])) {

                    AccountsRoting::updateOrCreate(
                        [
                            'type' => $direction['type'],
                            'section' => $direction['section'],
                        ],
                        [
                            'routing_type' => $direction['routing_type'],
                            'direction' => 'auto_assign',
                            'account_id' => $direction['account_id'],
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
