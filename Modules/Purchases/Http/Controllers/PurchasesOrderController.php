<?php

namespace Modules\Purchases\Http\Controllers;

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
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Sales\Utils\SalesUtile;

class PurchasesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transaction = Transaction::where('type', 'purchases-order')->get();

        if ($request->ajax()) {

            $transaction = Transaction::where('type', 'purchases-order')->get();
            return  Transaction::getSellsTable($transaction);
        }

        $columns = Transaction::getsQuotationColumns();
        return view('purchases::purchase-order.index', compact('columns', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $actionUtil = new ActionUtil();

        $clients = Contact::where('business_type', 'supplier')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();

        $po = true;
        $po_id = false;
        $po_id = $request->input('po_id');
        $transaction = Transaction::find($po_id);
        if ($po_id > 0) {

            $actionUtil->saveOrUpdateAction('create_po', 'convert-to-invoice', '#');
        }



        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();
        return view('purchases::purchase-order.create', compact('clients', 'transaction', 'po', 'taxes', 'establishments', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();
            $ref_no =  SalesUtile::generateReferenceNumber('purchases-order');

            $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
            $main_establishment = Establishment::notMain()->active()->first();

            $establishment_id =$request->storehouse;
            if ($request->storehouse == $main_establishment->id)
            {
                $establishment_id = $main_establishment->id;
            }
            $transaction =   Transaction::create([
                'type' => 'purchases-order',
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
                'status' => 'draft',
                'notice' => $request->notice,
                'establishment_id'=>$establishment_id,

            ]);


            $products = json_decode(json_encode($request->products));

            foreach ($products as $product) {
                $discount_type = $product->discount ? $product->discount_type : null;
                TransactionePurchasesLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->products_id,
                    'qyt' => $product->qty,
                    'unit_id'=>$product->unit,
                    'unit_price_before_discount' => $product->unit_price,
                    'unit_price' => $product->unit_price,
                    'discount_type' => $discount_type,
                    'discount_amount' => $product->discount,
                    'unit_price_inc_tax' => $product->total_after_vat,
                    'tax_id' => $product->tax_vat,
                    'tax_value' => $product->vat_value,
                    'total_before_vat' => $product->total_before_vat,
                ]);
            }


            DB::commit();
            return redirect()->route('purchases-order')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('purchases-order')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('purchases::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('purchases::edit');
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