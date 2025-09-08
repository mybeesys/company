<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;
use Modules\Inventory\Models\TransactionUtil;
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

        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::find($id);
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);
        return view('general::transactions.show', compact('transaction', 'qrCode', 'company'));
    }

    public function showReceiptsPayments($id)
    {

        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = TransactionPayments::find($id);
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);

        $title = 'sdfsf';
        return view('general::transactions.show-receipts-payments', compact('transaction', 'qrCode', 'title', 'company'));
    }


    public function print($id)
    {

        $transaction = Transaction::find($id);
        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);

        return view('general::transactions.print', compact('transaction', 'qrCode', 'company'));
    }


    public function paymentPrint($id)
    {
        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::find($id);
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);

        return view('general::transactions.print-payments', compact('transaction', 'qrCode', 'company'));
    }


    public function exportPDF($id)
    {
        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::find($id);

        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);


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

        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());

        $transaction = Transaction::find($id);
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);

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

        $transactionUtil = new TransactionUtils();
        $transaction = Transaction::find($id);
        $accounts =  AccountingAccount::forDropdown();
        $paid_amount = $transactionUtil->getTotalPaid($id);
        $amount = $transaction->final_total - $paid_amount;
        if ($amount < 0) {
            $amount = 0;
        }
        $company =  DB::connection('mysql')->table('companies')->find(get_company_id());
        $transactionUtil = new TransactionUtils();
        $qrData = $transactionUtil->generateZatcaQr(
            $company->name,
            $company->tax_number,
            $transaction,
            $transaction->final_total,
            $transaction->tax_amount
        );

        $qrCode = QrCode::size(150)->generate($qrData);

        return view('general::transactions.show-payments', compact('transaction', 'qrCode', 'company', 'accounts', 'amount'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function addPayment(Request $request)
    {


        // return $request;
        $transactionUtil = new TransactionUtils();

        $transaction = Transaction::find($request->id);
        if ($request->paid_amount) {
            $transactionUtil->addPaymentLines_journalEntry($transaction, $request);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);


        return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
    }

    
}