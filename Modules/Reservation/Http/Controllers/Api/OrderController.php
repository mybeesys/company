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
use Modules\Product\Models\TypesOfService;
use Modules\Product\Models\Transformers\Collections\ProductCollection;

class OrderController extends Controller
{

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
                        $mainItem = OrderTableItems::create([
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
                                'modifier_id' => $modifier->modifier_id,
                                'product_id' => $modifier->modifier_id,
                                'parent_id' => $mainItem->id,
                                'qyt' => $modifier->quantity,
                                'unit_price_before_discount' => $modifier->price,
                                'unit_price' => $modifier->price,
                                'discount_type' => $modifier->discount_type,
                                'discount_amount' => $modifier->discount_amount,
                                'unit_price_inc_tax' => $modifier->price_with_tax,
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
                                'combo_id' => $order_item_combo->option_id,
                                'product_id' => $find_product->product_id,
                                'parent_id' => $mainItem->id,
                                'qyt' => $find_product->quantity,
                                'unit_price_before_discount' => $find_product->price,
                                'unit_price' => $find_product->price,
                                'discount_type' => null,
                                'discount_amount' => null,
                                'unit_price_inc_tax' => null,
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

                $transaction = TableOrders::create([
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
                    'order_type' => $request->order_type ?? 1
                ]);

                $products = json_decode(json_encode($request->items));
                foreach ($products as $product) {
                    $find_product = Product::find($product->product_id);
                    if (!$find_product) {
                        return response()->json(['message' => 'Product not found id =' . $product->product_id], 404);
                    }

                    $mainItem = OrderTableItems::create([
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
                            'modifier_id' => $modifier->modifier_id,
                            'product_id' => $modifier->modifier_id,
                            'parent_id' => $mainItem->id,
                            'qyt' => $modifier->quantity,
                            'unit_price_before_discount' => $modifier->price,
                            'unit_price' => $modifier->price,
                            'discount_type' => $modifier->discount_type,
                            'discount_amount' => $modifier->discount_amount,
                            'unit_price_inc_tax' => $modifier->price_with_tax,
                            'tax_value' => $modifier->tax_value,
                        ]);
                    }

                    $order_item_combos = json_decode(json_encode($product->order_item_combos));
                    foreach ($order_item_combos as $order_item_combo) {
                        $find_product = ProductCombo::where('id', $order_item_combo->combo_group_id)->first();
                        if (!$find_product) {
                            return response()->json(['message' => 'Combo not found id =' . $order_item_combo->combo_group_id], 404);
                        }
                        // option
                        OrderTableItems::create([
                            'transaction_id' => $transaction->id,
                            'combo_id' => $order_item_combo->option_id,
                            'product_id' => $find_product->product_id,
                            'parent_id' => $mainItem->id,
                            'qyt' => $find_product->quantity,
                            'unit_price_before_discount' => $find_product->price,
                            'unit_price' => $find_product->price,
                            'discount_type' => null,
                            'discount_amount' => null,
                            'unit_price_inc_tax' => null,
                            'tax_value' => null,
                        ]);
                    }
                }
            }

            DB::commit();

            try {
                $tenantId = (string) tenancy()->tenant->id;
                \Illuminate\Support\Facades\Http::timeout(2)->post("http://127.0.0.1:3001/broadcast", [
                    'tenant_id' => $tenantId,
                    'event' => 'TableUpdated',
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

    public function cancelOrder(Request $request)
    {
        $actionUtil = new ActionUtil();
        $contactUtils = new ContactUtils();
        $accountUtil = new AccountingUtil();
        $ref_no = SalesUtile::generateReferenceNumber('sell');
        $transactionUtil = new TransactionUtils();

        $order = TableOrders::find($request->id);

        if (!$order) {
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
            'order_status' => 'canceled'
        ]);

        Reservation::where('table_id', $table->id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        $transaction = Transaction::create([
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
            'status' => 'approved',
            'notice' => $order->notice,
            'shift_number' => $request->shift_id,
            'establishment_id' => $order->establishment_id,
        ]);

        $order->sell_lines->map(function ($item) use ($transaction) {
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

        if (isset($request->payments) && is_array($request->payments)) {
            $payments = json_decode(json_encode($request->payments));
            foreach ($payments as $payment) {
                $find_payment = PaymentMethod::find($payment->method_id);
                if (!$find_payment) {
                    return response()->json(['message' => 'Payment method not found id =' . $payment->method_id], 404);
                }

                if ($payment->amount > 0) {
                    $payment_data = [
                        'paid_amount' => $payment->amount,
                        'payment_method_id' => $payment->method_id,
                    ];
                    $transactionUtil->createOrUpdatePaymentLines($transaction, $payment_data);
                }
            }
            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
        }

        return response()->json([
            'message' => 'done'
        ], 200);
    }

    public function establishmentOrders(Request $request, $id)
    {
        $type = $request->query('type');
        $orders = TableOrders::where('establishment_id', $id)
            ->where('order_status', '<>', 'canceled')
            ->with(['sell_lines.product', 'createdBy'])
            ->when($type, function ($query, $type) {
                return $query->where('order_type', $type);
            })
            ->get();

        $formattedOrders = $orders->map(function ($order) {
            $reservation = Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')->first();

            $allLines = $order->sell_lines;
            $parentItems = $allLines->where('parent_id', null);

            $service = TypesOfService::find($order->order_type);

            return [
                'id' => $order->id,
                'table_id' => $order->table_id,
                'customer_name' => $reservation->customer_name ?? 'Guest',
                'ref_no' => $order->ref_no,
                'status' => $order->status,
                'invoice_type' => $order->invoice_type,
                'transaction_date' => $order->transaction_date,
                'discount_amount' => $order->discount_amount,
                'discount_type' => $order->discount_type,
                'total_before_tax' => $order->total_before_tax,
                'total_after_discount' => $order->total_after_discount,
                'created_by' => $order->created_by,
                'description' => $order->description,
                'tax_amount' => $order->tax_amount,
                'order_status' => $order->order_status,
                'order_type' => $service->name_ar ?? 'محلي',
                'payment_status' => $order?->payment_status,
                'invoice_created' => !empty($order->id),
                'invoice_id' => $order->id,
                'paid_amount' => $order?->payment?->sum('amount') ?? 0,
                'total_amount' => $order->final_total ?? 0,
                'items' => $parentItems->map(function ($mainItem) use ($allLines) {
                    $subItems = $allLines->where('parent_id', $mainItem->id);
                    return [
                        'product_id' => $mainItem->product_id,
                        'product_name' => $mainItem->product->name_ar ?? '',
                        'quantity' => (float)$mainItem->qyt,
                        'price' => (float)$mainItem->unit_price,
                        'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                            return [
                                'modifier_id' => $mod->product_id,
                                'modifier_name' => $mod->product->name_ar ?? '',
                                'quantity' => (float)$mod->qyt,
                                'price' => (float)$mod->unit_price,
                            ];
                        })->values(),
                        'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                            return [
                                'option_id' => $combo->combo_id,
                                'option_name' => $combo->productCombo->name_ar ?? '',
                                'price' => (float)$combo->unit_price,
                            ];
                        })->values(),
                    ];
                })->values()
            ];
        });

        return response()->json($formattedOrders);
    }

    public function orders(Request $request)
    {
        $type = $request->query('type');
        $orders = TableOrders::with(['sell_lines.product', 'createdBy'])
            ->when($type, function ($query, $type) {
                return $query->where('order_type', $type);
            })
            ->get();

        $formattedOrders = $orders->map(function ($order) {
            $reservation = Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->first();

            $allLines = $order->sell_lines;
            $parentItems = $allLines->where('parent_id', null);

            $service = TypesOfService::find($order->order_type);

            return [
                'id' => $order->id,
                'table_id' => $order->table_id,
                'created_by' => $order->created_by,
                'order_type' => $service->name_ar ?? 'محلي',
                'order_status' => $order->order_status,
                'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
                'customer_name' => $reservation->customer_name ?? 'Guest',
                'customer_phone' => $reservation->customer_phone ?? '',
                'guests_count' => $reservation->guests_count ?? 0,
                'discount_type' => $order->discount_type,
                'discount_value' => (float)$order->discount_amount,
                'total_before_discount' => (float)$order->total_before_tax,
                'total_after_discount' => (float)$order->total_after_discount,
                'total_tax' => (float)$order->tax_amount,
                'total_paid' => (float)$order->final_total,
                'note' => $order->description,
                'items' => $parentItems->map(function ($mainItem) use ($allLines) {
                    $subItems = $allLines->where('parent_id', $mainItem->id);
                    return [
                        'id' => $mainItem->id,
                        'order_id' => $mainItem->transaction_id,
                        'product_id' => $mainItem->product_id,
                        'product_name' => $mainItem->product->name_ar ?? '',
                        'quantity' => (float)$mainItem->qyt,
                        'price' => (float)$mainItem->unit_price,
                        'price_with_tax' => (float)$mainItem->unit_price_inc_tax,
                        'tax_id' => $mainItem->tax_id,
                        'tax_value' => (float)$mainItem->tax_value,
                        'discount_type' => $mainItem->discount_type,
                        'discount_amount' => (float)$mainItem->discount_amount,
                        'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                            return [
                                'id' => $mod->id,
                                'modifier_id' => $mod->product_id,
                                'modifier_name' => $mod->product->name_ar ?? '',
                                'quantity' => (float)$mod->qyt,
                                'price' => (float)$mod->unit_price,
                                'price_with_tax' => (float)$mod->unit_price_inc_tax,
                            ];
                        })->values(),
                        'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                            return [
                                'id' => $combo->id,
                                'option_id' => $combo->combo_id,
                                'option_name' => $combo->productCombo->name_ar ?? '',
                                'price' => (float)$combo->unit_price,
                                'combo_group_id' => $combo->product_id,
                            ];
                        })->values(),
                    ];
                })->values()
            ];
        });

        return response()->json($formattedOrders);
    }

    public function updateOrders(Request $request, $id)
    {
        $order = TableOrders::find($id);
        if (!$order) {
            return response()->json([
                'message' => 'no order with given id'
            ], 409);
        }
        $order->update([
            'order_status' => $request->status
        ]);
        return response()->json([
            'message' => $order
        ], 200);
    }

    public function typesOfService()
    {
        $typesOfService = TypesOfService::all();
        $formattedData = $typesOfService->map(function ($service) {
            return [
                'id' => $service->id,
                'name_en' => $service->name_en,
                'name_ar' => $service->name_ar,
                'description' => $service->description,
                'packing_charge' => (float) $service->packing_charge,
                'packing_charge_type' => $service->packing_charge_type,
            ];
        });
        return response()->json($formattedData);
    }

    public function destroy($id)
    {
        //
    }
}
