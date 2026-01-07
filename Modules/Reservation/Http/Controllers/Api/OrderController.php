<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\General\Models\PaymentMethod;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductCombo;
use Modules\Reservation\Events\OrderCreated;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderItem;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;
use Modules\Sales\Utils\SalesUtile;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('reservation::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('reservation::create');
    }

    // public function storeApi(Request $request)
    // {
    //     $validated = $request->validate([
    //         'table_id' => 'required|exists:reservation_tables,id',
    //         'order_status' => 'required|string',
    //         'items' => 'required|array|min:1',
    //         'items.*.item_id' => 'required|numeric',
    //         'items.*.quantity' => 'required|numeric|min:1',
    //         'items.*.item_price' => 'required|numeric|min:0',
    //         'customer_name' => 'required|string',
    //         'customer_phone' => 'required|string',
    //         'reservation_time' => 'required|date',
    //         'guests_count' => 'required|integer|min:1',
    //     ]);

    //     return DB::transaction(function () use ($validated, $request) {

    //         $table = Table::findOrFail($validated['table_id']);

    //         if ($table->table_status != 0) {
    //             abort(409, 'Table not available for reservation');
    //         }

    //         $reservation = Reservation::create([
    //             'table_id' => $table->id,
    //             'customer_name' => $validated['customer_name'],
    //             'customer_phone' => $validated['customer_phone'],
    //             'reservation_time' => $validated['reservation_time'],
    //             'guests_count' => $validated['guests_count'],
    //             'status' => 'active',
    //         ]);

    //         $table->update([
    //             'table_status' => 2,
    //             // 'assigned_waiter_id' => Auth::user()->id

    //         ]);

    //         $order = Order::create([
    //             'no' => $this->generateOrdNo(),
    //             'order_date' => now(),
    //             'order_status' => $validated['order_status'],
    //             'table_id' => $table->id,
    //             'establishment_id' => $table->area->establishment_id,
    //         ]);

    //         foreach ($validated['items'] as $item) {
    //             OrderItem::create([
    //                 'order_id' => $order->id,
    //                 'item_id' => $item['item_id'],
    //                 'quantity' => $item['quantity'],
    //                 'item_price' => $item['item_price'],
    //                 'item_total_price' => $item['quantity'] * $item['item_price'],
    //             ]);
    //         }

    //         // event(new OrderCreated($order));

    //     });
    // }

    public function storeApi(Request $request)
    {
        try {
            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();

            $created_by = Employee::find($request->created_by);
            if (!$created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            $table = Table::findOrFail($request->table_id);

            if ($table->table_status != 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Table not available for reservation'
                ], 409);
            }

            $reservation = Reservation::create([
                'table_id' => $table->id,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone ?? null,
                'reservation_time' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                'guests_count' => $request->guests_count,
                'status' => 'active',
            ]);

            $table->update([
                'table_status' => 2,
                'assigned_waiter_id' => $request->created_by
            ]);

            $transaction =   Transaction::create([
                'type' => 'sell',
                'invoice_type' => 'cash',
                'due_date' => null,
                'local_id' => 'table_order',
                'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                'discount_amount' => $request->discount_value,
                'discount_type' => $request->discount_type,
                'total_before_tax' => $request->total_before_discount,
                'total_after_discount' => $request->total_after_discount,
                'tax_amount' => $request->total_tax,
                'final_total' => $request->total_paid,
                'created_by' => $request->created_by,
                'description' => $request->note,
                'ref_no' => $this->generateOrdNo(),
                'status' => 'draft',
                'notice' => null,
                'establishment_id' => $table->area->establishment_id,

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
                    $find_product = ProductCombo::where('id', $order_item_combo->combo_group_id)->first();
                    if (!$find_product) {
                        return response()->json(['message' => 'Combo not found id =' . $order_item_combo->combo_group_id], 404);
                    }

                    TransactionSellLine::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $find_product->product_id,
                        'qyt' => $find_product->quantity,
                        'unit_price_before_discount' => $find_product->price,
                        'unit_price' => $find_product->price,
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

                $request['payment_method_id'] = $request->method;
                $request['created_by'] = $request->user_id;
                if ($payment->amount) {
                    $request['paid_amount'] = $payment->amount;
                    $request['payment_method_id'] = $payment->method_id;
                    $request['invoice_type'] = $request->method;


                    $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                }
            }
            $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);



            DB::commit();
            try {
                $tenant = tenancy()->tenant;
                $tenantId = $tenant->id;

                // $tenantId = $table->area->establishment_id;
                $response = \Illuminate\Support\Facades\Http::post("http://172.31.80.61:3001/broadcast", [
                    'tenant_id' => $tenantId,
                    'event' => 'TableUpdated',
                    'data' => ['table_id' => $table->id]
                ]);
            } catch (\Exception $e) {
            }
            return response()->json([
                'status' => true,
                'order_id' => $transaction->id,
                'order_no' => $transaction->ref_no
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'something went wrong \n' . $e], 500);
        }
    }

    public function generateOrdNo()
    {
        $prefix = 'ORD';
        // Get the last invoice number (if any)
        $lastOrd = Transaction::where('local_id', 'table_order')->orderBy('id', 'desc')->first();

        // Check if there is a previous invoice
        $newOrdNumber = $prefix . '000001';  // Default starting number
        if ($lastOrd) {
            // Extract the number part from the last invoice
            preg_match('/(\d+)/', $lastOrd->no, $matches);
            $lastNumber = (int)$matches[0];
            $newOrdNumber = $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }

        return $newOrdNumber;
    }
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('reservation::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('reservation::edit');
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
