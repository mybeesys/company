<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;

class SalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('report::sales.index');
    }

    public function getSalesData(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $sales = Transaction::selectRaw('DATE(transaction_date) as date, COUNT(*) as count')
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($sales as $sale) {
            $labels[] = Carbon::parse($sale->date)->format('M d');
            $counts[] = $sale->count;
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'sales_count' => $counts
            ]
        ]);
    }
}
