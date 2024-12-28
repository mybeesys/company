<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Sales\Utils\SalesUtile;

class SellApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('sales::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sales::create');
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // return $request;

        // try {


        $transactionUtil = new TransactionUtils();
        DB::beginTransaction();
        $ref_no =  SalesUtile::generateReferenceNumber('sell');

        $transaction =   Transaction::create([
            'type' => 'sell',
            'invoice_type' => $request->payment_status,
            'due_date' => null,
            'transaction_date' =>Carbon::createFromFormat('d/m/Y H:i', $request->date)->format('Y-m-d H:i:s'),
            'contact_id' => $request->customer_id,
            // 'cost_center' => $request->cost_center ?? null,
            'discount_amount' => $request->discount_amount,
            'discount_type' => $request->discount_type,
            'total_before_tax' => $request->total_without_tax,
            'total_after_discount' => $request->totalAfterDiscount,
            'tax_amount' => $request->totalVat,
            'final_total' => $request->total,
            'created_by' => $request->created_by,
            'description' => $request->note,
            'ref_no' => $ref_no,
            'status' => $request->status,
            'notice' => null,
            'invoice_no'=>$request->invoice_no,
            'shift_number'=>$request->shift_number,
            // 'payment_terms',

        ]);



        // $products = json_decode(json_encode($request->products));

        foreach ($request->products as $product) {
            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->products_id,
                'qyt' => $product->quantity,
                'unit_price_before_discount' => $product->unit_price_before_discount,
                'unit_price' => $product->price_without_tax,
                'discount_type' => $product->discount_type,
                'discount_amount' => $product->discount_amount,
                'unit_price_inc_tax' => $product->price,
                'tax_id' => $product->tax_id,
                'tax_value' => $product->vat_value,
            ]);
        }
        // return $request->paid_amount;
        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        DB::commit();
        return response()->json(['message' => 'added'], 200);

        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return response()->json(['message' => 'something went wrong'], 500);
        // }
    }


    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
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