<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('general::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        // SVG avoids Imagick (PNG backend); safe for HTML and mPDF via print/export views
        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        return view('general::transactions.show', compact('transaction', 'qrCode', 'company'));
    }

    public function showReceiptsPayments($id)
    {
        $transaction = TransactionPayments::with([
            'transaction.sell_lines.product',
            'transaction.purchases_lines.product',
            'client.billingAddress',
        ])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction->transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $txDate = $transaction->paid_on ?? now();
        $amount = (float) ($transaction->amount ?? 0);
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $txDate,
            number_format($amount, 2, '.', ''),
            '0.00'
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        $title = $transaction->transaction?->type === 'purchases' || $transaction->transaction?->type === 'purchase'
            ? __('menuItemLang.supplier_receipt')
            : __('menuItemLang.customer_receipt');

        return view('general::transactions.show-receipts-payments', compact('transaction', 'qrCode', 'title', 'company'));
    }

    public function exportReceiptsPaymentsPDF($id)
    {
        $transaction = TransactionPayments::with([
            'transaction.sell_lines.product',
            'transaction.purchases_lines.product',
            'client.billingAddress',
        ])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction->transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $txDate = $transaction->paid_on ?? now();
        $amount = (float) ($transaction->amount ?? 0);
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $txDate,
            number_format($amount, 2, '.', ''),
            '0.00'
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        $title = $transaction->transaction?->type === 'purchases' || $transaction->transaction?->type === 'purchase'
            ? __('menuItemLang.supplier_receipt')
            : __('menuItemLang.customer_receipt');

        // Use a dedicated PDF view that mirrors the show page (no JS)
        $html = view('general::transactions.receipts-payments-pdf', compact('transaction', 'qrCode', 'title', 'company'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        $filename = ($transaction->payment_ref_no ?: ('payment-'.$transaction->id)).'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function print($id)
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        return view('general::transactions.print', compact('transaction', 'qrCode', 'company'));
    }

    public function paymentPrint($id)
    {
        $transaction = Transaction::with(['payment.account', 'payment.paymentMethod'])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        return view('general::transactions.print-payments', compact('transaction', 'qrCode', 'company'));
    }

    public function exportPDF($id)
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        $html = view('general::transactions.print', compact('transaction', 'qrCode', 'company'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output($transaction->ref_no, 'D');
    }

    public function exportTransactionPaymentPDF($id)
    {
        $transaction = Transaction::with(['payment.account', 'payment.paymentMethod'])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils;
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        $html = view('general::transactions.print-payments', compact('transaction', 'qrCode', 'company'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output($transaction->ref_no, 'D');
    }

    public function showPayments($id)
    {
        $transactionUtil = new TransactionUtils;
        $transaction = Transaction::with(['payment.account', 'payment.paymentMethod'])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        $accounts = AccountingAccount::forDropdown();
        $paid_amount = $transactionUtil->getTotalPaid($id);
        $amount = $transaction->final_total - $paid_amount;
        if ($amount < 0) {
            $amount = 0;
        }
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::format('svg')->size(150)->generate($qrData);

        return view('general::transactions.show-payments', compact('transaction', 'qrCode', 'company', 'accounts', 'amount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function addPayment(Request $request)
    {
        $transactionUtil = new TransactionUtils;

        $transaction = Transaction::find($request->id);
        if (! $transaction) {
            return redirect()->back();
        }
        $this->abortUnlessTransactionEntitled($transaction);

        if ($request->paid_amount) {
            $transactionUtil->addPaymentLines_journalEntry($transaction, $request);
        }

        $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        $redirectRoute = app(\App\Services\EntitlementGate::class)
            ->moduleForTransactionType($transaction->type) === 'purchases'
            ? 'purchase-invoices'
            : 'invoices';

        return redirect()->route($redirectRoute)->with('success', __('messages.add_successfully'));
    }

    protected function abortUnlessTransactionEntitled(?Transaction $transaction): void
    {
        if (! $transaction) {
            abort(403, __('responses.entitlement_forbidden'));
        }

        if (! app(\App\Services\EntitlementGate::class)->transactionTypeAllowed($transaction->type)) {
            abort(403, __('responses.entitlement_forbidden'));
        }
    }
}
