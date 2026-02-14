<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
use Modules\Reservation\Models\OrderTableItems;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Models\TableOrders;
use Modules\Sales\Utils\SalesUtile;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\General\Models\Actions;
use Modules\General\Models\Country;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\ActionUtil;
use Modules\Inventory\Models\Transfer;
use Modules\Product\Http\Controllers\Api\ProductController;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\Transformers\Collections\ProductCollection;

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

        //
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

            if (isset($request->order_id)) {
                $transaction = Transaction::where('table_order_id', $request->order_id)->first();
                if ($transaction) {
                    $transaction->update([
                        'discount_amount' => $request->discount_value,
                        'discount_type' => $request->discount_type,
                        'total_before_tax' => $request->total_before_discount,
                        'total_after_discount' => $request->total_after_discount,
                        'tax_amount' => $request->total_tax,
                        'final_total' => $request->total_paid,
                        'created_by' => $request->created_by,
                        'description' => $request->note,
                    ]);


                    $products = json_decode(json_encode($request->items));

                    foreach ($products as $product) {
                        $find_product = Product::find($product->product_id);
                        if (!$find_product) {
                            return response()->json(['message' => 'Product not found id =' . $product->product_id], 404);
                        }
                        OrderTableItems::create([
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

                            OrderTableItems::create([
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

                            OrderTableItems::create([
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
                }
            } else {
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

                $transaction =   TableOrders::create([
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
                    'table_id' => $table->id,
                    'order_status' => 'inpreparation',

                ]);


                $products = json_decode(json_encode($request->items));

                foreach ($products as $product) {
                    $find_product = Product::find($product->product_id);
                    if (!$find_product) {
                        return response()->json(['message' => 'Product not found id =' . $product->product_id], 404);
                    }

                $mainItem =    OrderTableItems::create([
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

                        OrderTableItems::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $modifier->modifier_id,
                            'parent_id'      => $mainItem->id,
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
 
                        OrderTableItems::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $find_product->product_id,
                          'parent_id'      => $mainItem->id,
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
            }


            // $payments = json_decode(json_encode($request->payments));
            // foreach ($payments as $payment) {

            //     $find_payment = PaymentMethod::find($payment->method_id);
            //     if (!$find_payment) {
            //         return response()->json(['message' => 'Payment method not found id =' . $payment->method_id], 404);
            //     }

            //     $request['payment_method_id'] = $request->method;
            //     $request['created_by'] = $request->user_id;
            //     if ($payment->amount) {
            //         $request['paid_amount'] = $payment->amount;
            //         $request['payment_method_id'] = $payment->method_id;
            //         $request['invoice_type'] = $request->method;


            //         $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
            //     }
            // }
            // $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);



            DB::commit();

            try {
                $tenantId = (string) tenancy()->tenant->id;

                \Illuminate\Support\Facades\Http::timeout(2)->post("http://127.0.0.1:3001/broadcast", [
                    'tenant_id' => $tenantId,
                    'event'     => 'TableUpdated',
                    'data' => [
                        'table_id' => $table->id,
                        'table_code' => $table->code,
                        'transaction_ref_no' => $transaction->ref_no
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error("Socket Error: " . $e->getMessage());
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

        $lastOrd = TableOrders::where('local_id', 'table_order')
            ->whereNotNull('ref_no')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;

        if ($lastOrd && preg_match('/(\d+)/', $lastOrd->ref_no, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return $prefix . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Show the specified resource.
     */
    public function cancelOrder(Request $request)
    {
        // try{

          $actionUtil = new ActionUtil();
        $contactUtils = new ContactUtils();
        $accountUtil = new AccountingUtil();
         $ref_no =  SalesUtile::generateReferenceNumber('sell');
    $transactionUtil = new TransactionUtils();
         
            $order = TableOrders::find($request->id);

      if(!$order){
      return response()->json([
                    'message' => 'No Order with given id'
                ], 409);
      }
          
             $table = Table::find($order->table_id);
             $table->update([
                    'table_status' => 0,
                    'assigned_waiter_id' => null
                ]);
$order->update([
    'order_status'=>'served'
]);

             $transaction =   Transaction::create([
            'type' => 'sell',
            'invoice_type' => $order->invoice_type,
            'due_date' => $order->due_date,
            'transaction_date' => $order->transaction_date,
            'contact_id' => $order->contact_id,
            'cost_center' => $order->cost_center ?? null,
            'discount_amount' => $order->discount_amount,
            'discount_type' => $order->discount_type,
            'total_before_tax' => $order->total_before_tax,
            'totalAfterDiscount' => $order->totalAfterDiscount,
            'tax_amount' => $order->tax_amount,
            'final_total' => $order->final_total,
            'created_by' => $order->created_by,
            'description' => $order->description,
            'ref_no' => $ref_no,
            'status' => $order->status,
            'notice' => $order->notice,
            'establishment_id' => $order->establishment_id,
            // 'settings_terms_notes' => $order,

        
        ]);

        $order->sell_lines->map(function ($item) use ($transaction){
     TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'qyt' => $item->qyt,
                'unit_id' => $item->unit_id,
                'unit_price_before_discount' => $item->unit_price_before_discount,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type,
                'discount_amount' => $item->discount_amount,
                'unit_price_inc_tax' => $item->unit_price_inc_tax,
                'tax_id' => $item->tax_id,
                'tax_value' => $item->tax_value,
                'total_before_vat' => $item->total_before_vat,
            ]);
        });


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

    
        // if ($request->paid_amount) {
        //     if ($transaction->final_total == $request->paid_amount) {
        //         $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        //     } else {
        //         $this->createPaymentLines($transaction, $request);
        //     }
        // } else {
        //     $acc_trans_mapping = new AccountingAccTransMapping();
        //     $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
        //     $acc_trans_mapping->ref_no = $ref_number;
        //     $acc_trans_mapping->note = '';
        //     $acc_trans_mapping->type = 'journal_entry';
        //     $acc_trans_mapping->created_by = Auth::user()->id;
        //     $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
        //     $acc_trans_mapping->save();
        //     $acc_trans_mapping_id = $acc_trans_mapping->id;

        //     $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
        //     $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

        //     $client = Contact::find($request->client_id);
        //     $transactionPayment = new \stdClass();

        //     $transactionPayment->paid_on = Carbon::parse(now())->format('Y-m-d H:i:s');
        //     $transactionPayment->account_id = $client->account_id;
        //     $transactionPayment->created_by = Auth::user()->id;
        //     $transactionPayment->created_by = Auth::user()->id;
        //     $transactionPayment->transaction_id = $transaction->id;
        //     $transactionPayment->id = null;

        //     $transactionPayment->amount = $transaction->final_total;

        //     $accountUtil->saveAccountRouteTransaction(
        //         'debit',
        //         $transactionPayment,
        //         $transaction,
        //         $acc_trans_mapping_id,
        //         $request
        //     );

        //     $transactionPayment->account_id = $sales_sales->account_id;
        //     $transactionPayment->amount = $transaction->total_before_tax;

        //     $accountUtil->saveAccountRouteTransaction(
        //         'credit',
        //         $transactionPayment,
        //         $transaction,
        //         $acc_trans_mapping_id,
        //         $request
        //     );

        //     $transactionPayment->account_id = $sales_vat_calculation->account_id;
        //     $transactionPayment->amount = $transaction->tax_amount;

        //     $accountUtil->saveAccountRouteTransaction(
        //         'credit',
        //         $transactionPayment,
        //         $transaction,
        //         $acc_trans_mapping_id,
        //         $request
        //     );
        // }
         if(!$order){
      return response()->json([
                    'message' => 'done'
                ], 200);
      }
//         }catch(Exception $e){
//  return response()->json([
//                     'message' => $e
//                 ], 500);
//         }
    }

       public function createPaymentLines($transaction, $request)
    {
        // dd($transaction, $request);
        $acc_trans_mapping = new AccountingAccTransMapping();

        $accountUtil = new AccountingUtil();
        $cash_account_id = $request->account_id;
        $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
        $acc_trans_mapping->ref_no = $ref_number;
        $acc_trans_mapping->note = '';
        $acc_trans_mapping->type = 'journal_entry';
        $acc_trans_mapping->created_by = Auth::user()->id;
        $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
        $acc_trans_mapping->save();
        $acc_trans_mapping_id = $acc_trans_mapping->id;

        $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
        $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

        $payment_method_id = null;

        if (!$request->has('payment_method_id')) {
            $payment_method_id = $request->payment_method_id;
        }
        $date = Carbon::parse($request->payment_on);
        $payment_on = $date->format('Y-m-d H:i:s');
        $transactionUtil = new TransactionUtils();
        $prefix_type = $transaction->type == 'purchase' ? 'purchase_payment' : 'sell_payment';

        $payment_ref_no = $transactionUtil->generateReferenceNumber($prefix_type);

        $client = Contact::find($request->client_id);
        $transactionPayment = TransactionPayments::create([
            'transaction_id' => $transaction->id,
            'payment_type' => $transaction->invoice_type,
            'amount' => $request->paid_amount,
            'method' => 'due',
            'payment_method_id' => $payment_method_id,
            'is_return' => $transaction->type == 'sell-return' ?? 0,
            'note' => $request->additionalNotes,
            'paid_on' => $payment_on,
            'created_by' => Auth::check() ? Auth::user()->id : $request->created_by,
            'payment_for' => $transaction->contact_id,
            'payment_ref_no' => $payment_ref_no,
            'account_id' => $cash_account_id,
        ]);
        $client = Contact::find($transactionPayment->payment_for);

        $transactionPayment->account_id = $client->account_id;

        $transactionPayment->amount = $transaction->final_total - $request->paid_amount;

        $accountUtil->saveAccountRouteTransaction(
            'debit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );


        $transactionPayment->account_id = $cash_account_id;
        $transactionPayment->amount = $request->paid_amount; // $transaction->total_before_tax;

        $accountUtil->saveAccountRouteTransaction(
            'debit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );



        $transactionPayment->account_id = $sales_sales->account_id;
        $transactionPayment->amount = $transaction->total_before_tax;

        $accountUtil->saveAccountRouteTransaction(
            'credit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );



        $transactionPayment->account_id = $sales_vat_calculation->account_id;
        $transactionPayment->amount = $transaction->tax_amount;

        $accountUtil->saveAccountRouteTransaction(
            'credit',
            $transactionPayment,
            $transaction,
            $acc_trans_mapping_id,
            $request
        );
    }
    /**
     * Show the form for editing the specified resource.
     */
   public function establishmentOrders($id)
{
    $orders = TableOrders::where('establishment_id', $id)
        ->where('order_status', '<>', 'served')
        ->with(['sell_lines.product', 'createdBy'])
        ->get();

    $formattedOrders = $orders->map(function ($order) {
        $reservation = Reservation::where('table_id', $order->table_id)
            ->where('status', 'active')->first();

        $allLines = $order->sell_lines;
        $parentItems = $allLines->where('parent_id', null);

        return [
            'id'               => $order->id,
            'table_id'         => $order->table_id,
            'customer_name'    => $reservation->customer_name ?? 'Guest',
            
            'items' => $parentItems->map(function ($mainItem) use ($allLines) {
                $subItems = $allLines->where('parent_id', $mainItem->id);

                return [
                    'product_id'   => $mainItem->product_id,
                    'product_name' => $mainItem->product->name_ar ?? '',
                    'quantity'     => (float)$mainItem->qyt,
                    'price'        => (float)$mainItem->unit_price,
                    
                    'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                        return [
                            'modifier_id'   => $mod->product_id,
                            'modifier_name' => $mod->product->name_ar ?? '',
                            'quantity'      => (float)$mod->qyt,
                            'price'         => (float)$mod->unit_price,
                        ];
                    })->values(),

                    'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                        return [
                            'option_id'   => $combo->product_id,
                            'option_name' => $combo->product->name_ar ?? '',
                            'price'       => (float)$combo->unit_price,
                        ];
                    })->values(),
                ];
            })->values()
        ];
    });

    return response()->json($formattedOrders);
}

  public function orders()
{
    $orders = TableOrders::with(['sell_lines.product', 'createdBy'])
        ->get();

    $formattedOrders = $orders->map(function ($order) {
        $reservation = \Modules\Reservation\Models\Reservation::where('table_id', $order->table_id)
            ->where('status', 'active')
            ->first();

        $allLines = $order->sell_lines;

         $parentItems = $allLines->where('parent_id', null);

        return [
            'id'                     => $order->id,
            'table_id'               => $order->table_id,
            'created_by'             => $order->created_by,
            'created_at'             => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
            'customer_name'          => $reservation->customer_name ?? 'Guest',
            'customer_phone'         => $reservation->customer_phone ?? '',
            'guests_count'           => $reservation->guests_count ?? 0,
            'discount_type'          => $order->discount_type,
            'discount_value'         => (float)$order->discount_amount,
            'total_before_discount'  => (float)$order->total_before_tax,
            'total_after_discount'   => (float)$order->total_after_discount,
            'total_tax'              => (float)$order->tax_amount,
            'total_paid'             => (float)$order->final_total,
            'note'                   => $order->description,
            'items' => $parentItems->map(function ($mainItem) use ($allLines) {
                $subItems = $allLines->where('parent_id', $mainItem->id);

                return [
                    'id'                => $mainItem->id,
                    'order_id'          => $mainItem->transaction_id,
                    'product_id'        => $mainItem->product_id,
                    'product_name'      => $mainItem->product->name_ar ?? '',
                    'quantity'          => (float)$mainItem->qyt,
                    'price'             => (float)$mainItem->unit_price,
                    'price_with_tax'    => (float)$mainItem->unit_price_inc_tax,
                    'tax_id'            => $mainItem->tax_id,
                    'tax_value'         => (float)$mainItem->tax_value,
                    'discount_type'     => $mainItem->discount_type,
                    'discount_amount'   => (float)$mainItem->discount_amount,
                    
                    'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                        return [
                            'id'              => $mod->id,
                            'modifier_id'     => $mod->product_id,
                            'modifier_name'   => $mod->product->name_ar ?? '',
                            'quantity'        => (float)$mod->qyt,
                            'price'           => (float)$mod->unit_price,
                            'price_with_tax'  => (float)$mod->unit_price_inc_tax,
                        ];
                    })->values(),

                    'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                        return [
                            'id'              => $combo->id,
                            'combo_group_id'  => $combo->product_id,
                            'option_id'       => $combo->product_id,
                            'option_name'     => $combo->product->name_ar ?? '',
                            'price'           => (float)$combo->unit_price,
                        ];
                    })->values(),
                ];
            })->values()
        ];
    });

    return response()->json($formattedOrders);
}

        public function updateOrders(Request $request,$id)
    {
                  
  
                 $order =  TableOrders::find($id);
                    if(!$order){
                            return response()->json([
                                                'message' => 'no order with given id'
                                            ], 409);
                      }
                 $order->update([
    // 'status'=>$request->status
    'order_status'=>$request->status
    
]);
  return response()->json([
                                                'message' => $order
                                            ], 200);


 
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
