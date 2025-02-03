<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\General\Models\CashRegister;

class CashRegisterApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('general::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $initial_amount = 0;
            if (! empty($request->input('initial_amount'))) {
                $initial_amount = $request->input('initial_amount');
            }
            DB::beginTransaction();
            $establishment_id = $request->establishment_id;
            $register = CashRegister::create([
                'establishment_id' => $establishment_id,
                'user_id' => $request->creator_id,
                'status' => 'open',
                'shift_number' => $request->shift_number,
                'created_at' => $request->created_at,
            ]);
            if (! empty($initial_amount)) {
                $register->cash_register_transactions()->create([
                    'amount' => $initial_amount,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'something went wrong'], 500);
        }
    }


    public function postCloseRegister(Request $request)
    {


        try {

            $input = $request->only(['close_amount', 'shift_number', 'close_at', 'transaction_ids']);
            $input['close_amount'] = $input['close_amount'];
            $user_id = Auth::user()->id;
            $input['status'] = 'close';

            DB::beginTransaction();
            CashRegister::where('user_id', $user_id)
                ->where('status', 'open')
                ->update($input);

            DB::commit();
            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'something went wrong'], 500);
        }
        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('general::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('general::edit');
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