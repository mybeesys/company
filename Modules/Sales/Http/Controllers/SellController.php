<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Models\AccountsRoting;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Actions;
use Modules\General\Models\Country;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Inventory\Models\Transfer;
use Modules\Product\Http\Controllers\Api\ProductController;
use Modules\Product\Models\Product;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\Transformers\Collections\ProductCollection;
use Modules\Sales\Utils\SalesUtile;
//use Illuminate\Support\Facades\Log;

class SellController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function salesDashbord()
    {
        $today = \Carbon\Carbon::today();
        $yesterday = \Carbon\Carbon::yesterday();

        $todaySales = Transaction::where('type', 'sell')
            ->whereDate('transaction_date', $today)
            ->sum('final_total');

        $yesterdaySales = Transaction::where('type', 'sell')
            ->whereDate('transaction_date', $yesterday)
            ->sum('final_total');

        $dailyChangePercent =
            $yesterdaySales != 0 ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 2) : 0;

        $formattedTodaySales = number_format($todaySales);

        $salesTypes = Transaction::select('type', DB::raw('SUM(final_total) as total'))
            ->groupBy('type')
            ->get();
        $monthlyTrend = Transaction::selectRaw('MONTH(transaction_date) as month, SUM(final_total) as total')
            ->whereYear('transaction_date', date('Y'))
            ->groupBy('month')
            ->get();

        $stats = Transaction::where('type', 'sell')->selectRaw('COUNT(*) as total_invoices')
            ->selectRaw('SUM(final_total) / COUNT(*) as average_invoice')
            ->selectRaw('COUNT(DISTINCT contact_id) as active_customers')
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()])
            ->first();


        $monthlySales = Transaction::where('type', 'sell')
            ->selectRaw('MONTH(transaction_date) as month, SUM(final_total) as total')
            ->whereYear('transaction_date', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $salesData = [];
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthSales = $monthlySales->firstWhere('month', $i);
            $salesData[] = $monthSales ? $monthSales->total : 0;

            $months[] = __(date('F', mktime(0, 0, 0, $i, 1)));
        }

        $transactions = Transaction::whereIn('type', ['sell', 'quotation', 'sell-return'])->latest()
            ->take(10)
            ->get();



        $receiptsStats = TransactionPayments::where(function ($q) {
            $q->where('payment_type', 'debit')->orWhereHas('transaction', function ($q) {
                $q->whereIn('type', ['sell']);
            });
        })

            ->selectRaw(
                '
    COUNT(*) as total_receipts,
    SUM(amount) as total_collected,
    SUM(CASE WHEN MONTH(paid_on) = MONTH(CURRENT_DATE()) THEN amount ELSE 0 END) as monthly_collected,
    SUM(CASE WHEN is_return = 1 THEN amount ELSE 0 END) as returned_amount
',
            )
            ->first();

        $recentReceipts = TransactionPayments::with(['transaction', 'account'])
            ->where(function ($q) {
                $q->where('payment_type', 'debit')->orWhereHas('transaction', function ($q) {
                    $q->whereIn('type', ['sell']);
                });
            })

            ->where('payment_type', '!=', 'is_return')
            ->orderBy('paid_on', 'desc')
            ->take(10)
            ->get();

        $monthlyCollections = TransactionPayments::where(function ($q) {
            $q->where('payment_type', 'debit')->orWhereHas('transaction', function ($q) {
                $q->whereIn('type', ['sell']);
            });
        })
            ->selectRaw(
                '
    MONTH(paid_on) as month,
    SUM(amount) as total
',
            )
            ->where('payment_type', '!=', 'is_return')
            ->whereYear('paid_on', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $paymentMethods = TransactionPayments::where(function ($q) {
            $q->where('payment_type', 'debit')->orWhereHas('transaction', function ($q) {
                $q->whereIn('type', ['sell']);
            });
        })
            ->selectRaw(
                '
    method,
    SUM(amount) as total
',
            )
            ->where('payment_type', '!=', 'is_return')
            ->groupBy('method')
            ->get();

        $monthNames = $monthlyCollections->map(function ($item) {
            return __(date('F', mktime(0, 0, 0, $item->month, 1)));
        });


        return view('sales::sell.dashboard', compact(
            'months',
            'monthNames',
            'paymentMethods',
            'monthlyCollections',
            'recentReceipts',
            'receiptsStats',
            'transactions',
            'salesData',
            'stats',
            'dailyChangePercent',
            'yesterdaySales',
            'formattedTodaySales'
        ));
    }

    public function index(Request $request)
    {
        $transactionsQuery = Transaction::where('type', 'sell');


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




            $transactions = $transactionsQuery->orderBy('id', 'desc')->get();
            return Transaction::getSellsTable($transactions);
        }

        $transaction = $transactionsQuery->get();
        $columns = Transaction::getsSellsColumns();

        $quotations = Transaction::where('type', 'quotation')->where('po_status', '<>', 'completed')->get();

        $Latest_event = Actions::where('user_id', Auth::id())->where('type', 'create_sell')->first();

        if (!$Latest_event) {
            $actionUtil = new ActionUtil();
            $Latest_event = $actionUtil->saveOrUpdateAction('create_sell', 'add_sell', 'create-invoice');
        }

        $clients =  Contact::where('business_type', 'customer')->get();
        return view('sales::sell.index', compact('columns', 'clients', 'Latest_event', 'transaction', 'quotations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $actionUtil = new ActionUtil();
        $actionUtil->saveOrUpdateAction('create_sell', 'add_sell', 'create-invoice');
        $clients = Contact::where('business_type', 'customer')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts =  AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();
        $quotation = false;
        $quotationId = $request->input('quotation_id');
        $transaction = Transaction::find($quotationId);
        if ($quotationId > 0) {

            $actionUtil->saveOrUpdateAction('create_sell', 'convert-to-invoice', '#');
        }


        $settings = Setting::getNotesAndTermsConditions();

        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();


        $products = Product::productsForSell();

        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'save_sell')->first();
        if (!$Latest_event) {
            $actionUtil = new ActionUtil();
            $Latest_event = $actionUtil->saveOrUpdateAction('save_sell', 'save_sell', 'save');
        }

        return view('sales::sell.create', compact('clients', 'settings', 'Latest_event', 'transaction', 'quotation', 'taxes', 'establishments', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers'));
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        // try {
        $actionUtil = new ActionUtil();
        $contactUtils = new ContactUtils();
        $accountUtil = new AccountingUtil();
        $actionUtil->saveOrUpdateAction('save_sell', 'save_sell', $request->action);


        $transactionUtil = new TransactionUtils();
        DB::beginTransaction();
        $ref_no =  SalesUtile::generateReferenceNumber('sell');

        $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
        $main_establishment = Establishment::notMain()->active()->first();
        $establishment_id = $request->storehouse;
        if ($request->storehouse == $main_establishment->id) {
            $establishment_id = $main_establishment->id;
        }
        $termsNotesData = null;
        if (isset($request->toggle_terms_notes)) {
            $termsNotesData = json_encode([
                'terms_en' => request('terms_and_conditions_en'),
                'terms_ar' => request('terms_and_conditions_ar'),
                'note_en' => request('note_en'),
                'note_ar' => request('note_ar'),
            ]);
        }

        $quotation_id = null;
        if ($request->quotation_id) {
            $quotation_id = $request->quotation_id;
        }
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
            'establishment_id' => $establishment_id,
            'settings_terms_notes' => $termsNotesData,

            'parent_id' => $quotation_id,

        ]);


        $products = json_decode(json_encode($request->products));

        foreach ($products as $product) {
            $discount_type = $product->discount ? $product->discount_type : null;


            if (!auth()->user()->hasDashboardPermission('sales.Allow Sale Without Stock.create')) {
                $product_inventorie = DB::table('product_products')
                    ->select(
                        'product_products.id',
                        DB::raw('COALESCE(SUM(product_inventories.qty), 0) as inventory_qty')
                    )
                    ->leftJoin('product_inventories', 'product_products.id', '=', 'product_inventories.product_id')
                    ->where('product_products.id', $product->products_id)
                    ->groupBy('product_products.id')
                    ->first();
                $inventory_qty = $product_inventorie->inventory_qty ?? 0;

                if ($inventory_qty < $product->qty) {
                    continue;
                }
            }


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

            //$is_recipe_yield = Product::find($product->products_id)->recipe_yield;
            //if ($is_recipe_yield) {


            $recipeProducts = RecipeProduct::with('products')->where('product_id', $product->products_id)->get();

            if ($recipeProducts->isNotEmpty()) {
                foreach ($recipeProducts as $recipeProduct) {
                    $ingredient = $recipeProduct->products;
                    if ($ingredient) {
                        $discount_type = $ingredient->discount ? $ingredient->discount_type : null;
                        $price_with_tax = $ingredient->type == 'ingredint' ? $ingredient->orderPriceWithTax : $ingredient->price_with_tax;

                        TransactionSellLine::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $ingredient->id,
                            'qyt' => $product->qty * $recipeProduct->quantity,
                            'unit_id' => $recipeProduct->unit_transfer_id,
                            'unit_price_before_discount' => $ingredient->price ?? 0,
                            'unit_price' => $ingredient->price ?? 0,
                            'discount_type' => 'fixd',
                            'discount_amount' => 0,
                            'unit_price_inc_tax' => $price_with_tax,
                            'tax_id' => $ingredient->tax_id,
                            'tax_value' => $price_with_tax - ($ingredient->price ?? 0),
                            'total_before_vat' => $ingredient->price ?? 0,
                            'is_show' => 0,
                        ]);
                    }
                }
            }
        }


        if ($quotation_id) {
            $this->updatePurchaseOrderStatus(
                $quotation_id

            );
        }
        // return $request;
        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        } else {
            $acc_trans_mapping = new AccountingAccTransMapping();
            $ref_number = $accountUtil->generateReferenceNumber('journal_entry');
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = '';
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();
            $acc_trans_mapping_id = $acc_trans_mapping->id;

            $sales_sales = AccountsRoting::where('type', 'sales_sales')->first();
            $sales_vat_calculation = AccountsRoting::where('type', 'sales_vat_calculation')->first();

            $client = Contact::find($request->client_id);
            $transactionPayment = new \stdClass();

            $transactionPayment->paid_on = Carbon::parse(now())->format('Y-m-d H:i:s');
            $transactionPayment->account_id = $client->account_id;
            $transactionPayment->created_by = Auth::user()->id;
            $transactionPayment->created_by = Auth::user()->id;
            $transactionPayment->transaction_id = $transaction->id;
            $transactionPayment->id = null;

            $transactionPayment->amount = $transaction->final_total;

            $accountUtil->saveAccountRouteTransaction(
                'debit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $sales_sales->account_id;
            $transactionPayment->amount = $transaction->total_before_tax;

            $accountUtil->saveAccountRouteTransaction(
                'credit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );

            $transactionPayment->account_id = $sales_vat_calculation->account_id;
            $transactionPayment->amount = $transaction->tax_amount;

            $accountUtil->saveAccountRouteTransaction(
                'credit',
                $transactionPayment,
                $transaction,
                $acc_trans_mapping_id,
                $request
            );
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        $totalOutstanding =  $transactionUtil->contactTotalOutstanding($transaction);

        $msg = __('messages.add_successfully');
        $status = 'success';
        if ($totalOutstanding) {
            $credit_limit =  Contact::find($transaction->contact_id)->credit_limit;
            if ($credit_limit && $credit_limit < $totalOutstanding) {
                $msg = __('messages.Added successfully, but the customer exceeded');
                $status = 'error';
            }
        }

        DB::commit();
        if ($request->action == 'save_print') {
            return redirect()->route('transaction-print', $transaction->id)->with($status, $msg);
        } else if ($request->action == 'save_add') {
            return redirect()->route('create-invoice')->with($status, $msg);
        } else {
            return redirect()->route('invoices')->with($status, $msg);
        }
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
        // }
    }



    public function validateInvoiceRequest($request)
    {
        $rules = [
            'products' => ['required', 'array', 'min:1'],
            'products.*.products_id' => ['required'],
        ];

        $messages = [
            'products.required' => 'يجب إرسال المنتجات.',
            'products.array' => 'المنتجات يجب أن تكون قائمة.',
            'products.min' => 'يجب إضافة منتج واحد على الأقل.',
            'products.*.products_id.required' => 'يجب أن يحتوي كل منتج على رقم تعريف.',
        ];

        $validatedData = $request->validate($rules, $messages);

        return $validatedData;
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    function updatePurchaseOrderStatus($quotation_id)
    {
        $poTransaction = Transaction::find($quotation_id);

        if (!$poTransaction) {
            return;
        }

        $poLines = TransactionSellLine::where('transaction_id', $quotation_id)->get();

        if ($poLines->isEmpty()) {
            $poTransaction->po_status = 'pending';
            $poTransaction->save();
            return;
        }

        $invoiceIds = Transaction::where('parent_id', $quotation_id)->pluck('id');
        $invoiceLines = TransactionSellLine::whereIn('transaction_id', $invoiceIds)->get();

        $overallStatus = 'completed';
        $productsStatus = [];

        foreach ($poLines as $poLine) {
            $requestedQty = $poLine->qyt;

            $purchasedQty = $invoiceLines
                ->where('product_id', $poLine->product_id)
                ->sum('qyt');

            $remainingQty = max(0, $requestedQty - $purchasedQty);

            if ($purchasedQty >= $requestedQty) {
                $lineStatus = 'completed';
            } elseif ($purchasedQty > 0 && $purchasedQty < $requestedQty) {
                $lineStatus = 'partial';
                $overallStatus = 'partial';
            } else {
                $lineStatus = 'pending';
                if ($overallStatus === 'completed') {
                    $overallStatus = 'partial';
                }
            }

            $poLine->line_status = $lineStatus;
            $poLine->remaining_qty = $remainingQty;
            $poLine->save();

            $productsStatus[] = [
                'product_id' => $poLine->product_id,
                'requested' => $requestedQty,
                'purchased' => $purchasedQty,
                'remaining' => $remainingQty,
                'line_status' => $lineStatus,
            ];
        }

        if ($invoiceIds->isEmpty()) {
            $overallStatus = 'pending';
        }

        $poTransaction->po_status = $overallStatus;
        $poTransaction->save();

        return [
            'po_status' => $overallStatus,
            'products' => $productsStatus,
        ];
    }
}
