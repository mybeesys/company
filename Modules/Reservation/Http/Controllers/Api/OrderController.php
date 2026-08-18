<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\Product;
use Modules\Product\Models\TypesOfService;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderTableItems;
use Modules\Sales\Services\PosSalesInvoiceMapper;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Services\EstablishmentOrdersBroadcastService;
use Modules\Reservation\Services\KitchenBroadcastService;
use Modules\Reservation\Services\RealtimeBroadcastService;
use Modules\Reservation\Support\EstablishmentOrderPayload;
use Modules\Reservation\Support\KitchenItemStatusGrouper;
use Modules\Reservation\Support\KitchenOrderPayload;
use Modules\Reservation\Support\OrderLineParent;

class OrderController extends Controller
{
    public function __construct(
        private readonly RealtimeBroadcastService $realtime,
        private readonly KitchenBroadcastService $kitchen,
        private readonly EstablishmentOrdersBroadcastService $posOrders
    ) {}
    public function storeApi(Request $request)
    {
        try {
            DB::beginTransaction();

            // تحديد الهوية: مسجل دخول أو من الطلب
            $userId = auth()->user() ? auth()->user()->id : $request->created_by;

            $created_by = Employee::find($userId);
            if (! $created_by) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            $table = Table::findOrFail($request->table_id);

            // آخر طلب على الطاولة (بما فيه المخدوم) — يُعاد فتحه عند إضافة أصناف بدل إنشاء طلب جديد
            $existingOrder = TableOrders::where('table_id', $table->id)
                ->whereNotIn('order_status', ['canceled'])
                ->latest('id')
                ->first();

            // فحص هل الطلب الجديد مدفوع؟
            $isNewRequestPaid = isset($request->payments) && is_array($request->payments) && count($request->payments) > 0;

            $wasReopenedFromTerminal = false;

            // --- تطبيق المنطق الخاص بالحالات (بدون تحويل لترانزكشن) ---
            if ($existingOrder) {
                // الحالة 3: القديم معلق (غير مدفوع) والجديد مدفوع -> مرفوض محاسبياً
                if ($existingOrder->payment_status != 'paid' && $isNewRequestPaid) {
                    DB::rollBack();

                    return response()->json([
                        'status' => false,
                        'message' => 'لا يمكن دفع طلب جديد منفصل وهناك طلبات سابقة معلقة على هذه الطاولة.',
                    ], 422);
                }

                // الحالة 2: دمج / تحديث الطلب الحالي (بما فيه إعادة فتح المخدوم)
                $wasReopenedFromTerminal = $this->isReopenableOrderStatus($existingOrder->order_status);
                $request->merge(['order_id' => $existingOrder->id]);
            }

            $transaction = null;
            $wasCreated = false;

            // --- تنفيذ عملية التخزين (Update or Create) ---
            if (isset($request->order_id) && $existingOrder) {
                $transaction = $existingOrder;
                $updateData = [
                    'discount_amount' => $request->discount_value,
                    'discount_type' => $request->discount_type,
                    'total_before_tax' => $request->total_before_discount,
                    'total_after_discount' => $request->total_after_discount,
                    'tax_amount' => $request->total_tax,
                    'final_total' => $request->total_paid,
                    'created_by' => $userId,
                    'description' => $request->note,
                    'payment_status' => $isNewRequestPaid ? 'paid' : $transaction->payment_status,
                ];

                if ($wasReopenedFromTerminal) {
                    $updateData['order_status'] = 'inpreparation';
                    if (! $isNewRequestPaid) {
                        $updateData['payment_status'] = 'due';
                    }
                }

                $transaction->update($updateData);

                if ($wasReopenedFromTerminal) {
                    $this->reopenTableForOrder($table, $userId, $request);
                }

                $this->saveOrderItems($transaction, $request->items);
            } else {
                // إنشاء حجز وطلب جديد تماماً
                if ($table->table_status != 0 && ! $existingOrder) {
                    // تنظيف أي حجز عالق قبل البدء
                    Reservation::where('table_id', $table->id)->where('status', 'active')->update(['status' => 'canceled']);
                }

                $reservation = Reservation::create([
                    'table_id' => $table->id,
                    'customer_name' => $request->customer_name ?? 'Guest',
                    'customer_phone' => $request->customer_phone ?? null,
                    'reservation_time' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                    'guests_count' => $request->guests_count ?? 1,
                    'status' => 'active',
                    'created_by' => $userId,
                ]);

                $table->update(['table_status' => 2, 'assigned_waiter_id' => $userId]);

                $transaction = TableOrders::create([
                    'type' => 'sell',
                    'invoice_type' => 'cash',
                    'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
                    'discount_amount' => $request->discount_value,
                    'discount_type' => $request->discount_type,
                    'total_before_tax' => $request->total_before_discount,
                    'total_after_discount' => $request->total_after_discount,
                    'tax_amount' => $request->total_tax,
                    'final_total' => $request->total_paid,
                    'created_by' => $userId,
                    'description' => $request->note,
                    'ref_no' => $this->generateOrdNo(),
                    'status' => 'draft',
                    'establishment_id' => $table->area->establishment_id,
                    'table_id' => $table->id,
                    'order_status' => 'inpreparation',
                    'payment_status' => $isNewRequestPaid ? 'paid' : 'due',
                    'order_type' => $request->order_type ?? 1,
                    'local_id' => 'table_order',
                ]);

                $this->saveOrderItems($transaction, $request->items);
                $wasCreated = true;
            }

            DB::commit();
            $table->refresh();
            $transaction->refresh();

            if ($wasCreated) {
                $this->realtime->orderCreated($transaction, (int) $userId);
                $transaction->load(['sell_lines.product', 'sell_lines.productCombo', 'createdBy']);
                $this->kitchen->orderCreated($transaction, 'local');
                $this->posOrders->orderCreated($transaction);
            } elseif ($wasReopenedFromTerminal) {
                $this->realtime->tableUpdated($table);
                $this->realtime->orderUpdated($table->id);
                $transaction->load(['sell_lines.product', 'sell_lines.productCombo', 'createdBy']);
                $this->kitchen->orderCreated($transaction, 'local');
                $this->posOrders->orderUpdated($transaction);
            } elseif ($this->isTerminalOrderStatus($transaction->order_status)) {
                $notifyWaiterId = $this->tableAssignedWaiterId($table->id);
                $this->finalizeTableOrderIfTerminal($transaction);
                $table->refresh();
                $this->realtime->orderFinished($transaction, $notifyWaiterId);
            } elseif ($isNewRequestPaid) {
                $this->realtime->tableUpdated($table);
                $this->realtime->orderUpdated($table->id);
                $transaction->load(['sell_lines.product', 'sell_lines.productCombo', 'createdBy']);
                $this->kitchen->orderUpdated($transaction, 'local');
                $this->posOrders->orderUpdated($transaction);
            } else {
                $this->realtime->tableUpdated($table);
                $this->realtime->orderUpdated($table->id);
                $transaction->load(['sell_lines.product', 'sell_lines.productCombo', 'createdBy']);
                $this->kitchen->orderUpdated($transaction, 'local');
                $this->posOrders->orderUpdated($transaction);
            }

            return response()->json([
                'status' => true,
                'message' => $isNewRequestPaid ? 'Order paid successfully' : 'Order saved',
                'order_id' => $transaction->id,
                'order_no' => $transaction->ref_no,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Store Error: '.$e->getMessage());

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
            if (! $order) {
                return response()->json(['message' => 'No Order found'], 404);
            }

            // تحديث حالة الطلب للإلغاء
            $order->update(['order_status' => 'canceled']);

            // تصفير الطاولة
            $table = Table::find($order->table_id);
            $notifyWaiterId = $this->tableAssignedWaiterId($table?->id);
            if ($table) {
                $table->update(['table_status' => 0, 'assigned_waiter_id' => null]);
            }

            // إغلاق الحجز
            Reservation::where('table_id', $order->table_id)
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            DB::commit();

            if ($table) {
                $table->refresh();
                $this->realtime->orderFinished($order, $notifyWaiterId);
            }
            if ($establishmentId = KitchenOrderPayload::establishmentIdFromOrder($order)) {
                $this->kitchen->orderRemoved($order->id, $establishmentId, 'cancelled', $order);
            }
            $this->posOrders->orderCancelled($order);

            return response()->json(['message' => 'Order canceled successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Cancel failed', 'error' => $e->getMessage()], 500);
        }
    }

    private function saveOrderItems($transaction, $items)
    {
        OrderTableItems::where('transaction_id', $transaction->id)->delete();

        $products = json_decode(json_encode($items));
        foreach ($products as $product) {
            $mainItem = OrderTableItems::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->product_id,
                'qyt' => $product->quantity,
                'unit_price_before_discount' => $product->price_after_discount ?? $product->price,
                'unit_price' => $product->price,
                'discount_type' => $product->discount_type ?? null,
                'discount_amount' => $product->discount_amount ?? 0,
                'unit_price_inc_tax' => $product->price_with_tax ?? $product->price,
                'tax_id' => $product->tax_id ?? null,
                'tax_value' => $product->tax_value ?? 0,
                'line_status' => 'inpreparation',
                'note' => PosSalesInvoiceMapper::resolveItemNote($product),
            ]);

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

            if (isset($product->order_item_combos)) {
                foreach ($product->order_item_combos as $combo) {
                    $comboProductId = (int) ($combo->product_id ?? 0);
                    if ($comboProductId <= 0) {
                        $resolved = PosSalesInvoiceMapper::resolveComboOption((object) [
                            'option_id' => $combo->option_id ?? 0,
                            'combo_group_id' => $combo->combo_group_id ?? 0,
                        ]);
                        $comboProductId = $resolved ? (int) $resolved->item_id : (int) ($combo->option_id ?? 0);
                    }

                    OrderTableItems::create([
                        'transaction_id' => $transaction->id,
                        'combo_id' => $combo->option_id,
                        'product_id' => $comboProductId,
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

        return $prefix.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    public function establishmentOrders(Request $request, $id)
    {
        $type = $request->query('type');

        return response()->json(
            EstablishmentOrderPayload::ordersForEstablishment((int) $id, $type)
        );
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
            $parentItems = $allLines->filter(fn ($line) => OrderLineParent::isRoot($line));

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
                'discount_value' => (float) $order->discount_amount,
                'total_before_discount' => (float) $order->total_before_tax,
                'total_after_discount' => (float) $order->total_after_discount,
                'total_tax' => (float) $order->tax_amount,
                'total_paid' => (float) $order->final_total,
                'note' => $order->description,
                'items' => $parentItems->map(function ($mainItem) use ($allLines) {
                    $subItems = $allLines->where('parent_id', $mainItem->id);

                    return [
                        'id' => $mainItem->id,
                        'order_id' => $mainItem->transaction_id,
                        'product_id' => $mainItem->product_id,
                        'product_name' => $mainItem->product->name_ar ?? '',
                        'quantity' => (float) $mainItem->qyt,
                        'price' => (float) $mainItem->unit_price,
                        'price_with_tax' => (float) $mainItem->unit_price_inc_tax,
                        'tax_id' => $mainItem->tax_id,
                        'tax_value' => (float) $mainItem->tax_value,
                        'discount_type' => $mainItem->discount_type,
                        'discount_amount' => (float) $mainItem->discount_amount,
                        'note' => $mainItem->note ?? '',
                        'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                            return [
                                'id' => $mod->id,
                                'modifier_id' => $mod->product_id,
                                'modifier_name' => $mod->product->name_ar ?? '',
                                'quantity' => (float) $mod->qyt,
                                'price' => (float) $mod->unit_price,
                                'price_with_tax' => (float) $mod->unit_price_inc_tax,
                            ];
                        })->values(),
                        'order_item_combos' => $subItems->whereNotNull('combo_id')->map(function ($combo) {
                            return [
                                'id' => $combo->id,
                                'option_id' => $combo->combo_id,
                                'option_name' => $combo->productCombo->name_ar ?? '',
                                'price' => (float) $combo->unit_price,
                                'combo_group_id' => $combo->product_id,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return response()->json($formattedOrders);
    }

    public function updateOrders(Request $request, $id)
    {
        $order = TableOrders::find($id);
        if (! $order) {
            return response()->json([
                'message' => 'no order with given id',
            ], 409);
        }

        $newStatus = $request->input('status') ?? $request->input('order_status');
        if (empty($newStatus)) {
            return response()->json(['message' => 'status or order_status is required'], 422);
        }

        $notifyWaiterId = $this->tableAssignedWaiterId($order->table_id);

        $order->update([
            'order_status' => $newStatus,
        ]);
        $order->refresh();

        $this->finalizeTableOrderIfTerminal($order);

        if ($this->isTerminalOrderStatus($order->order_status)) {
            $this->realtime->orderFinished($order, $notifyWaiterId);
            $this->kitchen->orderUpdated($order, 'local');
            $this->posOrders->orderClosed($order);
        } else {
            $this->posOrders->orderUpdated($order);
            $this->realtime->orderStatusChanged($order, $notifyWaiterId);
            $this->realtime->orderUpdated($order->table_id, $notifyWaiterId);
            $order->load(['sell_lines.product']);
            $this->kitchen->orderUpdated($order, 'local');
        }

        return response()->json([
            'message' => $order,
        ], 200);
    }

    private function isTerminalOrderStatus(?string $status): bool
    {
        return in_array($status, ['served', 'canceled', 'completed'], true);
    }

    private function tableAssignedWaiterId(int|string|null $tableId): ?int
    {
        if (! $tableId) {
            return null;
        }

        $waiterId = Table::whereKey($tableId)->value('assigned_waiter_id');

        return $waiterId ? (int) $waiterId : null;
    }

    private function isReopenableOrderStatus(?string $status): bool
    {
        return in_array($status, ['served', 'prepared', 'completed'], true);
    }

    private function reopenTableForOrder(Table $table, int $userId, Request $request): void
    {
        $table->update([
            'table_status' => 2,
            'assigned_waiter_id' => $userId,
        ]);

        $hasActiveReservation = Reservation::where('table_id', $table->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveReservation) {
            return;
        }

        Reservation::create([
            'table_id' => $table->id,
            'customer_name' => $request->customer_name ?? 'Guest',
            'customer_phone' => $request->customer_phone ?? null,
            'reservation_time' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
            'guests_count' => $request->guests_count ?? 1,
            'status' => 'active',
            'created_by' => $userId,
        ]);
    }

    private function finalizeTableOrderIfTerminal(TableOrders $order): void
    {
        if (! $this->isTerminalOrderStatus($order->order_status)) {
            return;
        }

        $table = Table::find($order->table_id);
        if (! $table) {
            return;
        }

        $table->update([
            'table_status' => 0,
            'assigned_waiter_id' => null,
        ]);

        $reservationStatus = $order->order_status === 'canceled' ? 'canceled' : 'completed';
        Reservation::where('table_id', $order->table_id)
            ->where('status', 'active')
            ->update(['status' => $reservationStatus]);
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
        $category_ids = array_map('intval', (array) $request->input('category_ids', []));
        $establishment_id = (int) $request->input('establishment_id');

        if (! $establishment_id || ! Establishment::find($establishment_id)) {
            return response()->json(['message' => 'Establishment not found'], 404);
        }

        return response()->json(
            KitchenOrderPayload::ordersForEstablishment($establishment_id, $category_ids)
        );
    }

    public function updateItemStatus(Request $request)
    {
        $request->validate([
            'item_id' => 'required',
            'order_type' => 'required|in:local,pos',
        ]);

        $orderType = $request->order_type;
        $itemId = $request->item_id;

        if ($orderType === 'local') {
            $itemModel = OrderTableItems::class;
            $orderModel = TableOrders::class;
        } else {
            $itemModel = TransactionSellLine::class;
            $orderModel = Transaction::class;
        }

        $item = $itemModel::with(['product'])->find($itemId);

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $lineIds = $this->resolvePreparedLineGroupIds($itemModel, $item);
        $itemModel::whereIn('id', $lineIds)
            ->where('line_status', 'inpreparation')
            ->update(['line_status' => 'prepared']);

        $order = $orderModel::find($item->transaction_id);
        $allOrderPrepared = false;

        if ($order) {
            $remainingItems = $itemModel::where('transaction_id', $order->id)
                ->where('line_status', 'inpreparation')
                ->count();

            if ($remainingItems == 0) {
                $order->update(['order_status' => 'prepared']);
                $allOrderPrepared = true;
            }
        }

        if ($order) {
            $order->refresh();
            $order->load(['sell_lines.product']);
            $this->kitchen->itemStatusChanged($order, (int) $item->id, 'prepared', $orderType);

            if ($orderType === 'local' && $order->table_id) {
                $notifyWaiterId = $this->tableAssignedWaiterId($order->table_id);
                if ($allOrderPrepared) {
                    $this->realtime->orderStatusChanged($order, $notifyWaiterId);
                }
                $this->realtime->orderUpdated($order->table_id, $notifyWaiterId);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
            'new_status' => 'prepared',
            'updated_line_ids' => $lineIds,
            'order_status' => $order ? $order->order_status : null,
        ]);
    }

    /**
     * @param  OrderTableItems|TransactionSellLine  $item
     * @param  class-string  $itemModel
     * @return array<int, int>
     */
    private function resolvePreparedLineGroupIds(string $itemModel, $item): array
    {
        if ($itemModel === OrderTableItems::class) {
            return KitchenItemStatusGrouper::tableLineGroupIds($item);
        }

        $allLines = TransactionSellLine::where('transaction_id', $item->transaction_id)->get();

        return KitchenItemStatusGrouper::posLineGroupIds($allLines, (int) $item->id);
    }

    /**
     * تجهيز الطلب كاملاً من المطبخ (كل الأصناف + رأس الطلب) دفعة واحدة.
     */
    public function updateOrderStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'order_type' => 'required|in:local,pos',
            'status' => 'required|in:prepared',
        ]);

        $orderType = $validated['order_type'];
        $orderId = (int) $validated['order_id'];

        if ($orderType === 'local') {
            $itemModel = OrderTableItems::class;
            $order = TableOrders::find($orderId);
        } else {
            $itemModel = TransactionSellLine::class;
            $order = Transaction::where('type', 'sell')->find($orderId);
        }

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($order->order_status !== 'inpreparation') {
            return response()->json([
                'message' => 'Order is not in preparation',
                'order_status' => $order->order_status,
            ], 422);
        }

        $updatedLines = $itemModel::where('transaction_id', $order->id)
            ->where('line_status', 'inpreparation')
            ->update(['line_status' => 'prepared']);

        $order->update(['order_status' => 'prepared']);
        $order->refresh();
        $order->load(['sell_lines.product', 'sell_lines.productCombo']);

        $kitchenSource = $orderType === 'local' ? 'local' : 'pos';
        $this->kitchen->orderUpdated($order, $kitchenSource);

        if ($orderType === 'local' && $order instanceof TableOrders && $order->table_id) {
            $notifyWaiterId = $this->tableAssignedWaiterId($order->table_id);
            $this->realtime->orderStatusChanged($order, $notifyWaiterId);
            $this->realtime->orderUpdated($order->table_id, $notifyWaiterId);
            $this->posOrders->orderUpdated($order);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order marked as prepared',
            'order_id' => $order->id,
            'order_type' => $orderType,
            'source' => KitchenOrderPayload::resolveSource($order),
            'kitchen_key' => KitchenOrderPayload::kitchenKey($order),
            'order_status' => $order->order_status,
            'updated_lines' => $updatedLines,
        ]);
    }
}
