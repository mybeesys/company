<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\General\Support\WhatsAppInvoiceSharePresenter;
use Modules\Sales\Support\SalesAccess;

class WhatsAppInvoiceShareController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->with('client')->findOrFail($id);
        SalesAccess::authorizeTransaction($transaction, 'print');

        if (! WhatsAppInvoiceSharePresenter::supports($transaction)) {
            return response()->json([
                'message' => __('general::lang.whatsapp_share_unsupported'),
            ], 422);
        }

        $payload = WhatsAppInvoiceSharePresenter::build($transaction);

        if ($request->filled('phone')) {
            $phone = WhatsAppInvoiceSharePresenter::normalizePhone((string) $request->input('phone'));
            $message = (string) $request->input('message', $payload['message']);
            $payload['phone'] = $phone;
            $payload['phone_display'] = (string) $request->input('phone');
            $payload['message'] = $message;
            $payload['wa_url'] = WhatsAppInvoiceSharePresenter::waUrl($phone, $message);
        }

        return response()->json($payload);
    }
}
