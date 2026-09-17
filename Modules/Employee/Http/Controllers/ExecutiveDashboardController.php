<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Services\ExecutiveDashboardService;

class ExecutiveDashboardController extends Controller
{
    public function __construct(protected ExecutiveDashboardService $service) {}

    public function index()
    {
        return view('employee::dashboard.executive-v2');
    }

    public function bootstrap(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'data' => $this->service->bootstrap($request),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    public function data(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'ok' => true,
                'data' => $this->service->summary($request),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    public function widget(Request $request, string $widget): JsonResponse
    {
        if (! in_array($widget, ExecutiveDashboardService::WIDGETS, true)) {
            return response()->json(['ok' => false, 'error' => 'Unknown widget'], 404);
        }

        try {
            $filters = $this->service->resolveFilters($request);
            $data = match ($widget) {
                'kpis' => $this->service->kpis($filters),
                'financial-trend' => $this->service->financialTrend($filters),
                'expense-distribution' => $this->service->expenseDistribution($filters),
                'branch-sales' => $this->service->branchSales($filters),
                'sales-analysis' => $this->service->salesAnalysis($filters),
                'product-health' => $this->service->productHealth($filters),
                'inventory-health' => $this->service->inventoryHealth($filters),
                'accounting-health' => $this->service->accountingHealth($filters),
                'insights' => $this->service->insights($filters),
                'alerts' => $this->service->alerts($filters),
                'drilldown' => $this->service->drilldown($filters, (string) $request->input('source', 'sales')),
                default => [],
            };

            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 200);
        }
    }
}
