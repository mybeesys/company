<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Transformers\TableResource;

use function Laravel\Prompts\table;

class TableConrtollerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tables = Table::with('area')->get();
        return TableResource::collection($tables);
    }


    public function tables()
    {
        $tables = Table::with([
            'area',
            'activeOrder',
            'reservation'
        ])->where('active', 1)->get();

        return response()->json([
            'data' => $tables->map(function ($table) {
                $status = 'available';
                if ($table->table_status == 2) {
                    $status = 'notAvailable';
                }

                return [
                    'id' => $table->id,
                    'name' => $table->code,
                    'capacity' => $table->steating_capacity,
                    'status' => $status,

                    'current_order_id' => optional($table->activeOrder)->id,
                    'current_order' => optional($table->activeOrder)->order_status,
                    'order_create_by' => optional($table->activeOrder)->created_by ?? null,
                    'assigned_waiter_id' => $table->assigned_waiter_id,
                    'current_guests' => optional($table->reservation)->guests_count,
                    'opened_at' => optional($table->activeOrder)->created_at,

                    'area' => optional($table->area)->name_en,
                    'floor' => optional($table->area)->floor ?? 1,

                    'assigned_waiter_id' => $table->assigned_waiter_id ?? null,

                    'reservation' => $table->reservation ? [
                        'customer_name' => $table->reservation->customer_name,
                        'customer_phone' => $table->reservation->customer_phone,
                        'reservation_time' => $table->reservation->reservation_time,
                        'guests_count' => $table->reservation->guests_count,
                    ] : null,
                ];
            })
        ]);
    }

    public function details($id)
    {
        $table = Table::with([
            'area',
            'reservation',
            'activeOrder.payment',
        ])->find($id);

        if (!$table) {
            return response()->json([
                'message' => 'Table not found id = ' . $id
            ], 404);
        }
        $table->load([
            'area',
            'reservation',
            'activeOrder.payment',
            // 'activeOrder.client',
        ]);

        $order = $table->activeOrder;
        $table_status = 'available';
        if ($table->table_status == 1) {
            $table_status = 'available';
        } else if ($table->table_status == 2) {
            $table_status = 'notAvailable';
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
                'status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'invoice_created' => !empty($order->id),
                'invoice_id' => $order->id,
                'paid_amount' => $order->payment?->sum('amount') ?? 0,
                'total_amount' => $order->final_total ?? 0,
                'items' => $order->sell_lines,
                'payment' => $order->payment,
            ] : null,
        ]);
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
