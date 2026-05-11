<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\General\Models\Country;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;
use Modules\ClientsAndSuppliers\Models\Contact as ContactModel;

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
    public function create()
    {
        $clients = Contact::where('business_type', 'customer')->get();
        $accounts = AccountingAccount::forDropdown();
        $countries = Country::all();
        $supplier = false;
        $cost_centers = AccountingCostCenter::forDropdown();

        return view('sales::receipts.create', compact('clients', 'cost_centers', 'supplier', 'accounts', 'countries'));
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
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }

        try {
            DB::beginTransaction();
        if ($validated['allocation_option'] == 'specified_invoices') {
            $ids = $validated['transactions'] ?? [];
            if (count($ids) === 0) {
                return redirect()->back()->with('error', __('messages.something_went_wrong'));
            }
            $allowedTypes = $contact->business_type === 'supplier' ? ['purchases', 'purchase'] : ['sell'];
            $transactions = Transaction::where('contact_id', $validated['client_id'])
                ->whereIn('type', $allowedTypes)
                ->where('status', 'approved')
                ->where('payment_status', '<>', 'paid')
                ->whereIn('id', $ids)
                ->get();

            $this->settleTransactions($transactions, $request, $contact);
        } else {
            if ($contact->business_type == 'customer') {
                $transactions = Transaction::where('contact_id', $validated['client_id'])
                    ->where('status', 'approved')
                    ->where('payment_status', '<>', 'paid')
                    ->whereIn('type', ['sell'])
                    ->orderBy('transaction_date')
                    ->get();
            } else {
                $transactions = Transaction::where('contact_id', $validated['client_id'])
                    ->where('status', 'approved')
                    ->where('payment_status', '<>', 'paid')
                    ->whereIn('type', ['purchases', 'purchase'])
                    ->orderBy('transaction_date')
                    ->get();
            }
            $this->settleTransactions($transactions, $request, $contact);
        }

        DB::commit();
        if ($contact->business_type == 'customer') {
            return redirect()->route('receipts')->with('success', __('messages.add_successfully'));
        }

        return redirect()->route('suppliers-receipts')->with('success', __('messages.add_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->back()->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        }
    }

    public function settleTransactions($transactions, $request, $contact)
    {

        $transactionUtil = new TransactionUtils;
        $contactUtils = new ContactUtils;
        $paid_amount = $request->paid_amount;
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

            $paid_amount = (float) str_replace(',', '', (string) $paid_amount);
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

        // Any extra amount beyond open invoices becomes customer balance (once).
        if ($paid_amount > 0) {
            if ($contact && $contact->business_type === 'supplier') {
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
