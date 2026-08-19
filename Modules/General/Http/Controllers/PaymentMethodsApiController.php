<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\EstablishmentPaymentAccount;
use Modules\General\Transformers\PaymentMethodsResource;

class PaymentMethodsApiController extends Controller
{
    /**
     * Cashier payment methods for a branch (source of truth: est_establishment_payment_accounts).
     * Each method includes its active fees. Existing fields are unchanged.
     */
    public function index(Request $request)
    {
        $establishmentId = (int) $request->input('establishment_id');
        if ($establishmentId <= 0) {
            return response()->json([
                'message' => __('establishment::responses.cashier_payment_establishment_required'),
            ], 422);
        }

        $methods = EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->with([
                'assignedEstablishments:id',
                'activeFees',
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (EstablishmentPaymentAccount $row) => (bool) $row->accountIdForEstablishment($establishmentId))
            ->values();

        return PaymentMethodsResource::collection($methods);
    }
}
