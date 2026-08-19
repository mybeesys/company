<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\General\Transformers\EstablishmentServiceFeeResource;

class ServiceFeesApiController extends Controller
{
    /**
     * Cashier service fees for a branch (est_establishment_service_fees).
     * Distinct from legacy GET /api/serviceFees (global product catalog).
     */
    public function index(Request $request)
    {
        $establishmentId = (int) $request->input('establishment_id');
        if ($establishmentId <= 0) {
            return response()->json([
                'message' => __('establishment::responses.cashier_payment_establishment_required'),
                'code' => 'establishment_id_required',
            ], 422);
        }

        $fees = EstablishmentServiceFee::query()
            ->forEstablishment($establishmentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return EstablishmentServiceFeeResource::collection($fees);
    }
}
