<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryCostingService;

class InvoiceInventoryCostsApiController extends Controller
{
    /**
     * Read-only outbound inventory cost preview for cashier invoice lines.
     * Same engine as web POST invoice-inventory-costs (average / FIFO / LIFO or product card cost).
     */
    public function store(Request $request, InventoryCostingService $costing)
    {
        $establishmentId = (int) $request->input('establishment_id');
        if ($establishmentId <= 0) {
            return response()->json([
                'message' => __('establishment::responses.cashier_payment_establishment_required'),
                'code' => 'establishment_id_required',
            ], 422);
        }

        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.product_id' => 'nullable|integer',
            'lines.*.qty' => 'nullable|numeric',
            'lines.*.unit_id' => 'nullable|integer',
        ]);

        $lines = [];
        foreach ($validated['lines'] as $line) {
            $lines[] = [
                'product_id' => (int) ($line['product_id'] ?? 0),
                'qty' => (float) ($line['qty'] ?? 0),
                'unit_id' => ! empty($line['unit_id']) ? (int) $line['unit_id'] : null,
            ];
        }

        $method = $costing->getMethod();

        return response()->json([
            'data' => $costing->previewOutboundCosts($establishmentId, $lines),
            'method' => $method !== '' ? $method : 'product_card',
            'engine_active' => $costing->isActive(),
            'method_label_ar' => $this->methodLabel($method, 'ar'),
            'method_label_en' => $this->methodLabel($method, 'en'),
        ]);
    }

    private function methodLabel(string $method, string $locale): string
    {
        $key = match ($method) {
            'fifo' => 'general::general.fifo',
            'lifo' => 'general::general.lifo',
            'average' => 'general::general.average',
            default => 'general::general.inventory_costing_method_not_set',
        };

        return (string) trans($key, [], $locale);
    }
}
