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
            DB::beginTransaction();

            // تحديد الهوية: مسجل دخول أو من الطلب
            $userId = auth()->user() ? auth()->user()->id : $request->created_by;

            $created_by = Employee::find($userId);
            if (!$created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            $table = Table::findOrFail($request->table_id);

            // البحث عن طلب نشط حالي (غير ملغي وغير مكتمل)
            $existingOrder = TableOrders::where('table_id', $table->id)
                ->whereNotIn('order_status', ['canceled', 'served'])
                ->first();

            // فحص هل الطلب الجديد مدفوع؟
            $isNewRequestPaid = isset($request->payments) && is_array($request->payments) && count($request->payments) > 0;

            // --- تطبيق المنطق الخاص بالحالات (بدون تحويل لترانزكشن) ---
            if ($existingOrder) {
                // الحالة 1: القديم "خالص" (مدفوع أو مخدوم) والجديد قادم -> ننهي القديم ونفتح جديد
                if ($existingOrder->payment_status == 'paid' || $existingOrder->order_status == 'served') {
                    // نغلق الحجز القديم والطلب القديم رسمياً
                    $existingOrder->update(['order_status' => 'canceled']);
                    Reservation::where('table_id', $table->id)->where('status', 'active')->update(['status' => 'completed']);

                    $table->update(['table_status' => 0]);
                    $existingOrder = null; // تصفير المتغير لإنشاء طلب جديد تماماً تحت
                }
                // الحالة 3: القديم معلق (غير مدفوع) والجديد مدفوع -> مرفوض محاسبياً
                elseif ($existingOrder->payment_status != 'paid' && $isNewRequestPaid) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن دفع طلب جديد منفصل وهناك طلبات سابقة معلقة على هذه الطاولة.'
                    ], 422);
                }
                // الحالة 2: دمج الطلبات (القديم والجديد غير مدفوعين)
                else {
                    $request->merge(['order_id' => $existingOrder->id]);
                }
            }

            $transaction = null;

            // --- تنفيذ عملية التخزين (Update or Create) ---
            if (isset($request->order_id) && $existingOrder) {
                $transaction = $existingOrder;
                $transaction->update([
                    'discount_amount'      => $request->discount_value,
                    'discount_type'        => $request->discount_type,
                    'total_before_tax'     => $request->total_before_discount,
                    'total_after_discount' => $request->total_after_discount,
                    'tax_amount'           => $request->total_tax,
                    'final_total'          => $request->total_paid,
                    'created_by'           => $userId,
                    'description'          => $request->note,
                    'payment_status'       => $isNewRequestPaid ? 'paid' : $transaction->payment_status,
                ]);

                $this->saveOrderItems($transaction, $request->items);
            } else {
                // إنشاء حجز وطلب جديد تماماً
                if ($table->table_status != 0 && !$existingOrder) {
                    // تنظيف أي حجز عالق قبل البدء
                    Reservation::where('table_id', $table->id)->where('status', 'active')->update(['status' => 'canceled']);
                }

                $reservation = Reservation::create([
                    'table_id'         => $table->id,
                    'customer_name'    => $request->customer_name ?? 'Guest',
                    'customer_phone'   => $request->customer_phone ?? null,
                    'reservation_time' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                    'guests_count'     => $request->guests_count ?? 1,
                    'status'           => 'active',
                    'created_by'       => $userId
                ]);

                $table->update(['table_status' => 2, 'assigned_waiter_id' => $userId]);

                $transaction = TableOrders::create([
                    'type'                 => 'sell',
                    'invoice_type'         => 'cash',
                    'transaction_date'     => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                    'discount_amount'      => $request->discount_value,
                    'discount_type'        => $request->discount_type,
                    'total_before_tax'     => $request->total_before_discount,
                    'total_after_discount' => $request->total_after_discount,
                    'tax_amount'           => $request->total_tax,
                    'final_total'          => $request->total_paid,
                    'created_by'           => $userId,
                    'description'          => $request->note,
                    'ref_no'               => $this->generateOrdNo(),
                    'status'               => 'draft',
                    'establishment_id'     => $table->area->establishment_id,
                    'table_id'             => $table->id,
                    'order_status'         => 'inpreparation',
                    'payment_status'       => $isNewRequestPaid ? 'paid' : 'due',
                    'order_type'           => $request->order_type ?? 1,
                    'local_id'             => 'table_order'
                ]);

                $this->saveOrderItems($transaction, $request->items);
            }

            DB::commit();
            $this->broadcastTableUpdate($table, $transaction);

            return response()->json([
                'status'   => true,
                'message'  => $isNewRequestPaid ? 'Order paid successfully' : 'Order saved',
                'order_id' => $transaction->id,
                'order_no' => $transaction->ref_no
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Store Error: " . $e->getMessage());
            return response()->json(['message' => 'something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * إلغاء الطلب بشكل كامل (تعديل الحالة فقط)
     */
    public function cancelOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $order = TableOrders::find($request->id);
            if (!$order) return response()->json(['message' => 'No Order found'], 404);

            // تحديث حالة الطلب للإلغاء
            $order->update(['order_status' => 'canceled']);

            // تصفير الطاولة
            $table = Table::find($order->table_id);
            if ($table) {
                $table->update(['table_status' => 0, 'assigned_waiter_id' => null]);
            }

            // إغلاق الحجز
            Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            DB::commit();
            return response()->json(['message' => 'Order canceled successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cancel failed', 'error' => $e->getMessage()], 500);
        }
    }

    private function saveOrderItems($transaction, $items)
    {
        $products = json_decode(json_encode($items));
        foreach ($products as $product) {
            $mainItem = OrderTableItems::create([
                'transaction_id'             => $transaction->id,
                'product_id'                 => $product->product_id,
                'qyt'                        => $product->quantity,
                'unit_price_before_discount' => $product->price_after_discount ?? $product->price,
                'unit_price'                 => $product->price,
                'discount_type'              => $product->discount_type ?? null,
                'discount_amount'            => $product->discount_amount ?? 0,
                'unit_price_inc_tax'         => $product->price_with_tax ?? $product->price,
                'tax_id'                     => $product->tax_id ?? null,
                'tax_value'                  => $product->tax_value ?? 0,
                'line_status'                => 'inpreparation',
            ]);

            if (isset($product->order_item_modifiers)) {
                foreach ($product->order_item_modifiers as $modifier) {
                    OrderTableItems::create([
                        'transaction_id'             => $transaction->id,
                        'modifier_id'                => $modifier->modifier_id,
                        'product_id'                 => $modifier->modifier_id,
                        'parent_id'                  => $mainItem->id,
                        'qyt'                        => $modifier->quantity,
                        'unit_price'                 => $modifier->price,
                        'unit_price_before_discount' => $modifier->price_after_discount ?? $modifier->price,
                        'unit_price_inc_tax'         => $modifier->price_with_tax ?? $modifier->price,
                        'line_status'                => 'inpreparation',
                    ]);
                }
            }

            if (isset($product->order_item_combos)) {
                foreach ($product->order_item_combos as $combo) {
                    OrderTableItems::create([
                        'transaction_id'             => $transaction->id,
                        'combo_id'                   => $combo->option_id,
                        'product_id'                 => $combo->product_id ?? $mainItem->product_id,
                        'parent_id'                  => $mainItem->id,
                        'qyt'                        => $combo->quantity ?? 1,
                        'unit_price'                 => $combo->price ?? 0,
                        'unit_price_before_discount' => $combo->price ?? 0,
                        'line_status'                => 'inpreparation',
                    ]);
                }
            }
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

    private function broadcastTableUpdate($table, $transaction)
    {
        try {
            $tenantId = (string) tenancy()->tenant->id;
            \Illuminate\Support\Facades\Http::timeout(2)->post("http://127.0.0.1:3001/broadcast", [
                'tenant_id' => $tenantId,
                'event'     => 'TableUpdated',
                'data'      => [
                    'table_id'           => $table->id,
                    'table_code'         => $table->code,
                    'transaction_ref_no' => $transaction->ref_no
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Socket Error: " . $e->getMessage());
        }
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

    // public function getFilteredOrdersByCategory(Request $request)
    // {
    //     $category_ids = $request->input('category_ids', []);
    //     $establishment_id = $request->input('establishment_id');

    //     if (!Establishment::find($establishment_id) || !$establishment_id) {
    //         return response()->json(['message' => 'Establishment not found'], 404);
    //     }

    //     $tableOrders = TableOrders::where('establishment_id', $establishment_id)
    //         ->whereNotIn('order_status', ['canceled', 'served'])
    //         ->whereHas('sell_lines.product', function ($query) use ($category_ids) {
    //             $query->whereIn('category_id', $category_ids);
    //         })
    //         ->with(['sell_lines.product', 'createdBy'])
    //         ->get();

    //     $posTransactions = Transaction::where('establishment_id', $establishment_id)
    //         ->where('type', 'sell')
    //         ->where('order_status', 'inpreparation')
    //         ->whereHas('sell_lines.product', function ($query) use ($category_ids) {
    //             $query->whereIn('category_id', $category_ids);
    //         })
    //         ->with(['sell_lines.product', 'createdBy'])
    //         ->get();

    //     $allOrders = $tableOrders->concat($posTransactions);

    //     $formattedOrders = $allOrders->map(function ($order) use ($category_ids) {

    //         $reservation = null;
    //         if (isset($order->table_id)) {
    //             $reservation = Reservation::where('table_id', $order->table_id)
    //                 ->where('status', 'active')
    //                 ->first();
    //         }

    //         $allLines = $order->sell_lines;

    //         $filteredLines = $allLines->filter(function ($line) use ($category_ids) {
    //             return $line->product && in_array($line->product->category_id, $category_ids);
    //         });

    //         $serviceName = 'محلي';
    //         if (!isset($order->table_id)) {
    //             $serviceName = 'سفري/كاشير';
    //         } else {
    //             $service = TypesOfService::find($order->order_type);
    //             $serviceName = $service->name_ar ?? 'محلي';
    //         }

    //         return [
    //             'id' => $order->id,
    //             'table_id' => $order->table_id ?? null,
    //             'is_pos_order' => !isset($order->table_id),
    //             'created_by' => $order->created_by,
    //             'order_type' => $serviceName,
    //             'order_status' => $order->order_status,
    //             'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
    //             'customer_name' => $reservation->customer_name ?? ($order->contact->name ?? 'Guest'),
    //             'customer_phone' => $reservation->customer_phone ?? ($order->contact->mobile ?? ''),
    //             'guests_count' => $reservation->guests_count ?? 0,
    //             'discount_type' => $order->discount_type,
    //             'discount_value' => (float)$order->discount_amount,
    //             'total_before_discount' => (float)$order->total_before_tax,
    //             'total_after_discount' => (float)$order->total_after_discount,
    //             'total_tax' => (float)$order->tax_amount,
    //             'total_paid' => (float)$order->final_total,
    //             'note' => $order->description,
    //             'items' => $filteredLines->map(function ($mainItem) {
    //                 return [
    //                     'id' => $mainItem->id,
    //                     'order_id' => $mainItem->transaction_id,
    //                     'product_id' => $mainItem->product_id,
    //                     'product_name' => $mainItem->product->name_ar ?? '',
    //                     'category_id' => $mainItem->product->category_id,
    //                     'quantity' => (float)$mainItem->qyt,
    //                     'price' => (float)$mainItem->unit_price,
    //                     'price_with_tax' => (float)$mainItem->unit_price_inc_tax,
    //                     'tax_id' => $mainItem->tax_id,
    //                     'tax_value' => (float)$mainItem->tax_value,
    //                     'discount_type' => $mainItem->discount_type,
    //                     'discount_amount' => (float)$mainItem->discount_amount,
    //                     'status' => $mainItem->line_status ?? 'inpreparation',
    //                 ];
    //             })->values()
    //         ];
    //     });

    //     $sortedOrders = $formattedOrders->sortByDesc('created_at')->values();

    //     return response()->json($sortedOrders);
    // }
public function getFilteredOrdersByCategory(Request $request)
{
    $category_ids = $request->input('category_ids', []);
    $establishment_id = $request->input('establishment_id');

    if (!$establishment_id || !Establishment::find($establishment_id)) {
        return response()->json(['message' => 'Establishment not found'], 404);
    }

    // 1. جلب طلبات الطاولات (المحلي)
    $tableOrders = TableOrders::where('establishment_id', $establishment_id)
        // إذا المصفوفة فارغة، لا يطبق شرط الـ whereHas (يجلب الكل)
        ->when(!empty($category_ids), function ($query) use ($category_ids) {
            return $query->whereHas('sell_lines.product', function ($q) use ($category_ids) {
                $q->whereIn('category_id', $category_ids);
            });
        })
        ->with(['sell_lines.product', 'createdBy'])
        ->get();

    // 2. جلب طلبات الكاشير (السفري)
    $posTransactions = Transaction::where('establishment_id', $establishment_id)
        ->where('type', 'sell')
        ->where('order_status', 'inpreparation')
        // نفس المنطق: إذا المصفوفة فارغة يجلب كل ما هو "قيد التحضير"
        ->when(!empty($category_ids), function ($query) use ($category_ids) {
            return $query->whereHas('sell_lines.product', function ($q) use ($category_ids) {
                $q->whereIn('category_id', $category_ids);
            });
        })
        ->with(['sell_lines.product', 'createdBy'])
        ->get();

    // دمج المجموعتين (المحلي والسفري)
    $allOrders = $tableOrders->concat($posTransactions);

    // بناء الريسبونس بنفس الحقول التي طلبتها في كودك الأصلي
    $formattedOrders = $allOrders->map(function ($order) use ($category_ids) {

        // جلب الحجز لطلبات الطاولات فقط
        $reservation = null;
        if (isset($order->table_id)) {
            $reservation = Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->first();
        }

        $allLines = $order->sell_lines;

        // فلترة الأصناف داخل الطلب: إذا الفلتر فارغ خذ كل الأصناف، وإلا فلترها
        $filteredLines = $allLines->filter(function ($line) use ($category_ids) {
            if (empty($category_ids)) return true;
            return $line->product && in_array($line->product->category_id, $category_ids);
        });

        // إذا كان هناك فلاتر وأصبح الطلب فارغاً بعد الفلترة، نتجاهله
        if (!empty($category_ids) && $filteredLines->isEmpty()) {
            return null;
        }

        // تحديد نوع الخدمة للحقل order_type
        $serviceName = 'محلي';
        if (!isset($order->table_id)) {
            $serviceName = 'سفري';
        } else {
            $service = TypesOfService::find($order->order_type);
            $serviceName = $service->name_ar ?? 'محلي';
        }

        // الهيكلية (Response) كما هي في كودك الأصلي دون نقصان
        return [
            'id' => $order->id,
            'table_id' => $order->table_id,
            'created_by' => $order->created_by,
            'order_type' => $serviceName,
            'order_status' => $order->order_status,
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s.v') : null,
            'customer_name' => $reservation->customer_name ?? ($order->contact->name ?? 'Guest'),
            'customer_phone' => $reservation->customer_phone ?? ($order->contact->mobile ?? ''),
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
    })->filter()->values(); // تنظيف الـ null نتيحة الفلترة

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
