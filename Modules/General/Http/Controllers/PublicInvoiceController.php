<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Accounting\Support\LedgerStatementPresenter;
use Modules\General\Models\Transaction;
use Modules\General\Support\InvoicePdfRenderer;
use Modules\General\Support\WhatsAppInvoiceSharePresenter;

class PublicInvoiceController extends Controller
{
    public function show(Request $request, Transaction $transaction)
    {
        $this->assertShareable($transaction);
        $transaction->loadMissing('client');

        $company = InvoicePdfRenderer::company();
        $localeAr = str_starts_with(strtolower((string) app()->getLocale()), 'ar');
        $companyName = $company
            ? (LedgerStatementPresenter::companyDisplayName($company, $localeAr) ?: trim((string) ($company->name ?? '')))
            : (string) config('app.name', 'My Bee');
        $logoSrc = $company ? LedgerStatementPresenter::companyLogoSrc($company, false) : null;
        $pdfUrl = WhatsAppInvoiceSharePresenter::signedPdfUrl($transaction);
        $customerName = trim((string) ($transaction->client?->name ?? '')) ?: '—';

        return view('general::transactions.public-invoice', [
            'transaction' => $transaction,
            'company' => $company,
            'companyName' => $companyName !== '' ? $companyName : 'My Bee',
            'logoSrc' => $logoSrc,
            'pdfUrl' => $pdfUrl,
            'customerName' => $customerName,
            'issueDate' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
            'amount' => number_format((float) ($transaction->final_total ?: 0), 2),
            'localeAr' => $localeAr,
            'expiresAt' => $request->query('expires')
                ? Carbon::createFromTimestamp((int) $request->query('expires'))->format('Y-m-d H:i')
                : now()->addDays(WhatsAppInvoiceSharePresenter::LINK_TTL_DAYS)->format('Y-m-d H:i'),
        ]);
    }

    public function pdf(Transaction $transaction)
    {
        $this->assertShareable($transaction);

        return InvoicePdfRenderer::download($transaction);
    }

    private function assertShareable(Transaction $transaction): void
    {
        if (! WhatsAppInvoiceSharePresenter::supports($transaction)) {
            abort(404);
        }
    }
}
