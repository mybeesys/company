<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Reservation\Events\OrderCreated;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderItem;
use Modules\Reservation\Models\Reservation;
use Modules\Reservation\Models\Table;

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

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:reservation_tables,id',
            'order_status' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|numeric',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.item_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {

            $table = Table::findOrFail($validated['table_id']);

            if ($table->table_status != 0) {
                abort(409, 'Table not available for reservation');
            }

            $reservation = Reservation::create([
                'table_id' => $table->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'reservation_time' => $validated['reservation_time'],
                'guests_count' => $validated['guests_count'],
                'status' => 'active',
            ]);

            $table->update([
                'table_status' => 2,
                // 'assigned_waiter_id' => Auth::user()->id

            ]);

            $order = Order::create([
                'no' => $this->generateOrdNo(),
                'order_date' => now(),
                'order_status' => $validated['order_status'],
                'table_id' => $table->id,
                'establishment_id' => $table->area->establishment_id,
            ]);

            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'item_price' => $item['item_price'],
                    'item_total_price' => $item['quantity'] * $item['item_price'],
                ]);
            }

          event(new OrderCreated($order));

            return response()->json([
                'status' => true,
                'order_id' => $order->id,
                'order_no' => $order->no
            ]);
        });
    }



    public function generateOrdNo()
    {
        $prefix = 'ORD';
        // Get the last invoice number (if any)
        $lastOrd = Order::orderBy('no', 'desc')->first();

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
