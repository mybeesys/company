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
        return $request;

        try {


            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();
            $ref_no =  SalesUtile::generateReferenceNumber('sell');
            $main_establishment = Establishment::notMain()->active()->first();
            $establishment_id = $request->establishment_id;
            if ($request->establishment_id == $main_establishment->id) {
                $establishment_id = $main_establishment->id;
            }
            $created_by = Employee::find($request->user_id);
            if (!$created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }
            $transaction =   Transaction::create([
                'type' => 'sell',
                'invoice_type' => $request->payment_status,
                'due_date' => null,
                'transaction_date' => Carbon::createFromFormat('d/m/Y H:i', $request->created_at)->format('Y-m-d H:i:s'),
                'contact_id' => $request->customer_id,
                // 'cost_center' => $request->cost_center ?? null,
                'discount_amount' => $request->discount_value,
                'discount_type' => $request->discount_type,
                'total_before_tax' => $request->total_before_discount,
                'total_after_discount' => $request->total_after_discount,
                'tax_amount' => $request->total_tax,
                'final_total' => $request->total_paid,
                'created_by' => $request->user_id,
                'description' => $request->note,
                'ref_no' => $ref_no,
                'status' => $request->status,
                'notice' => null,
                'invoice_no' => $request->invoice_number,
                'shift_number' => $request->shift_id,
                'establishment_id' => $establishment_id,
            ]);


            $products = json_decode(json_encode($request->items));

            foreach ($products as $product) {
                $find_product = Product::find($product->product_id);
                if (!$find_product) {
                    return response()->json(['message' => 'Product not found id =' . $product->product_id], 404);
                }

                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->product_id,
                    'qyt' => $product->quantity,
                    'unit_price_before_discount' => $product->price_after_discount,
                    'unit_price' => $product->price,
                    'discount_type' => $product->discount_type,
                    'discount_amount' => $product->discount_amount,
                    'unit_price_inc_tax' => $product->price_with_tax_after_discount,
                    'tax_id' => $product->tax_id,
                    'tax_value' => $product->tax_value,
                ]);

                $modifiers = json_decode(json_encode($product->order_item_modifiers));

                foreach ($modifiers as $modifier) {
                    $find_product = Product::find($modifier->modifier_id);
                    if (!$find_product) {
                        return response()->json(['message' => 'Modifier not found id =' . $modifier->modifier_id], 404);
                    }

                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $modifier->modifier_id,
                        'qyt' => $modifier->quantity,
                        'unit_price_before_discount' => $modifier->price,
                        'unit_price' => $modifier->price,
                        'discount_type' => $modifier->discount_type,
                        'discount_amount' => $modifier->discount_amount,
                        'unit_price_inc_tax' => $modifier->price_with_tax,
                        // 'tax_id' => $product->tax_id,
                        'tax_value' => $modifier->tax_value,
                    ]);
                }

                $order_item_combos = json_decode(json_encode($product->order_item_combos));

                foreach ($order_item_combos as $order_item_combo) {
                    $find_product = ProductCombo::where('product_id', $order_item_combo->option_id)->first();
                    if (!$find_product) {
                        return response()->json(['message' => 'Modifier not found id =' . $order_item_combo->option_id], 404);
                    }

                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $find_product->product_id,
                        'qyt' => $order_item_combo->quantity,
                        'unit_price_before_discount' => $order_item_combo->price,
                        'unit_price' => $order_item_combo->price,
                        'discount_type' => null,
                        'discount_amount' => null,
                        'unit_price_inc_tax' => null,
                        // 'tax_id' => $product->tax_id,
                        'tax_value' => null,
                    ]);
                }
            }

            $payments = json_decode(json_encode($request->payments));
            foreach ($payments as $payment) {

                $find_payment = PaymentMethod::find($payment->method_id);
                if (!$find_payment) {
                    return response()->json(['message' => 'Payment method not found id =' . $payment->method_id], 404);
                }

                if ($payment->amount) {
                    $request['paid_amount'] = $payment->amount;
                    $request['payment_method_id'] = $payment->method_id;
                    $request['invoice_type'] = $request->method;


                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
            }
            $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();
            return response()->json(['message' => 'Added successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'something went wrong \n' . $e], 500);
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
