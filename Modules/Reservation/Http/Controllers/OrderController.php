<?php

namespace Modules\Reservation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Category;
use Modules\Reservation\Models\Order;
use Modules\Reservation\Models\OrderItem;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function menu($id)
    {
        $tenant = tenancy()->tenant;
        $tenantId = $tenant->id;
        $table = ['tenant' => $tenantId, 'code' => $id];
        return view('reservation::order.menu' , compact('table')); 
    }

    public function menuQR()
    {
        return view('reservation::order.menuSimple'); 
    }

    public function products(Request $request)
    {
        // $request->validate([
        //     'establishment_id' => ['required', 'numeric']
        // ]);
        $establishment_id = $request->query('establishment_id', '');
        $categories = Category::where('active', 1)->whereHas('childrenWithProducts')
                        ->with(['childrenWithProducts' => function ($query) {
                            $query->with(['productsForSale']);
                        }])
                        ->get();
        return response()->json($categories);
    }

    public function generateOrdNo()
    {
        $prefix = 'ORD';
        // Get the last invoice number (if any)
        $lastOrd = Order::orderBy('no', 'desc')->first();
        
        // Check if there is a previous invoice
        $newOrdNumber = $prefix .'000001';  // Default starting number
        if ($lastOrd) {
            // Extract the number part from the last invoice
            preg_match('/(\d+)/', $lastOrd->no, $matches);
            $lastNumber = (int)$matches[0];
            $newOrdNumber = $prefix.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }
        
        return $newOrdNumber;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'establishment_id' => 'nullable|numeric',
            'table_code' => 'required|string',
            'order_status' => 'required|numeric',
        ]);
        $validated['no'] = $this->generateOrdNo();
        $validated['order_date'] = date("Y-m-d");
        $est = Establishment::where('is_main', 0)->first();
        $validated['establishment_id'] = $est->id;
        DB::transaction(function () use ($validated, $request) {
            $order= Order::create($validated);
            if(isset($request["items"]))
            { 
                foreach ($request["items"] as $item) {
                    $item['order_id'] = $order->id;
                    $item['item_total_price'] = $item['item_price'] * $item['quantity'];
                    OrderItem::create($item);
                }
            }
        });
    }

}
