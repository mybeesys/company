<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCombo;
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

        try {


            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();
            $ref_no =  SalesUtile::generateReferenceNumber('sell');
            $main_establishment = Establishment::notMain()->active()->first();
            $establishment_id = $request->establishment_id;
            if ($request->establishment_id == $main_establishment->id) {
                $establishment_id = $main_establishment->id;
            }
            $created_by = Employee::find($request->created_by);
            if (!$created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }
            $transaction =   Transaction::create([
                'type' => 'sell',
                'invoice_type' => $request->payment_status,
                'due_date' => null,
                'transaction_date' => Carbon::createFromFormat('d/m/Y H:i', $request->date)->format('Y-m-d H:i:s'),
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
                'invoice_no' => $request->invoice_no,
                'shift_number' => $request->shift_number,
                'establishment_id' => $establishment_id,
            ]);



            $products = json_decode(json_encode($request->products));

            foreach ($products as $product) {
                $find_product = Product::find($product->product_id);
                if (!$find_product) {
                    return response()->json(['message' => 'Product not found id =' . $product->product_id], 404);
                }

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->product_id,
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

            $modifiers = json_decode(json_encode($request->modifiers));

            foreach ($modifiers as $modifier) {
                $find_product = Product::find($modifier->id);
                if (!$find_product) {
                    return response()->json(['message' => 'Modifier not found id =' . $modifier->id], 404);
                }

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $modifier->id,
                    'qyt' => $modifier->quantity,
                    'unit_price_before_discount' => $modifier->unit_price_before_discount,
                    'unit_price' => $modifier->price_without_tax,
                    'discount_type' => $modifier->discount_type,
                    'discount_amount' => $modifier->discount_amount,
                    'unit_price_inc_tax' => $modifier->price,
                    // 'tax_id' => $product->tax_id,
                    'tax_value' => $modifier->vat_value,
                ]);
            }


            $combos = json_decode(json_encode($request->combos));

            foreach ($combos as $combo) {

                $find_product = Product::find($combo->id);
                if (!$find_product) {
                    return response()->json(['message' => 'Combo not found id =' . $combo->id], 404);
                }

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $combo->id,
                    'qyt' => $combo->quantity,
                    'unit_price' => $combo->price,
                    'unit_price_before_discount' => 0,
                    'discount_type' => 'fixed',
                    'discount_amount' => 0,
                    'unit_price_inc_tax' => 0,
                    // 'tax_id' => $product->tax_id,
                    'tax_value' => 0,

                ]);
            }

            // return $request->paid_amount;

            $payments = json_decode(json_encode($request->payments));
            foreach ($payments as $payment) {

                $find_payment = PaymentMethod::find($payment->id);
                if (!$find_payment) {
                    return response()->json(['message' => 'Payment method not found id =' . $payment->id], 404);
                }

                if ($payment->ammount) {
                    $request['paid_amount'] = $payment->ammount;
                    $request['payment_method_id'] = $payment->id;
                    $request['invoice_type'] = $request->payment_status;

              
                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
            }
            $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();
            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'something went wrong \n'.$e], 500);
        }
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
