<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\General\Models\Transaction;
use Modules\General\Support\InvoicePdfRenderer;
use Modules\General\Support\WhatsAppCloudApiClient;
use Modules\General\Support\WhatsAppInvoiceSharePresenter;
use Modules\Sales\Support\SalesAccess;
use RuntimeException;
use Throwable;

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
            if ($payload['cloud_configured']) {
                $message = WhatsAppInvoiceSharePresenter::stripLinksFromMessage($message);
            }
            $payload['phone'] = $phone;
            $payload['phone_display'] = (string) $request->input('phone');
            $payload['message'] = $message;
            $payload['wa_url'] = WhatsAppInvoiceSharePresenter::waUrl($phone, $message);
        }

        return response()->json($payload);
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->with('client')->findOrFail($id);
        SalesAccess::authorizeTransaction($transaction, 'print');

        if (! WhatsAppInvoiceSharePresenter::supports($transaction)) {
            return response()->json([
                'message' => __('general::lang.whatsapp_share_unsupported'),
            ], 422);
        }

        if (! WhatsAppCloudApiClient::isConfigured()) {
            return response()->json([
                'message' => __('general::lang.whatsapp_cloud_not_configured'),
            ], 422);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'message' => ['nullable', 'string', 'max:4000'],
        ]);

        $phone = WhatsAppInvoiceSharePresenter::normalizePhone($validated['phone']);
        if ($phone === '') {
            return response()->json([
                'message' => __('general::lang.whatsapp_share_phone_required'),
            ], 422);
        }

        $payload = WhatsAppInvoiceSharePresenter::build($transaction);
        $body = WhatsAppInvoiceSharePresenter::stripLinksFromMessage(
            (string) ($validated['message'] ?? $payload['message'])
        );

        try {
            $pdf = InvoicePdfRenderer::binary($transaction);
            // Prefer signed PDF URL for the CTA button (forces download).
            $result = WhatsAppCloudApiClient::sendInvoiceShare(
                $phone,
                $body,
                $pdf['binary'],
                $pdf['filename'],
                $payload['pdf_url'] ?? null,
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('general::lang.whatsapp_cloud_send_failed'),
            ], 500);
        }

        return response()->json([
            'message' => __('general::lang.whatsapp_cloud_send_success'),
            'mode' => $result['mode'],
            'ref_no' => $payload['ref_no'],
            'phone' => $phone,
        ]);
    }
}
