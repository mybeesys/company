<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Sales\Utils\SalesUtile;

class SellReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $transactionsQuery = Transaction::where('type', 'sell-return');

        if ($request->ajax()) {
            if ($request->filled('favorite')) {
                $transactionsQuery->whereHas('favorites', function ($query) {
                    $query->where('user_id', Auth::user()->id);
                });
            }

            $transactions = $transactionsQuery->get();
            return Transaction::getSellsTable($transactions);
        }
        $transaction = $transactionsQuery->get();


        $columns = Transaction::getsSellsColumns();


        return view('sales::sell-return.index', compact('columns', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $transaction = Transaction::find($id);
        $taxes = Tax::all();

        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();

        return view('sales::sell-return.create', compact('transaction', 'products', 'taxes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;
        try {
            $sell = Transaction::findOrFail($request->transaction_id);

            if (!$sell) {
                return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
            }

            $ref_no =  SalesUtile::generateReferenceNumber('sell-return');
            $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();
            $main_establishment = Establishment::notMain()->active()->first();
            $establishment_id = $request->storehouse;
            if ($request->storehouse == $main_establishment->id) {
                $establishment_id = $main_establishment->id;
            }
            $transaction =   Transaction::create([
                'type' => 'sell-return',
                'invoice_type' => $request->invoice_type,
                // 'due_date' => $request->due_date,
                'parent_id' => $sell->id,
                'transaction_date' => now(),
                'contact_id' => $sell->contact_id,
                'cost_center' => $request->cost_center ?? null,
                'discount_amount' => $request->invoice_discount,
                'discount_type' => $invoiced_discount_type,
                'total_before_tax' => $request->totalBeforeVat,
                'totalAfterDiscount' => $request->totalAfterDiscount,
                'tax_amount' => $request->totalVat,
                'final_total' => $request->totalAfterVat,
                'created_by' => Auth::user()->id,
                'description' => $request->invoice_note,
                'ref_no' => $ref_no,
                'status' => 'final',
                'notice' => $request->notice,
                'establishment_id' => $establishment_id,
                'establishment_id' => $establishment_id,


            ]);

            $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            $products = json_decode(json_encode($request->products));

            foreach ($products as $product) {
                $discount_type =  null;
                TransactionePurchasesLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->product_id,
                    'qyt' => $product->qty,
                    'unit_id' => $product->unit ?? 0,
                    'unit_price_before_discount' => $product->unit_price,
                    'unit_price' => $product->unit_price,
                    'discount_type' => $discount_type,
                    'discount_amount' => 0,
                    'unit_price_inc_tax' => $product->total_after_vat,
                    'tax_id' => $product->tax_vat,
                    'tax_value' => $product->vat_value,
                    'total_before_vat' => $product->total_before_vat,
                ]);
            }
            DB::commit();
            return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
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