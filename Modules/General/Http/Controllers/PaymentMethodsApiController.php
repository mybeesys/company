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
            ->where('establishment_id', $establishmentId)
            ->whereNotNull('account_id')
            ->orderBy('id')
            ->get();

        return PaymentMethodsResource::collection($methods);
    }
}
