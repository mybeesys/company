<?php

namespace Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
use Modules\General\Models\TransactionSellLine;
use Modules\General\Utils\ActionUtil;
use Modules\General\Utils\TransactionUtils;
use Modules\Product\Models\Product;
use Modules\Product\Models\UnitTransfer;
use Modules\Sales\Utils\SalesUtile;

class PurchasesReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $transactionsQuery = Transaction::where('type', 'purchases-return');

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

        $clients = Contact::where('business_type', 'supplier')->get();

        return view('purchases::purchases-return.index', compact('columns', 'clients', 'transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction && $transaction->type === 'purchases' && $transaction->isDraft()) {
            return redirect()->route('purchase-invoices')->with('error', __('purchases::lang.cannot_return_draft_invoice'));
        }

        $taxes = Tax::all();

        $products = Product::with(['unitTransfers' => function ($query) {
            $query->whereNull('unit2');
        }])->get();

        $invoicePrecheckConfig = $this->buildPurchasesReturnPrecheckConfig();

        return view('purchases::purchases-return.create', compact('transaction', 'products', 'taxes', 'invoicePrecheckConfig'));
    }

    public function createReturnInvoice()
    {
        $actionUtil = new ActionUtil;
        $actionUtil->saveOrUpdateAction('create_po', 'add_sell', 'create-purchases-invoice');

        $clients = Contact::where('business_type', 'supplier')->get();
        // $taxes = Tax::all();
        $payment_terms = SalesUtile::paymentTerms();
        $paymentMethods = SalesUtile::paymentMethods();
        $orderStatuses = SalesUtile::orderStatuses();
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $establishments = Establishment::where('is_main', 0)->get();
        $countries = Country::all();
        $transaction = Transaction::find(0);
        $taxes = Tax::all();
        $po = false;
        $settings = Setting::getNotesAndTermsConditions();

        $products = Product::where('active', 1)->take(25)->get();
        $Latest_event = Actions::where('user_id', Auth::user()->id)->where('type', 'save_purchases')->first();
        if (! $Latest_event) {
            $actionUtil = new ActionUtil;
            $Latest_event = $actionUtil->saveOrUpdateAction('save_purchases', 'save_purchases', 'save');
        }
        $invoicePrecheckConfig = $this->buildPurchasesReturnPrecheckConfig();

        return view('purchases::purchases-return.create-return', compact('clients', 'settings', 'Latest_event', 'establishments', 'po', 'taxes', 'transaction', 'countries', 'payment_terms', 'orderStatuses', 'products', 'paymentMethods', 'accounts', 'cost_centers', 'invoicePrecheckConfig'));
    }

    private function buildPurchasesReturnPrecheckConfig(): array
    {
        $missing = [];
        if (! AccountsRoting::where('type', 'purchases_purchase')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب المشتريات' : 'Purchases account';
        }
        if (! AccountsRoting::where('type', 'purchases_purchase_return')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب مردود المشتريات' : 'Purchases return account';
        }
        if (! AccountsRoting::where('type', 'purchases_vat_calculation')->value('account_id')) {
            $missing[] = app()->getLocale() === 'ar' ? 'حساب ضريبة المشتريات' : 'Purchases VAT account';
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
                'contactMissingAccount' => __('purchases::lang.contact_missing_accounting_account'),
            ],
        ];
    }

    public function storeReturnInvoice(Request $request)
    {
        try {
            $this->validateStandalonePurchaseReturnRequest($request);

            $ref_no = SalesUtile::generateReferenceNumber('purchases-return');
            $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
            $transactionUtil = new TransactionUtils;
            DB::beginTransaction();

            $establishment_id = $this->resolvePurchaseReturnEstablishmentId($request);
            $termsNotesData = null;
            if (isset($request->toggle_terms_notes)) {
                $termsNotesData = json_encode([
                    'terms_en' => request('terms_and_conditions_en'),
                    'terms_ar' => request('terms_and_conditions_ar'),
                    'note_en' => request('note_en'),
                    'note_ar' => request('note_ar'),
                ]);
            }

            $transaction = Transaction::create([
                'type' => 'purchases-return',
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
                'status' => $request->input('status', 'approved'),
                'notice' => $request->notice,
                'establishment_id' => $establishment_id,
                'settings_terms_notes' => $termsNotesData,

            ]);

            $products = $this->normalizePurchaseReturnProducts($request);

            foreach ($products as $product) {
                $this->createPurchaseReturnLine((int) $transaction->id, $product);
            }

            $request = $transactionUtil->mergeInvoicePaymentAmount($transaction, $request);
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            DB::commit();

            return redirect()->route('purchases-return')->with('success', __('messages.add_successfully'));
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong')
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //   return $request;
        try {
            $purchases = Transaction::findOrFail($request->transaction_id);

            if (! $purchases) {
                return redirect()->route('invoices')->with('error', __('messages.something_went_wrong'));
            }

            $ref_no = SalesUtile::generateReferenceNumber('purchases-return');
            $invoiced_discount_type = $request->invoice_discount ? $request->invoiced_discount_type : null;
            $transactionUtil = new TransactionUtils;
            DB::beginTransaction();

            $establishment_id = $this->resolvePurchaseReturnEstablishmentId($request);
            $transaction = Transaction::create([
                'type' => 'purchases-return',
                'invoice_type' => $request->invoice_type,
                // 'due_date' => $request->due_date,
                'parent_id' => $purchases->id,
                'transaction_date' => now(),
                'contact_id' => $purchases->contact_id,
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

            ]);

            $products = $this->normalizePurchaseReturnProducts($request);

            foreach ($products as $product) {
                $this->createPurchaseReturnLine((int) $transaction->id, $product);
            }

            if ($transaction) {
                $request = $transactionUtil->mergeInvoicePaymentAmount($transaction, $request);

                $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
            }

            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            DB::commit();

            return redirect()->route('purchase-invoices')->with('success', __('messages.add_successfully'));
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (Exception $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong')
            );
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

    private function createPurchaseReturnLine(int $transactionId, object $product): void
    {
        $productId = (int) ($product->products_id ?? $product->product_id ?? 0);
        if ($productId <= 0) {
            throw ValidationException::withMessages([
                'products' => app()->getLocale() === 'ar'
                    ? 'يرجى اختيار صنف صالح لكل سطر.'
                    : 'Please choose a valid product on each line.',
            ]);
        }

        $discountAmount = (float) ($product->discount ?? 0);
        $discountType = $discountAmount > 0 ? (string) ($product->discount_type ?? 'fixed') : null;
        $unitId = $this->resolvePurchaseUnitId($productId, $product->unit ?? null);

        TransactionSellLine::create([
            'transaction_id' => $transactionId,
            'product_id' => $productId,
            'qyt' => $product->qty,
            'unit_id' => $unitId,
            'unit_price_before_discount' => $product->unit_price,
            'unit_price' => $product->unit_price,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'unit_price_inc_tax' => $product->total_after_vat,
            'tax_id' => $product->tax_vat,
            'tax_value' => $product->vat_value,
            'total_before_vat' => $product->total_before_vat,
        ]);
    }

    private function validateStandalonePurchaseReturnRequest(Request $request): void
    {
        $request->validate([
            'client_id' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
            'due_date' => 'required|date',
            'invoice_type' => 'required|in:cash,due,credit',
            'products' => 'required|array|min:1',
            'totalAfterVat' => 'required|numeric|min:0.01',
        ]);

        $products = $this->normalizePurchaseReturnProducts($request);
        if ($products === []) {
            throw ValidationException::withMessages([
                'products' => app()->getLocale() === 'ar'
                    ? 'يرجى إضافة صنف واحد على الأقل.'
                    : 'Please add at least one product line.',
            ]);
        }

        $supplier = Contact::find($request->client_id);
        if (! $supplier || ! $supplier->account_id) {
            throw ValidationException::withMessages([
                'client_id' => __('purchases::lang.contact_missing_accounting_account'),
            ]);
        }

        $invoiceType = in_array((string) $request->invoice_type, ['due', 'credit'], true) ? 'due' : 'cash';
        if ($invoiceType === 'cash' && ! $request->filled('cash_account')) {
            throw ValidationException::withMessages([
                'cash_account' => app()->getLocale() === 'ar'
                    ? 'يرجى اختيار حساب الدفع للمردود النقدي.'
                    : 'Please select a payment account for the cash purchase return.',
            ]);
        }
    }

    /**
     * @return list<object>
     */
    private function normalizePurchaseReturnProducts(Request $request): array
    {
        $rawProducts = $request->input('products', []);
        if (! is_array($rawProducts)) {
            return [];
        }

        $filtered = [];
        foreach ($rawProducts as $product) {
            if (! is_array($product)) {
                continue;
            }

            $productId = (int) ($product['products_id'] ?? $product['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $filtered[] = $product;
        }

        return json_decode(json_encode($filtered), false) ?: [];
    }

    private function resolvePurchaseReturnEstablishmentId(Request $request): ?int
    {
        $establishmentId = $request->storehouse;
        $mainEstablishment = Establishment::notMain()->active()->first();

        if ($mainEstablishment && (int) $request->storehouse === (int) $mainEstablishment->id) {
            return (int) $mainEstablishment->id;
        }

        return $establishmentId ? (int) $establishmentId : null;
    }

    private function resolvePurchaseUnitId(int $productId, $rawUnit): ?int
    {
        if (is_null($rawUnit) || $rawUnit === '') {
            $defaultTransferId = UnitTransfer::query()
                ->where('product_id', $productId)
                ->orderByDesc('default')
                ->value('id');
            if ($defaultTransferId) {
                return (int) $defaultTransferId;
            }

            throw ValidationException::withMessages([
                'products' => app()->getLocale() === 'ar'
                    ? "لم يتم اختيار وحدة للصنف #{$productId} ولا يوجد وحدة افتراضية معرفة له."
                    : "Unit is missing for product #{$productId} and no default unit is configured.",
            ]);
        }

        if (is_numeric($rawUnit)) {
            return (int) $rawUnit;
        }

        $rawUnit = trim((string) $rawUnit);
        $unitTransferId = UnitTransfer::query()
            ->where('product_id', $productId)
            ->where(function ($q) use ($rawUnit) {
                $q->where('unit1', $rawUnit)->orWhere('unit2', $rawUnit);
            })
            ->orderByDesc('default')
            ->value('id');

        if ($unitTransferId) {
            return (int) $unitTransferId;
        }

        throw ValidationException::withMessages([
            'products' => app()->getLocale() === 'ar'
                ? "تعذر تحديد وحدة الصنف #{$productId}. يرجى اختيار وحدة صحيحة من القائمة."
                : "Unable to resolve unit for product #{$productId}. Please choose a valid unit from list.",
        ]);
    }
}
