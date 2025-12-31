<?php

namespace Modules\Reservation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Reservation\Models\Table;
use Modules\Reservation\Transformers\TableResource;

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
            'data' => $tables->map(fn($table) => [
                'id' => $table->id,
                'name' => $table->code,
                'capacity' => $table->steating_capacity,
                'status' => $table->table_status,

                'current_order_id' => optional($table->activeOrder)->id,
                'current_order' => optional($table->activeOrder)->order_status,
                'order_create_by' => optional($table->activeOrder)->created_by ?? null,

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
            ])
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('reservation::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
