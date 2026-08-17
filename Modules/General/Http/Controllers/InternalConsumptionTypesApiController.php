<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Models\EstablishmentInternalConsumptionType;
use Modules\General\Transformers\InternalConsumptionTypeResource;

class InternalConsumptionTypesApiController extends Controller
{
    public function index(Request $request)
    {
        $establishmentId = (int) $request->input('establishment_id');
        if ($establishmentId <= 0) {
            return response()->json([
                'message' => __('establishment::responses.cashier_payment_establishment_required'),
            ], 422);
        }

        $types = EstablishmentInternalConsumptionType::query()
            ->forEstablishment($establishmentId)
            ->where('is_active', true)
            ->whereNotNull('account_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return InternalConsumptionTypeResource::collection($types);
    }
}
