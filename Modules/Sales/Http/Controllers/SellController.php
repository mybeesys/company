<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Country;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Http\Controllers\Api\ProductController;
use Modules\Product\Models\Product;
use Modules\Product\Models\Transformers\Collections\ProductCollection;
use Modules\Sales\Utils\SalesUtile;

class SellController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transaction = Transaction::where('type', 'sell')->get();

        if ($request->ajax()) {

            $transaction = Transaction::where('type', 'sell')->get();
            return  Transaction::getSellsTable($transaction);
        }

        $columns = Transaction::getsSellsColumns();
        return view('sales::sell.index', compact('columns', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Contact::where('business_type', 'customer')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main',0)->get();
        $countries = Country::all();


        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();
        return view('sales::sell.create', compact('clients', 'taxes','establishments','countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        // try {


        $transactionUtil = new TransactionUtils();
        DB::beginTransaction();
        $ref_no =  SalesUtile::generateReferenceNumber('sell');

        $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
        $transaction =   Transaction::create([
            'type' => 'sell',
            'invoice_type' => $request->invoice_type,
            'due_date' => $request->due_date,
            'transaction_date' => $request->transaction_date,
            'contact_id' => $request->client_id,
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
            'status' => $request->status,
            'notice' => $request->notice,
            // 'payment_terms',

        ]);


        $products = json_decode(json_encode($request->products));

        foreach ($products as $product) {
            $discount_type = $product->discount ? $product->discount_type : null;
            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->products_id,
                'qyt' => $product->qty,
                'unit_price_before_discount' => $product->unit_price,
                'unit_price' => $product->unit_price,
                'discount_type' => $discount_type,
                'discount_amount' => $product->discount,
                'unit_price_inc_tax' => $product->total_after_vat,
                'tax_id' => $product->tax_vat,
                'tax_value' => $product->vat_value,
                'total_before_vat'=>$product->total_before_vat,
            ]);
        }
        // return $request->paid_amount;
        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        }

        //Update payment status
        // $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $request->paid_amount);
        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        //  if(due)

        DB::commit();
        return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
        // }
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