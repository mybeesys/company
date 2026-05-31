<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TypesOfService;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Models\TableOrders;
use Modules\Reservation\Services\RealtimeBroadcastService;
use Modules\Reservation\Transformers\TableResource;

class TableConrtollerController extends Controller
{
    public function __construct(
        private readonly RealtimeBroadcastService $realtime
    ) {}
    public function index()
    {
        $tables = Table::with('area')->get();

        return TableResource::collection($tables);
    }

    public function tables(Request $request)
    {

        $establishmentId = $request->query('establishment_id');
        $tables = Table::with([
            'area',
            'activeOrder',
            'reservation',
        ])->where('active', 1)
            ->when($establishmentId, function ($query) use ($establishmentId) {
                return $query->whereHas('area', function ($q) use ($establishmentId) {
                    $q->where('establishment_id', $establishmentId);
                });
            })
            ->get();

        return response()->json([
            'data' => $tables->map(function ($table) {
                return [
                    'id' => $table->id,
                    'name' => $table->code,
                    'capacity' => $table->steating_capacity,
                    'status' => match ($table->table_status) {
                        '1' => 'reserved',
                        '2' => 'notAvailable',
                        default => 'available',
                    },
                    'current_order_id' => optional($table->activeOrder)->id,
                    'order_status' => optional($table->activeOrder)->order_status,
                    'assigned_waiter_id' => $table->assigned_waiter_id,
                    'current_guests' => optional($table->reservation)->guests_count,
                    'opened_at' => optional($table->activeOrder)->created_at,
                    'area' => optional($table->area)->name_en,
                    'establishment_id' => optional($table->area)->establishment_id,
                    'floor' => optional($table->area)->floor ?? 1,
                    'reservation' => $table->reservation ? [
                        'customer_name' => $table->reservation->customer_name,
                        'customer_phone' => $table->reservation->customer_phone,
                        'reservation_time' => $table->reservation->reservation_time,
                        'guests_count' => $table->reservation->guests_count,
                    ] : null,
                ];
            }),
        ]);
    }

    public function details($id)
    {
        $table = Table::with([
            'area',
            'reservation',
            'activeOrder.payment',
            'activeOrder.sell_lines.product',
        ])->find($id);

        if (! $table) {
            return response()->json([
                'message' => 'Table not found id = '.$id,
            ], 404);
        }

        $order = $table->activeOrder;
        $table_status = 'available';
        if ($table->table_status == 1) {
            $table_status = 'reserved';
        } elseif ($table->table_status == 2) {
            $table_status = 'notAvailable';
        }

        $items = [];
        if ($order && $order->sell_lines) {
            $allLines = $order->sell_lines;
            $parentItems = $allLines->filter(function ($line) {
                return empty($line->parent_id) || $line->parent_id == 0;
            });

            $items = $parentItems->map(function ($mainItem) use ($allLines) {
                $subItems = $allLines->where('parent_id', $mainItem->id);

                return [
                    'id' => $mainItem->id,
                    'product_id' => $mainItem->product_id,
                    'product_name' => $mainItem->product->name_ar ?? '',
                    'quantity' => (float) $mainItem->qyt,
                    'price' => (float) $mainItem->unit_price,
                    'price_after_discount' => (float) $mainItem->unit_price_before_discount,
                    'discount_type' => $mainItem->discount_type,
                    'discount_amount' => (float) $mainItem->discount_amount,
                    'tax_id' => $mainItem->tax_id,
                    'tax_value' => (float) $mainItem->tax_value,
                    'price_with_tax_after_discount' => (float) $mainItem->unit_price_inc_tax,
                    'order_item_modifiers' => $subItems->whereNotNull('modifier_id')->map(function ($mod) {
                        return [
                            'id' => $mod->id,
                            'modifier_id' => $mod->modifier_id,
                            'modifier_name' => $mod->product->name_ar ?? 'Modifier',
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
                        ];
                    })->values(),
                ];
            })->values();
        }

        $service_name = 'محلي';
        if ($order && $order->order_type) {
            $service = TypesOfService::find($order->order_type);
            $service_name = $service->name_ar ?? 'محلي';
        }

        return response()->json([
            'table' => [
                'id' => $table->id,
                'code' => $table->code,
                'status' => $table_status,
                'capacity' => $table->steating_capacity,
                'assigned_waiter_id' => $table->assigned_waiter_id,
                'area' => [
                    'id' => $table->area?->id,
                    'name_ar' => $table->area?->name_ar,
                    'name_en' => $table->area?->name_en,
                ],
            ],
            'reservation' => $table->reservation ? [
                'id' => $table->reservation->id,
                'customer_name' => $table->reservation->customer_name,
                'customer_phone' => $table->reservation->customer_phone,
                'guests_count' => $table->reservation->guests_count,
                'reservation_time' => $table->reservation->reservation_time,
                'status' => $table->reservation->status,
            ] : null,
            'order' => $order ? [
                'id' => $order->id,
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
                'order_type' => $service_name,
                'payment_status' => $order?->payment_status,
                'invoice_created' => ! empty($order->id),
                'invoice_id' => $order->id,
                'paid_amount' => $order?->payment?->sum('amount') ?? 0,
                'total_amount' => $order->final_total ?? 0,
                'items' => $items,
                'payment' => $order?->payment,
            ] : null,
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        try {
            DB::beginTransaction();
            $table = Table::findOrFail($id);
            $hasActiveOrder = TableOrders::where('table_id', $table->id)
                ->where('status', 'draft')
                ->where('order_status', '<>', 'served')
                ->exists();

            if ($hasActiveOrder) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن تغيير حالة الطاولة يدوياً لوجود طلب مفتوح عليها.',
                ], 422);
            }

            if ($request->status == 1) {
                Reservation::updateOrCreate(
                    ['table_id' => $table->id, 'status' => 'active'],
                    [
                        'customer_name' => $request->customer_name ?? 'Guest',
                        'reservation_time' => now(),
                        'guests_count' => $request->guests_count ?? $table->steating_capacity,
                    ]
                );
            }

            if ($request->status == 0) {
                Reservation::where('table_id', $table->id)
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);
                $table->assigned_waiter_id = null;
            }

            $table->table_status = $request->status;
            $table->save();
            DB::commit();
            $table->load(['area', 'activeOrder', 'reservation']);
            $this->realtime->tableUpdated($table);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الحالة بنجاح',
                'data' => [
                    'table_id' => $table->id,
                    'status' => $table->table_status,
                    'updated_at' => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['status' => false, 'message' => 'خطأ: '.$e->getMessage()], 500);
        }
    }

}
