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
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Sales\Utils\SalesUtile;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $transactionsQuery = Transaction::where('type', 'quotation');

        if ($request->ajax()) {

            $transactionsQuery
                ->when($request->filled('favorite'), function ($query) {
                    $query->whereHas('favorites', fn($q) => $q->where('user_id', Auth::id()));
                })
                ->when($request->filled('customer'), fn($query) => $query->where('contact_id', $request->customer))
                ->when($request->filled('payment_status'), fn($query) => $query->where('payment_status', $request->payment_status))
                ->when($request->filled('due_date_range'), function ($query) use ($request) {
                    $dueDateRange = trim($request->due_date_range);
                    $dates = explode(' إلى ', $dueDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('due_date', [$dates[0], $dates[1]]);
                    }
                })
                ->when($request->filled('sale_date_range'), function ($query) use ($request) {
                    $saleDateRange = trim($request->sale_date_range);
                    $dates = explode(' إلى ', $saleDateRange);
                    if (count($dates) == 2) {
                        $query->whereBetween('transaction_date', [$dates[0], $dates[1]]);
                    }
                });


            $transactions = $transactionsQuery->get();
            return Transaction::getSellsTable($transactions);
        }

        $transaction = $transactionsQuery->get();

        $columns = Transaction::getsQuotationColumns();
        $clients =  Contact::where('business_type', 'customer')->get();
        $page='quotations';

        return view('sales::quotation.index', compact('columns', 'page','clients', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $actionUtil = new ActionUtil();
        $actionUtil->saveOrUpdateAction('create_sell', 'convert-to-invoice', '#');

        $clients = Contact::where('business_type', 'customer')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();

        $quotation = true;
        $transaction = null;
        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();
        return view('sales::quotation.create', compact('clients', 'transaction', 'quotation', 'taxes', 'establishments', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $transactionUtil = new TransactionUtils();
            DB::beginTransaction();
            $ref_no =  SalesUtile::generateReferenceNumber('quotation');

            $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
            $main_establishment = Establishment::notMain()->active()->first();
            $establishment_id = $request->storehouse;
            if ($request->storehouse == $main_establishment->id) {
                $establishment_id = $main_establishment->id;
            }
            $transaction =   Transaction::create([
                'type' => 'quotation',
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
                'establishment_id' => $establishment_id,


            ]);


            $products = json_decode(json_encode($request->products));

            foreach ($products as $product) {
                $discount_type = $product->discount ? $product->discount_type : null;
                TransactionSellLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->products_id,
                    'qyt' => $product->qty,
                    'unit_id' => $product->unit,
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
            return redirect()->route('quotations')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('quotations')->with('error', __('messages.something_went_wrong'));
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