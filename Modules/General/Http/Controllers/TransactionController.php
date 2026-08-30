<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Support\UnifiedInvoicePrintPresenter;
use Modules\General\Utils\TransactionUtils;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TransactionController extends Controller
{
    private function resolveInvoiceQrPayload(Transaction $transaction, object $company): string
    {
        $sync = ZatcaInvoiceSync::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', ZatcaInvoiceSync::STATUS_SYNCED)
            ->whereNotNull('qr_tlv')
            ->first();

        if ($sync?->qr_tlv) {
            return (string) $sync->qr_tlv;
        }

        $transactionUtil = new TransactionUtils;

        return $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction->transaction_date,
            $transaction->final_total,
            $transaction->tax_amount
        );
    }
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

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::find($id);
        if (! $transaction) {
            return redirect()->back();
        }

        // SVG avoids Imagick (PNG backend); safe for HTML and mPDF via print/export views
        $qrCode = QrCode::format('svg')->size(150)->generate($this->resolveInvoiceQrPayload($transaction, $company));

        return view('general::transactions.show', compact('transaction', 'qrCode', 'company'));
    }

    public function showReceiptsPayments($id)
    {

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = TransactionPayments::with([
            'transaction.sell_lines.product',
            'transaction.purchases_lines.product',
            'client.billingAddress',
        ])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }
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
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transaction = TransactionPayments::with([
            'transaction.sell_lines.product',
            'transaction.purchases_lines.product',
            'client.billingAddress',
        ])->find($id);
        if (! $transaction) {
            return redirect()->back();
        }

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
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transaction = Transaction::query()->findOrFail($id);
        $qrCode = QrCode::format('svg')->size(150)->margin(1)->generate(
            $this->resolveInvoiceQrPayload($transaction, $company)
        );
        $qrCode = $this->normalizeQrSvg((string) $qrCode);

        if (UnifiedInvoicePrintPresenter::supports($transaction->type)) {
            return view(
                'general::transactions.unified-invoice-print',
                UnifiedInvoicePrintPresenter::build($transaction, $company, $qrCode, false)
            );
        }

        return view('general::transactions.print', compact('transaction', 'qrCode', 'company'));
    }

    public function paymentPrint($id)
    {
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::with(['payment.account', 'payment.paymentMethod'])->find($id);
        $qrCode = QrCode::format('svg')->size(150)->generate($this->resolveInvoiceQrPayload($transaction, $company));

        return view('general::transactions.print-payments', compact('transaction', 'qrCode', 'company'));
    }

    public function exportPDF($id)
    {
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $transaction = Transaction::query()->findOrFail($id);
        $qrCode = QrCode::format('svg')->size(150)->margin(1)->generate(
            $this->resolveInvoiceQrPayload($transaction, $company)
        );
        $qrCode = $this->normalizeQrSvg((string) $qrCode);

        if (UnifiedInvoicePrintPresenter::supports($transaction->type)) {
            $data = UnifiedInvoicePrintPresenter::build($transaction, $company, $qrCode, true);
            $html = view('general::transactions.unified-invoice-print', $data)->render();

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'DejaVuSans',
                'default_font_size' => 10,
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
                'margin_top' => 10,
                'margin_bottom' => 14,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->SetTitle(($data['docTitleAr'] ?? 'Invoice').' - '.(string) $transaction->ref_no);
            $mpdf->WriteHTML($html);

            $filename = preg_replace('/[^\w\-]+/u', '-', (string) $transaction->ref_no).'.pdf';

            return $mpdf->Output($filename, 'D');
        }

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

    private function normalizeQrSvg(string $svg): string
    {
        $svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg) ?? $svg;
        $svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg) ?? $svg;

        return $svg;
    }

    public function exportTransactionPaymentPDF($id)
    {

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::with(['payment.account', 'payment.paymentMethod'])->find($id);
        $qrCode = QrCode::format('svg')->size(150)->generate($this->resolveInvoiceQrPayload($transaction, $company));

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
        $accounts = AccountingAccount::forDropdown();
        $paid_amount = $transactionUtil->getTotalPaid($id);
        $amount = $transaction->final_total - $paid_amount;
        if ($amount < 0) {
            $amount = 0;
        }
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $qrCode = QrCode::format('svg')->size(150)->generate($this->resolveInvoiceQrPayload($transaction, $company));

        return view('general::transactions.show-payments', compact('transaction', 'qrCode', 'company', 'accounts', 'amount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function addPayment(Request $request)
    {

        // return $request;
        $transactionUtil = new TransactionUtils;

        $transaction = Transaction::find($request->id);
        if ($request->paid_amount) {
            $transactionUtil->addPaymentLines_journalEntry($transaction, $request);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

        return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
    }
}
