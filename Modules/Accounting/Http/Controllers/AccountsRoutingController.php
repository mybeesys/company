<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $options = [
            'auto_assign' => 'تعيين تلقائي',
            'no_routing' => 'بلا توجيه',
            'cancel_account' => 'إلغاء الحساب',
            'assign_to_each' => 'تعيين لكل منها',
        ];


        return view('accounting::AccountsRouting.index', compact('accounts','options'));
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
        $data = $request->all();

        $directions = [];

        $mapping = [
            'purchases' => 'expense',
            'suppliers' => 'liability',
            'purchases_return' => 'revenue',
            'discount_purchases' => 'revenue',
            'sales' => 'revenue',
            'sell_return' => 'expense',
            'discount_sales' => 'expense',
        ];

        foreach ($data as $key => $value) {
            if ($value !== null) {
                if (str_contains($key, '_type_route')) {
                    $type = str_replace('_type_route', '', $key);
                    $directionType = $mapping[$type] ?? null;

                    if ($directionType) {
                        $directions[$type]['type'] = $type;
                        $directions[$type]['direction_type'] = $directionType;
                    }
                } elseif (str_contains($key, '_account')) {
                    $type = str_replace('_account', '', $key);
                    $accountId = $value;

                    $directions[$type]['account_id'] = $accountId;
                }
            }
        }

        $formattedDirections = array_values($directions);

        return response()->json(['directions' => $formattedDirections]);


        // AccountsRoting::create([

        // ]);
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