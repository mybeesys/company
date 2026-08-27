<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\General\Models\Country;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;

class ReceiptsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TransactionPayments::query()
            ->with(['transaction', 'client'])
            ->where(function ($q) {
                $q->where('payment_type', 'debit')
                    ->orWhereHas('transaction', function ($q) {
                        $q->whereIn('type', ['sell']);
                    });
            })
            ->orderByDesc('id');

        if ($request->ajax()) {
            return TransactionPayments::getReceiptsTable($query);
        }

        $columns = TransactionPayments::getReceiptsColumns();

        $hasTransactions = $query->exists();

        return view('sales::receipts.index', compact('hasTransactions', 'columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clients = Contact::where('business_type', 'customer')->get();
        $accounts = AccountingAccount::forDropdown();
        $countries = Country::all();
        $supplier = false;
        $cost_centers = AccountingCostCenter::forDropdown();
        $duplicateDefaults = TransactionPayments::formDefaultsFromPaymentForDuplicate(
            (int) $request->query('from_payment', 0),
            false
        );

        return view('sales::receipts.create', compact('clients', 'cost_centers', 'supplier', 'accounts', 'countries', 'duplicateDefaults'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'min:1'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_on' => ['required', 'date'],
            // Account where money is received (cash/bank)
            'account_id' => ['required', 'integer', 'min:1'],
            'allocation_option' => ['required', 'in:specified_invoices,auto_allocate'],
            'transactions' => ['nullable', 'array'],
            'transactions.*' => ['integer', 'min:1'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
            'cost_center_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $contact = Contact::find($validated['client_id']);
        if (! $contact) {
            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }

        try {
            $this->assertReceiptContactHasAccount($contact);
            $this->assertReceiptPaymentAccount((int) $validated['account_id'], $contact);
            FiscalPeriodGatekeeper::assertPostable($validated['payment_on']);
            $transactions = $this->openTransactionsForReceipt(
                $contact,
                $validated['allocation_option'],
                $validated['transactions'] ?? []
            );
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong')
            );
        }

        try {
            DB::beginTransaction();
            $this->settleTransactions($transactions, $request, $contact);
            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->with(
                'error',
                config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong')
            );
        }

        if ($contact->business_type == 'customer') {
            return redirect()->route('receipts')->with('success', __('messages.add_successfully'));
        }

        return redirect()->route('suppliers-receipts')->with('success', __('messages.add_successfully'));
    }

    public function settleTransactions($transactions, $request, $contact)
    {

        $transactionUtil = new TransactionUtils;
        $contactUtils = new ContactUtils;
        $paid_amount = (float) str_replace(',', '', (string) $request->paid_amount);
        foreach ($transactions as $transaction) {
            $paidAmount = $transactionUtil->getTotalPaid($transaction->id);

            $remaining_amount = $transaction->final_total - $paidAmount;

            $transaction->remaining_amount = number_format(max(0, (float) $remaining_amount), 2, '.', '');
        }
        $transactions = $transactions->sortBy('transaction_date');

        $settledTransactions = [];
        foreach ($transactions as $transaction) {
            $remaining_amount = (float) ($transaction->remaining_amount ?? 0);

            if ($paid_amount == 0) {
                break;
            }

            if ($remaining_amount <= 0) {
                continue;
            }

            if ($paid_amount >= $remaining_amount) {
                // Pay the full remaining amount of this invoice.
                $paid_amount -= $remaining_amount;
                $request->merge(['paid_amount' => $remaining_amount]);
                // Paid in full
                $transactionUtil->addPaymentLines_journalEntry($transaction, $request);
                $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            } else {
                // Partial payment of the bill
                $request->merge(['paid_amount' => $paid_amount]);
                $transactionUtil->addPaymentLines_journalEntry($transaction, $request);
                $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                $paid_amount = 0;
            }

            $settledTransactions[] = $transaction;
        }

        // Any extra amount beyond open invoices becomes contact balance (once).
        if ($paid_amount > 0) {
            if (! $contact?->account_id) {
                throw ValidationException::withMessages([
                    'client_id' => $contact?->business_type === 'supplier'
                        ? __('purchases::lang.contact_missing_accounting_account')
                        : __('sales::lang.contact_missing_accounting_account'),
                ]);
            }

            if ($contact->business_type === 'supplier') {
                $contactUtils->addRemainingAmountToSupplierAccount($request->client_id, $paid_amount);
            } else {
                $contactUtils->addRemainingAmountToCustomerAccount($request->client_id, $paid_amount);
            }
        }

        return $settledTransactions;
    }

    /**
     * Show the specified resource.
     */
    public function getTransactions(Request $request, $clientId)
    {
        $transactionUtil = new TransactionUtils;

        $scope = (string) $request->query('scope', 'customer');
        $types = $scope === 'supplier' ? ['purchases', 'purchase'] : ['sell'];

        $transactions = Transaction::where('contact_id', $clientId)
            ->where('payment_status', '<>', 'paid')
            ->where('status', 'approved')
            ->whereIn('type', $types)
            ->orderBy('transaction_date')
            ->get();

        $filteredTransactions = [];

        foreach ($transactions as $transaction) {
            $paid_amount = $transactionUtil->getTotalPaid($transaction->id);

            $remaining_amount = $transaction->final_total - $paid_amount;

            if ($remaining_amount > 0) {
                $transaction->remaining_amount = number_format($remaining_amount, 2);
                $filteredTransactions[] = $transaction;
            }
        }

        return $filteredTransactions;
    }

    /**
     * Edit a single receipt/payment line (invoice settlement): form only; journal is replaced on update.
     */
    public function editPayment(TransactionPayments $payment)
    {
        $payment->load(['transaction', 'client']);
        $transaction = $this->assertEligibleReceiptPayment($payment);

        $supplier = in_array($transaction->type, ['purchases', 'purchase'], true);
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();
        $countries = Country::all();
        $contact = $payment->client;

        $costCenterId = AccountingAccountsTransaction::query()
            ->where('transaction_payment_id', $payment->id)
            ->whereNotNull('cost_center_id')
            ->value('cost_center_id');

        $transactionUtil = new TransactionUtils;
        $maxPaidAmount = $transactionUtil->maxAmountForReceiptPaymentEdit($payment);

        return view('sales::receipts.edit', compact(
            'payment',
            'transaction',
            'supplier',
            'accounts',
            'cost_centers',
            'countries',
            'contact',
            'costCenterId',
            'maxPaidAmount'
        ));
    }

    /**
     * Update receipt/payment: remove old journal, write new balanced entry, refresh invoice payment status.
     */
    public function updatePayment(Request $request, TransactionPayments $payment)
    {
        $transaction = $this->assertEligibleReceiptPayment($payment);

        $validated = $request->validate([
            'client_id' => ['required', 'integer', 'min:1'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_on' => ['required', 'date'],
            'account_id' => ['required', 'integer', 'min:1'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
            'cost_center_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if ((int) $validated['client_id'] !== (int) $payment->payment_for) {
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }

        $transactionUtil = new TransactionUtils;
        $max = $transactionUtil->maxAmountForReceiptPaymentEdit($payment);
        if ((float) $validated['paid_amount'] > $max + 0.000001) {
            return redirect()->back()->withInput()->with('error', __('messages.something_went_wrong'));
        }

        try {
            DB::beginTransaction();
            $transactionUtil->deleteReceiptPaymentJournal($payment);
            $request->merge($validated);
            $transactionUtil->repostJournalForExistingReceiptPayment($transaction->fresh(), $payment->fresh(), $request);
            $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->withInput()->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        }

        $redirectRoute = in_array($transaction->type, ['purchases', 'purchase'], true)
            ? 'suppliers-receipts'
            : 'receipts';

        return redirect()->route($redirectRoute)->with('success', __('messages.updated_successfully'));
    }

    /**
     * Delete receipt/payment line, remove linked journal, recalculate invoice payment status.
     */
    public function destroyPayment(TransactionPayments $payment)
    {
        $transaction = $this->assertEligibleReceiptPayment($payment);
        $redirectRoute = in_array($transaction->type, ['purchases', 'purchase'], true)
            ? 'suppliers-receipts'
            : 'receipts';

        try {
            DB::beginTransaction();
            $transactionUtil = new TransactionUtils;
            $transactionUtil->deleteReceiptPaymentJournal($payment);
            $tid = (int) $payment->transaction_id;
            $payment->delete();
            $transactionUtil->updatePaymentStatus($tid, $transaction->final_total);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->route($redirectRoute)->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        }

        return redirect()->route($redirectRoute)->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Post another payment identical to this line (same invoice, same amount/date/account when possible), with new refs.
     */
    /**
     * Duplicate = open create form with same input values; user chooses allocation / invoices again (no auto-post).
     */
    public function duplicatePayment(TransactionPayments $payment)
    {
        $transaction = $this->assertEligibleReceiptPayment($payment);
        $targetRoute = in_array($transaction->type, ['purchases', 'purchase'], true)
            ? 'create-suppliers-receipts'
            : 'create-receipts';

        return redirect()->route($targetRoute, ['from_payment' => $payment->id]);
    }

    private function assertEligibleReceiptPayment(TransactionPayments $payment): Transaction
    {
        $transaction = $payment->transaction;
        if (! $transaction) {
            abort(404);
        }
        if (! in_array($transaction->type, ['sell', 'purchases', 'purchase'], true)) {
            abort(403);
        }

        return $transaction;
    }

    private function assertReceiptContactHasAccount(Contact $contact): void
    {
        if ($contact->account_id) {
            return;
        }

        throw ValidationException::withMessages([
            'client_id' => $contact->business_type === 'supplier'
                ? __('purchases::lang.contact_missing_accounting_account')
                : __('sales::lang.contact_missing_accounting_account'),
        ]);
    }

    private function assertReceiptPaymentAccount(int $accountId, Contact $contact): void
    {
        if (! AccountingAccount::whereKey($accountId)->exists()) {
            throw ValidationException::withMessages([
                'account_id' => __('sales::lang.receipt_payment_account_invalid'),
            ]);
        }

        if ((int) $accountId === (int) $contact->account_id) {
            throw ValidationException::withMessages([
                'account_id' => __('sales::lang.receipt_payment_account_same_as_contact'),
            ]);
        }
    }

    /**
     * @param  list<int|string>  $specifiedIds
     */
    private function openTransactionsForReceipt(Contact $contact, string $allocationOption, array $specifiedIds): Collection
    {
        $allowedTypes = $contact->business_type === 'supplier'
            ? ['purchases', 'purchase']
            : ['sell'];

        $query = Transaction::query()
            ->where('contact_id', $contact->id)
            ->whereIn('type', $allowedTypes)
            ->where('status', 'approved')
            ->where('payment_status', '<>', 'paid');

        if ($allocationOption === 'specified_invoices') {
            $ids = array_values(array_unique(array_map('intval', $specifiedIds)));
            if ($ids === []) {
                throw ValidationException::withMessages([
                    'transactions' => __('sales::lang.receipt_select_open_invoices'),
                ]);
            }

            $transactions = (clone $query)->whereIn('id', $ids)->get();
            if ($transactions->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'transactions' => __('sales::lang.receipt_invalid_invoices_selected'),
                ]);
            }

            return $transactions;
        }

        return $query->orderBy('transaction_date')->get();
    }

    /**
     * @deprecated Use editPayment
     */
    public function edit($id)
    {
        $payment = TransactionPayments::findOrFail($id);

        return $this->editPayment($payment);
    }

    /**
     * @deprecated Use updatePayment
     */
    public function update(Request $request, $id)
    {
        $payment = TransactionPayments::findOrFail($id);

        return $this->updatePayment($request, $payment);
    }

    /**
     * @deprecated Use destroyPayment
     */
    public function destroy($id)
    {
        $payment = TransactionPayments::findOrFail($id);

        return $this->destroyPayment($payment);
    }
}
