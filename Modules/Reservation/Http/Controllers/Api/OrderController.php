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


    // public function storeApi(Request $request)
    // {
    //     // try {
    //     $transactionUtil = new TransactionUtils();
    //     DB::beginTransaction();

    //     $created_by = Employee::find($request->created_by);
    //     if (!$created_by) {
    //         return response()->json(['message' => 'Employee not found'], 404);
    //     }

    //     $table = Table::findOrFail($request->table_id);

    //     if (!isset($request->order_id) && $table->table_status != 0) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Table not available for reservation'
    //         ], 409);
    //     }

    //     $transaction = null;

    //     if (isset($request->order_id)) {
    //         $transaction = TableOrders::find($request->order_id);
    //         if ($transaction) {
    //             $transaction->update([
    //                 'discount_amount' => $request->discount_value,
    //                 'discount_type' => $request->discount_type,
    //                 'total_before_tax' => $request->total_before_discount,
    //                 'total_after_discount' => $request->total_after_discount,
    //                 'tax_amount' => $request->total_tax,
    //                 'final_total' => $request->total_paid,
    //                 'created_by' => $request->created_by,
    //                 'description' => $request->note,
    //             ]);

    //             $this->saveOrderItems($transaction, $request->items);
    //         }
    //     } else {
    //         $reservation = Reservation::create([
    //             'table_id' => $table->id,
    //             'customer_name' => $request->customer_name,
    //             'customer_phone' => $request->customer_phone ?? null,
    //             'reservation_time' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
    //             'guests_count' => $request->guests_count,
    //             'status' => 'active',
    //         ]);

    //         $table->update([
    //             'table_status' => 2,
    //             'assigned_waiter_id' => $request->created_by
    //         ]);

    //         $transaction = TableOrders::create([
    //             'type' => 'sell',
    //             'invoice_type' => 'cash',
    //             'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
    //             'discount_amount' => $request->discount_value,
    //             'discount_type' => $request->discount_type,
    //             'total_before_tax' => $request->total_before_discount,
    //             'total_after_discount' => $request->total_after_discount,
    //             'tax_amount' => $request->total_tax,
    //             'final_total' => $request->total_paid,
    //             'created_by' => $request->created_by,
    //             'description' => $request->note,
    //             'ref_no' => $this->generateOrdNo(),
    //             'status' => 'draft',
    //             'establishment_id' => $table->area->establishment_id,
    //             'table_id' => $table->id,
    //             'order_status' => 'inpreparation',
    //             'order_type' => $request->order_type ?? 1,
    //             'local_id' => 'table_order'
    //         ]);

    //         $this->saveOrderItems($transaction, $request->items);
    //     }

    //     if (isset($request->payments) && is_array($request->payments) && count($request->payments) > 0) {
    //         $finalTransaction = $this->finalizeOrderToTransaction($transaction, $request);

    //         DB::commit();
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Order canceled and paid successfully',
    //             'transaction_id' => $finalTransaction->id,
    //             'ref_no' => $finalTransaction->ref_no
    //         ]);
    //     }

    //     DB::commit();

    //     $this->broadcastTableUpdate($table, $transaction);

    //     return response()->json([
    //         'status' => true,
    //         'order_id' => $transaction->id,
    //         'order_no' => $transaction->ref_no
    //     ]);
    //     // } catch (Exception $e) {
    //     //     DB::rollBack();
    //     //     Log::error("Store Error: " . $e->getMessage());
    //     //     return response()->json(['message' => 'something went wrong', 'error' => $e->getMessage()], 500);
    //     // }
    // }


    public function storeApi(Request $request)
    {
        try {
            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();

            $created_by = Employee::find($request->created_by);
            if (!$created_by) return response()->json(['message' => 'Employee not found'], 404);

            $table = Table::findOrFail($request->table_id);

            // البحث عن طلب نشط حالي على هذه الطاولة
            $existingOrder = TableOrders::where('table_id', $table->id)
                ->whereNotIn('order_status', ['canceled', 'completed'])
                ->first();

            $isNewRequestPaid = isset($request->payments) && count($request->payments) > 0;

            if ($existingOrder) {
                // الحالة 1: الطلب القديم "مدفوع" (محول لمعاملة) والجديد قادم
                // ملاحظة: في نظامك الطلب المدفوع يتحول لـ Transaction، لكن لو لسه كـ TableOrder وحالته مدفوع:
                if ($existingOrder->payment_status == 'paid' || $existingOrder->order_status == 'served') {
                    // تحويل القديم لـ canceled كما طلبت
                    $existingOrder->update(['order_status' => 'canceled']);
                    // إنهاء حجز الطاولة القديم لفتح واحد جديد
                    Reservation::where('table_id', $table->id)->where('status', 'active')->update(['status' => 'canceled']);
                    $table->update(['table_status' => 0]);

                    // الآن سيكمل الكود لإنشاء طلب جديد تماماً
                }
                // الحالة 3: القديم غير مدفوع والجديد مدفوع -> مرفوض
                elseif ($existingOrder->payment_status != 'paid' && $isNewRequestPaid) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن إضافة طلب مدفوع على طاولة بها طلبات سابقة غير مدفوعة. يرجى تسوية الحساب أولاً.'
                    ], 422);
                }
                // الحالة 2: القديم غير مدفوع والجديد غير مدفوع -> دمج
                else {
                    $request->merge(['order_id' => $existingOrder->id]);
                }
            }

            // تنفيذ عملية التخزين (إضافة أو جديد)
            $transaction = null;
            if (isset($request->order_id)) {
                $transaction = TableOrders::find($request->order_id);
                $transaction->update([
                    'total_before_tax' => $request->total_before_discount,
                    'total_after_discount' => $request->total_after_discount,
                    'tax_amount' => $request->total_tax,
                    'final_total' => $request->total_paid,
                ]);
                $this->saveOrderItems($transaction, $request->items);
            } else {
                // إنشاء حجز وطلب جديد
                $reservation = Reservation::create([
                    'table_id' => $table->id,
                    'customer_name' => $request->customer_name ?? 'Guest',
                    'reservation_time' => now(),
                    'guests_count' => $request->guests_count ?? 1,
                    'status' => 'active',
                ]);

                $table->update(['table_status' => 2, 'assigned_waiter_id' => $request->created_by]);

                $transaction = TableOrders::create([
                    'type' => 'sell',
                    'invoice_type' => 'cash',
                    'transaction_date' => now(),
                    'final_total' => $request->total_paid,
                    'ref_no' => $this->generateOrdNo(),
                    'status' => 'draft',
                    'establishment_id' => $table->area->establishment_id,
                    'table_id' => $table->id,
                    'order_status' => 'inpreparation',
                    'local_id' => 'table_order'
                ]);
                $this->saveOrderItems($transaction, $request->items);
            }

            // المعالجة النهائية إذا كان هناك دفع
            if ($isNewRequestPaid) {
                $finalTransaction = $this->finalizeOrderToTransaction($transaction, $request);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Order processed and finalized', 'transaction_id' => $finalTransaction->id]);
            }

            DB::commit();
            $this->broadcastTableUpdate($table, $transaction);

            return response()->json(['status' => true, 'order_id' => $transaction->id]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

    private function saveOrderItems($transaction, $items)
    {
        $products = json_decode(json_encode($items));
        foreach ($products as $product) {
            // تسجيل الصنف الرئيسي
            $mainItem = OrderTableItems::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->product_id,
                'qyt' => $product->quantity,
                'unit_price_before_discount' => $product->price_after_discount ?? $product->price,
                'unit_price' => $product->price,
                'discount_type' => $product->discount_type ?? null,
                'discount_amount' => $product->discount_amount ?? 0,
                'unit_price_inc_tax' => $product->price_with_tax_after_discount ?? $product->price_with_tax,
                'tax_id' => $product->tax_id ?? null,
                'tax_value' => $product->tax_value ?? 0,
                'line_status' => 'inpreparation',
            ]);

            // تسجيل الإضافات (Modifiers) إن وجدت
            if (isset($product->order_item_modifiers)) {
                foreach ($product->order_item_modifiers as $modifier) {
                    OrderTableItems::create([
                        'transaction_id' => $transaction->id,
                        'modifier_id' => $modifier->modifier_id,
                        'product_id' => $modifier->modifier_id,
                        'parent_id' => $mainItem->id,
                        'qyt' => $modifier->quantity,
                        'unit_price' => $modifier->price,
                        'unit_price_before_discount' => $modifier->price_after_discount ?? $modifier->price,
                        'unit_price_inc_tax' => $modifier->price_with_tax ?? $modifier->price,
                        'line_status' => 'inpreparation',
                    ]);
                }
            }

            // تسجيل الكومبو (Combos) إن وجدت
            if (isset($product->order_item_combos)) {
                foreach ($product->order_item_combos as $combo) {
                    OrderTableItems::create([
                        'transaction_id' => $transaction->id,
                        'combo_id' => $combo->option_id,
                        'product_id' => $combo->product_id ?? $mainItem->product_id,
                        'parent_id' => $mainItem->id,
                        'qyt' => $combo->quantity ?? 1,
                        'unit_price' => $combo->price ?? 0,
                        'unit_price_before_discount' => $combo->price ?? 0,
                        'line_status' => 'inpreparation',
                    ]);
                }
            }
        }
    }

    private function finalizeOrderToTransaction($order, $request)
    {
        $transactionUtil = new TransactionUtils();
        $ref_no = SalesUtile::generateReferenceNumber('sell');

        // تحديث حالة الطاولة والحجز
        $table = Table::find($order->table_id);
        if ($table) {
            $table->update(['table_status' => 0, 'assigned_waiter_id' => null]);
        }

        $order->update(['order_status' => 'served']);

        Reservation::where('table_id', $order->table_id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        // إنشاء المعاملة المالية (Transaction)
        $transaction = Transaction::create([
            'type' => 'sell',
            'invoice_type' => 'cash',
            'transaction_date' => $order->transaction_date,
            'discount_amount' => $order->discount_amount,
            'discount_type' => $order->discount_type,
            'total_before_tax' => $order->total_before_tax,
            'tax_amount' => $order->tax_amount,
            'final_total' => $order->final_total,
            'created_by' => $order->created_by,
            'ref_no' => $ref_no,
            'status' => 'approved',
            'establishment_id' => $order->establishment_id,
            'table_order_id' => $order->id
        ]);

        // نقل كافة الأصناف بما فيها الضرائب والخصومات لكل سطر
        $orderItems = OrderTableItems::where('transaction_id', $order->id)->get();
        foreach ($orderItems as $item) {
            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'qyt' => $item->qyt,
                'unit_price' => $item->unit_price,
                'unit_price_before_discount' => $item->unit_price_before_discount ?? $item->unit_price,
                'unit_price_inc_tax' => $item->unit_price_inc_tax,
                'tax_id' => $item->tax_id,
                'tax_value' => $item->tax_value,
                'parent_id' => $item->parent_id // الحفاظ على علاقة التبعية إذا كانت موجودة
            ]);
        }

        // معالجة الدفعات
        if (isset($request->payments)) {
            foreach ($request->payments as $payment) {
                if ($payment['amount'] > 0) {
                    // دمج بيانات الدفع مع بيانات المستخدم والشفت لضمان عدم حدوث SQL Error
                    $paymentData = array_merge($payment, [
                        'created_by' => $order->created_by,
                        'shift_id' => $request->shift_id ?? "00000"
                    ]);

                    $transactionUtil->createOrUpdatePaymentLines($transaction, (object)$paymentData);
                }
            }
            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
        }

        return $transaction;
    }
    // private function saveOrderItems($transaction, $items)
    // {
    //     $products = json_decode(json_encode($items));
    //     foreach ($products as $product) {
    //         $mainItem = OrderTableItems::create([
    //             'transaction_id' => $transaction->id,
    //             'product_id' => $product->product_id,
    //             'qyt' => $product->quantity,
    //             'unit_price_before_discount' => $product->price_after_discount ?? $product->price,

    //             'unit_price' => $product->price,
    //             'discount_type' => $product->discount_type,
    //             'discount_amount' => $product->discount_amount,
    //             'unit_price_inc_tax' => $product->price_with_tax_after_discount ?? $product->price_with_tax,
    //             'tax_id' => $product->tax_id,
    //             'tax_value' => $product->tax_value,
    //             'line_status' => 'inpreparation',
    //         ]);

    //         if (isset($product->order_item_modifiers)) {
    //             foreach ($product->order_item_modifiers as $modifier) {
    //                 OrderTableItems::create([
    //                     'transaction_id' => $transaction->id,
    //                     'modifier_id' => $modifier->modifier_id,
    //                     'product_id' => $modifier->modifier_id,
    //                     'parent_id' => $mainItem->id,
    //                     'qyt' => $modifier->quantity,
    //                     'unit_price' => $modifier->price,
    //                     'unit_price_before_discount' => $modifier->price_after_discount ?? $modifier->price,

    //                     'unit_price_inc_tax' => $modifier->price_with_tax ?? $modifier->price,
    //                     'line_status' => 'inpreparation',
    //                 ]);
    //             }
    //         }

    //         if (isset($product->order_item_combos)) {
    //             foreach ($product->order_item_combos as $combo) {
    //                 OrderTableItems::create([
    //                     'transaction_id' => $transaction->id,
    //                     'combo_id' => $combo->option_id,
    //                     'product_id' => $combo->product_id ?? $mainItem->product_id,
    //                     'parent_id' => $mainItem->id,
    //                     'qyt' => $combo->quantity ?? 1,
    //                     'unit_price' => $combo->price ?? 0,
    //                     'unit_price_before_discount' =>  $combo->price ?? 0,

    //                     'line_status' => 'inpreparation',
    //                 ]);
    //             }
    //         }
    //     }
    // }

    // private function finalizeOrderToTransaction($order, $request)
    // {
    //     $transactionUtil = new TransactionUtils();
    //     $ref_no = SalesUtile::generateReferenceNumber('sell');

    //     $table = Table::find($order->table_id);
    //     $table->update(['table_status' => 0, 'assigned_waiter_id' => null]);

    //     $order->update(['order_status' => 'served']);

    //     Reservation::where('table_id', $table->id)
    //         ->where('status', 'active')
    //         ->update(['status' => 'completed']);

    //     $transaction = Transaction::create([
    //         'type' => 'sell',
    //         'invoice_type' => 'cash',
    //         'transaction_date' => $order->transaction_date,
    //         'discount_amount' => $order->discount_amount,
    //         'discount_type' => $order->discount_type,
    //         'total_before_tax' => $order->total_before_tax,
    //         'tax_amount' => $order->tax_amount,
    //         'final_total' => $order->final_total,
    //         'created_by' => $order->created_by,
    //         'ref_no' => $ref_no,
    //         'status' => 'approved',
    //         'establishment_id' => $order->establishment_id,
    //         'table_order_id' => $order->id
    //     ]);

    //     $orderItems = OrderTableItems::where('transaction_id', $order->id)->get();
    //     foreach ($orderItems as $item) {
    //         TransactionSellLine::create([
    //             'transaction_id' => $transaction->id,
    //             'product_id' => $item->product_id,
    //             'qyt' => $item->qyt,
    //             'unit_price' => $item->unit_price,
    //             'unit_price_before_discount' => $item->unit_price ?? 0,

    //             'unit_price_inc_tax' => $item->unit_price_inc_tax,
    //             'tax_id' => $item->tax_id,
    //             'tax_value' => $item->tax_value,
    //             'parent_id' => null
    //         ]);
    //     }

    //     if (isset($request->payments)) {
    //         foreach ($request->payments as $payment) {
    //             if ($payment['amount'] > 0) {
    //                 $payment['created_by'] = $order->created_by;
    //                 $payment['shift_id'] = "00000";

    //                 $transactionUtil->createOrUpdatePaymentLines($transaction, (object)$payment);
    //             }
    //         }
    //         $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
    //     }

    //     return $transaction;
    // }

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

    private function broadcastTableUpdate($table, $transaction)
    {
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
    }

    public function cancelOrder(Request $request)
    {
        $order = TableOrders::find($request->id);
        if (!$order) return response()->json(['message' => 'No Order found'], 404);

        $this->finalizeOrderToTransaction($order, $request);

        return response()->json(['message' => 'Order closed and converted to transaction'], 200);
    }



    // public function cancelOrder(Request $request)
    // {
    //     $actionUtil = new ActionUtil();
    //     $contactUtils = new ContactUtils();
    //     $accountUtil = new AccountingUtil();
    //     $ref_no = SalesUtile::generateReferenceNumber('sell');
    //     $transactionUtil = new TransactionUtils();

    //     $order = TableOrders::find($request->id);

    //     if (!$order) {
    //         return response()->json([
    //             'message' => 'No Order with given id'
    //         ], 409);
    //     }

    //     $table = Table::find($order->table_id);
    //     $table->update([
    //         'table_status' => 0,
    //         'assigned_waiter_id' => null
    //     ]);

    //     $order->update([
    //         'order_status' => 'canceled'
    //     ]);

    //     Reservation::where('table_id', $table->id)
    //         ->where('status', 'active')
    //         ->update(['status' => 'canceled']);

    //     $transaction = Transaction::create([
    //         'type' => 'sell',
    //         'invoice_type' => $order->invoice_type,
    //         'due_date' => $order->due_date,
    //         'transaction_date' => $order->transaction_date,
    //         'contact_id' => $order->contact_id,
    //         'cost_center' => $order->cost_center ?? null,
    //         'discount_amount' => $order->discount_amount,
    //         'discount_type' => $order->discount_type,
    //         'total_before_tax' => $order->total_before_tax,
    //         'totalAfterDiscount' => $order->totalAfterDiscount,
    //         'tax_amount' => $order->tax_amount,
    //         'final_total' => $order->final_total,
    //         'created_by' => $order->created_by,
    //         'description' => $order->description,
    //         'ref_no' => $ref_no,
    //         'status' => 'approved',
    //         'notice' => $order->notice,
    //         'shift_number' => $request->shift_id,
    //         'establishment_id' => $order->establishment_id,
    //     ]);

    //     $order->sell_lines->map(function ($item) use ($transaction) {
    //         TransactionSellLine::create([
    //             'transaction_id' => $transaction->id,
    //             'product_id' => $item->product_id,
    //             'qyt' => $item->qyt,
    //             'unit_id' => $item->unit_id,
    //             'unit_price_before_discount' => $item->unit_price_before_discount,
    //             'unit_price' => $item->unit_price,

    //             'discount_type' => $item->discount_type,
    //             'discount_amount' => $item->discount_amount,
    //             'unit_price_inc_tax' => $item->unit_price_inc_tax,
    //             'tax_id' => $item->tax_id,
    //             'tax_value' => $item->tax_value,
    //             'total_before_vat' => $item->total_before_vat,
    //         ]);
    //     });

    //     if (isset($request->payments) && is_array($request->payments)) {
    //         $payments = json_decode(json_encode($request->payments));
    //         foreach ($payments as $payment) {
    //             $find_payment = PaymentMethod::find($payment->method_id);
    //             if (!$find_payment) {
    //                 return response()->json(['message' => 'Payment method not found id =' . $payment->method_id], 404);
    //             }

    //             if ($payment->amount > 0) {
    //                 $payment_data = (object) [
    //                     'paid_amount'       => $payment->amount,
    //                     // 'payment_on'        => $payment?->payment_on ,
    //                     'payment_method_id' => $payment->method_id,
    //                 ];
    //                 $transactionUtil->createOrUpdatePaymentLines($transaction, $payment);
    //             }
    //         }
    //         $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
    //     }

    //     return response()->json([
    //         'message' => 'done'
    //     ], 200);
    // }

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
                        'price_with_tax' => (float)$mainItem->unit_price_inc_tax,
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

    public function getFilteredOrdersByCategory(Request $request)
    {
        $category_ids = $request->input('category_ids', []);
        $establishment_id = $request->input('establishment_id');


        if (!Establishment::find($establishment_id) || !$establishment_id) {
            return response()->json(['message' => 'Establishment not found'], 404);
        }

        $orders = TableOrders::where('establishment_id', $establishment_id)

            // where('order_status', '<>', 'canceled')
            ->whereHas('sell_lines.product', function ($query) use ($category_ids) {
                $query->whereIn('category_id', $category_ids);
            })
            ->with(['sell_lines.product', 'createdBy'])
            ->get();

        $formattedOrders = $orders->map(function ($order) use ($category_ids) {
            $reservation = Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->first();

            $allLines = $order->sell_lines;

            $filteredLines = $allLines->filter(function ($line) use ($category_ids) {
                return $line->product && in_array($line->product->category_id, $category_ids);
            });

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
                'items' => $filteredLines->map(function ($mainItem) {
                    return [
                        'id' => $mainItem->id,
                        'order_id' => $mainItem->transaction_id,
                        'product_id' => $mainItem->product_id,
                        'product_name' => $mainItem->product->name_ar ?? '',
                        'category_id' => $mainItem->product->category_id,
                        'quantity' => (float)$mainItem->qyt,
                        'price' => (float)$mainItem->unit_price,
                        'price_with_tax' => (float)$mainItem->unit_price_inc_tax,
                        'tax_id' => $mainItem->tax_id,
                        'tax_value' => (float)$mainItem->tax_value,
                        'discount_type' => $mainItem->discount_type,
                        'discount_amount' => (float)$mainItem->discount_amount,
                        'status' => $mainItem->line_status ?? 'inpreparation',
                    ];
                })->values()
            ];
        });

        return response()->json($formattedOrders);
    }


    public function updateItemStatus(Request $request)
    {
        $request->validate([
            'item_id' => 'required',
            'status' => 'required|string'
        ]);

        $item = OrderTableItems::with('product')->find($request->item_id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->update([
            'line_status' => $request->status
        ]);

        try {
            $tenantId = (string) tenancy()->tenant->id;
            \Illuminate\Support\Facades\Http::timeout(2)->post("http://127.0.0.1:3001/broadcast", [
                'tenant_id' => $tenantId,
                'event' => 'ItemStatusUpdated',
                'data' => [
                    'item_id' => $item->id,
                    'order_id' => $item->transaction_id,
                    'status' => $item->line_status,
                    'product_name' => $item->product->name_ar ?? '',
                    'updated_at' => now()->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Socket Error (ItemStatusUpdated): " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
            'new_status' => $item->line_status
        ]);
    }
}
