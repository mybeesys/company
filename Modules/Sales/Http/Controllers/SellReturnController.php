<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Models\AccountsRoting;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Actions;
use Modules\General\Models\Country;
use Modules\General\Models\Setting;
use Modules\General\Models\Tax;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Sales\Services\SellReturnRepairService;
use Modules\Sales\Utils\SalesUtile;
use Modules\Zatca\Services\ZatcaAutoSyncService;
use Modules\Zatca\Services\ZatcaInvoiceGuard;

class SellReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $transactionsQuery = Transaction::where('type', 'sell-return');

        if ($request->ajax()) {
            $transactionsQuery
                ->when($request->filled('favorite'), function ($query) {
                    $query->whereHas('favorites', fn ($q) => $q->where('user_id', Auth::id()));
                })
                ->when($request->filled('customer'), fn ($query) => $query->where('contact_id', $request->customer))
                ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->payment_status))
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

        $clients = Contact::where('business_type', 'customer')->get();

        return view('sales::sell-return.index', compact('columns', 'clients', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $transaction = Transaction::with([
            'sell_lines' => fn ($q) => $q->where('is_show', 1)
                ->where(function ($query) {
                    $query->whereNull('parent_id')
                        ->orWhere('parent_id', '')
                        ->orWhere('parent_id', 0);
                })
                ->orderBy('id'),
            'sell_lines.product.unitTransfers' => fn ($q) => $q->whereNull('unit2'),
        ])->findOrFail($id);
        $taxes = Tax::all();

        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();

        $invoicePrecheckConfig = $this->buildSellReturnPrecheckConfig();

        return view('sales::sell-return.create', compact('transaction', 'products', 'taxes', 'invoicePrecheckConfig'));
    }

    public function createSellReturn(Request $request)
    {

        $actionUtil = new ActionUtil;
        $actionUtil->saveOrUpdateAction('create_sell-return', 'add_sell-return', 'create-invoice');
        $clients = Contact::where('business_type', 'customer')->get();
        $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts = AccountingAccount::forDropdown();
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

        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'save_sell')->first();
        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('save_sell', 'save_sell', 'save');
        }

        $invoicePrecheckConfig = $this->buildSellReturnPrecheckConfig();

        return view('sales::sell-return.create-return', compact('clients', 'settings', 'Latest_event', 'transaction', 'quotation', 'taxes', 'establishments', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers', 'invoicePrecheckConfig'));
    }

    private function buildSellReturnPrecheckConfig(): array
    {
        $missing = [];
        if (! AccountsRoting::where('type', 'sales_sell_return')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب مردود المبيعات' : 'Sales return account';
        }
        if (! AccountsRoting::where('type', 'sales_vat_calculation')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب ضريبة المبيعات' : 'Sales VAT account';
        }

        return [
            'missingAccounts' => $missing,
            'messages' => [
                'missingAccountsHeader' => app()->getLocale() === 'ar'
                    ? 'إعدادات الحسابات غير مكتملة، يرجى مراجعة توجيه الحسابات:'
                    : 'Accounting setup is incomplete, please review Accounts Routing:',
                'missingUnit' => app()->getLocale() === 'ar'
                    ? 'يرجى اختيار وحدة لكل صنف قبل الحفظ.'
                    : 'Please select unit for each product before saving.',
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;
        // try {
        $sell = Transaction::findOrFail($request->transaction_id);

        if (! $sell) {
            return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
        }

        try {
            app(ZatcaInvoiceGuard::class)->assertParentSyncedForReturn($sell);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $ref_no = SalesUtile::generateReferenceNumber('sell-return');
        $transactionUtil = new TransactionUtils;
        DB::beginTransaction();
        $main_establishment = Establishment::notMain()->active()->first();

        // Prefer the parent sell warehouse so inventory lands on the same establishment.
        $establishment_id = $request->storehouse ?: $sell->establishment_id;
        if (! $establishment_id && $main_establishment) {
            $establishment_id = $main_establishment->id;
        }

        $invoiceType = $request->invoice_type ?: $sell->invoice_type ?: 'due';
        if (! in_array((string) $invoiceType, ['cash', 'due', 'credit'], true)) {
            $invoiceType = 'due';
        }

        $products = json_decode(json_encode($request->products ?? []));
        $inputLines = [];
        foreach ($products ?? [] as $product) {
            $qty = (float) ($product->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $inputLines[] = [
                'product_id' => $product->product_id ?? $product->products_id ?? 0,
                'qty' => $qty,
                'unit_id' => $product->unit ?? 0,
                'unit_price' => $product->unit_price ?? 0,
                'discount_type' => ! empty($product->discount) ? ($product->discount_type ?? 'fixed') : null,
                'discount_amount' => $product->discount ?? 0,
                'tax_id' => $product->tax_id ?? $product->tax_vat ?? null,
            ];
        }

        if ($inputLines === []) {
            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }

        // Server-owned math: proportional share of parent invoice discount (never trust browser totals).
        $computed = SellReturnRepairService::computeCreditNoteFromParent($sell, $inputLines);
        $header = $computed['header'];

        $transaction = Transaction::create([
            'type' => 'sell-return',
            'invoice_type' => $invoiceType,
            // 'due_date' => $request->due_date,
            'parent_id' => $sell->id,
            'transaction_date' => now(),
            'contact_id' => $sell->contact_id,
            'cost_center' => $request->cost_center ?? $sell->cost_center ?? null,
            'discount_amount' => $header['discount_amount'],
            'discount_type' => $header['discount_type'],
            'total_before_tax' => $header['total_before_tax'],
            'totalAfterDiscount' => $header['totalAfterDiscount'],
            'tax_amount' => $header['tax_amount'],
            'final_total' => $header['final_total'],
            'created_by' => Auth::user()->id,
            'description' => $request->invoice_note,
            'ref_no' => $ref_no,
            'status' => 'approved',
            'notice' => $request->notice,
            'establishment_id' => $establishment_id,
        ]);

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        foreach ($computed['lines'] as $line) {
            TransactionePurchasesLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $line['product_id'],
                'qyt' => $line['qty'],
                'unit_id' => $line['unit_id'] ?? 0,
                'unit_price_before_discount' => $line['unit_price'],
                'unit_price' => $line['unit_price'],
                'discount_type' => $line['discount_type'],
                'discount_amount' => $line['discount_amount'],
                'unit_price_inc_tax' => $line['unit_price_inc_tax'],
                'tax_id' => $line['tax_id'],
                'tax_value' => $line['tax_value'],
                'total_before_vat' => $line['total_before_vat'],
            ]);
        }

        if ($transaction) {
            $request['amount'] = $transaction->final_total;
            // Ensure payment_for resolves to the customer for AR postings.
            if (! $request->filled('client_id')) {
                $request->merge(['client_id' => $sell->contact_id]);
            }

            try {
                $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
            } catch (\Throwable $e) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }
        if ($transaction) {
            $this->updateSalesReturnStatus(
                $sell->id

            );
        }
        DB::commit();

        app(ZatcaAutoSyncService::class)->queueIfInstant((int) $transaction->id);

        return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
    }

    public function storeSellReturn(Request $request)
    {
        // return $request;
        // try {
        $ref_no = SalesUtile::generateReferenceNumber('sell-return');
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
            DB::beginTransaction();
        }
        $transaction = Transaction::create([
            'type' => 'sell-return',
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
            'status' => 'approved',
            'notice' => $request->notice,
            'establishment_id' => $establishment_id,
            'settings_terms_notes' => $termsNotesData,

        ]);
        $transactionUtil = new TransactionUtils;

        $products = json_decode(json_encode($request->products));

        foreach ($products as $product) {
            $discount_type = null;
            TransactionePurchasesLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->products_id,
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

        $this->distributeReturnToInvoiceLines($transaction);

        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        $msg = __('messages.add_successfully');
        $status = 'success';
        DB::commit();

        return redirect()->route('sell-return')->with($status, $msg);
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     return redirect()->route('sell-return')->with('error', __('messages.something_went_wrong'));
        // }
    }

    /**
     * Show the specified resource.
     */
    public function distributeReturnToInvoiceLines($returnTransaction)
    {
        $invoice_id = $returnTransaction;
        if (! $invoice_id) {
            return;
        }

        $returnLines = TransactionePurchasesLine::where('transaction_id', $returnTransaction->id)->get();
        $transactionIds = Transaction::where('type', 'sell')
            ->where('contact_id', $returnTransaction->contact_id)
            ->pluck('id');

        $invoiceLines = TransactionSellLine::whereIn('transaction_id', $transactionIds)

            ->orderBy('id')
            ->get();

        foreach ($returnLines as $returnLine) {
            $remainingToReturn = $returnLine->qyt;
            $product_id = $returnLine->product_id;

            foreach ($invoiceLines->where('product_id', $product_id) as $sellLine) {
                if ($remainingToReturn <= 0) {
                    break;
                }

                $returnQty = min($sellLine->remaining_qty ?? $sellLine->qyt, $remainingToReturn);
                $sellLine->remaining_qty = ($sellLine->remaining_qty ?? $sellLine->qyt) - $returnQty;

                // تحديث الحالة
                if ($sellLine->remaining_qty == 0) {
                    $sellLine->line_status = 'completed';
                } elseif ($sellLine->remaining_qty < $sellLine->qyt) {
                    $sellLine->line_status = 'partial';
                } else {
                    $sellLine->line_status = 'pending';
                }

                $sellLine->save();

                $remainingToReturn -= $returnQty;
            }
        }
    }

    public function updateSalesReturnStatus($invoice_id)
    {
        $invoice = Transaction::find($invoice_id);

        if (! $invoice) {
            return;
        }

        $invoiceLines = TransactionSellLine::where('transaction_id', $invoice->id)->get();

        $returnIds = Transaction::where('parent_id', $invoice->id)
            ->where('type', 'sell-return')
            ->pluck('id');
        $returnLines = TransactionePurchasesLine::whereIn('transaction_id', $returnIds)->get();

        $productsStatus = [];
        $returnedCount = 0;
        $returnedQty = 0;

        $allCompleted = true;
        $anyReturned = false;

        foreach ($invoiceLines as $sellLine) {
            $soldQty = $sellLine->qyt;

            $returnedQtyForProduct = $returnLines
                ->where('product_id', $sellLine->product_id)
                ->sum('qyt');

            $remainingQty = max(0, $soldQty - $returnedQtyForProduct);

            if ($returnedQtyForProduct == 0) {
                $lineStatus = 'pending';
                $allCompleted = false;
            } elseif ($returnedQtyForProduct < $soldQty) {
                $lineStatus = 'partial';
                $allCompleted = false;
                $anyReturned = true;
            } else {
                $lineStatus = 'completed';
                $anyReturned = true;
            }

            if ($returnedQtyForProduct > 0) {
                $returnedCount++;
                $returnedQty += $returnedQtyForProduct;
            }

            $sellLine->line_status = $lineStatus;

            $sellLine->remaining_qty = $remainingQty;
            $sellLine->save();

            $productsStatus[] = [
                'product_id' => $sellLine->product_id,
                'qyt' => $soldQty,
                'returned_qty' => $returnedQtyForProduct,
                'remaining_qty' => $remainingQty,
                'line_status' => $lineStatus,
            ];
        }

        if ($allCompleted && $anyReturned) {
            $overallStatus = 'completed';
        } elseif ($anyReturned) {
            $overallStatus = 'partial';
        } else {
            $overallStatus = 'pending';
        }

        $invoice->po_status = $overallStatus;
        $invoice->save();

        return [
            'po_status' => $overallStatus,
            'returned_count' => $returnedCount,
            'returned_qty' => $returnedQty,
            'products' => $productsStatus,
        ];
    }
}
