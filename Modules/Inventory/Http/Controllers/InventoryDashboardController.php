<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;
use Mpdf\Mpdf;

class InventoryDashboardController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Establishment::where('is_main', 0)->get(['id', 'name']);
        $warehouseIds = $warehouses->pluck('id');
        $warehousesCount = $warehouses->count();
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }
        $selectedWarehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $movementType = $request->input('movement_type', 'all');
        $effectiveWarehouseIds = $selectedWarehouseId
            ? $warehouseIds->contains($selectedWarehouseId) ? collect([$selectedWarehouseId]) : collect([])
            : $warehouseIds;

        $dateFilter = function ($query) use ($startDate, $endDate) {
            $query->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$startDate, $endDate]);
        };

        $transferCount = Transaction::query()
            ->where('type', 'TRANSFER')
            ->where('status', 'approved')
            ->where('parent_id', null)
            ->when($selectedWarehouseId, fn ($q) => $q->where('establishment_id', $selectedWarehouseId))
            ->where($dateFilter)
            ->count();
        $wasteCount = Transaction::query()
            ->where('type', 'WASTE')
            ->where('status', 'approved')
            ->when($selectedWarehouseId, fn ($q) => $q->where('establishment_id', $selectedWarehouseId))
            ->where($dateFilter)
            ->count();
        $prepCount = Transaction::query()
            ->where('type', 'PREP')
            ->where('status', 'approved')
            ->where('parent_id', null)
            ->when($selectedWarehouseId, fn ($q) => $q->where('establishment_id', $selectedWarehouseId))
            ->where($dateFilter)
            ->count();

        $inventoryRows = DB::table('product_inventories as pi')
            ->join('product_products as p', 'p.id', '=', 'pi.product_id')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->select(
                'pi.establishment_id',
                'pi.product_id',
                'pi.qty',
                'p.name_ar',
                'p.name_en'
            )
            ->get()
            ->groupBy('establishment_id');

        foreach ($warehouses as $warehouse) {
            $rows = $inventoryRows->get($warehouse->id, collect());
            $most = $rows->sortByDesc('qty')->first();
            $least = $rows->sortBy('qty')->first();

            $warehouse->mostStockedProductName = $most ? ($most->name_ar ?: $most->name_en) : null;
            $warehouse->mostStockedQuantity = $most ? (float) $most->qty : 0;
            $warehouse->leastStockedProductName = $least ? ($least->name_ar ?: $least->name_en) : null;
            $warehouse->leastStockedQuantity = $least ? (float) $least->qty : 0;
            $warehouse->totalStockQuantity = (float) $rows->sum('qty');
            $warehouse->negativeItemsCount = $rows->where('qty', '<', 0)->count();
            $warehouse->zeroOrNegativeItemsCount = $rows->where('qty', '<=', 0)->count();
        }

        $totalStockQuantity = (float) DB::table('product_inventories as pi')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->sum('pi.qty');
        $negativeStockItemsCount = DB::table('product_inventories as pi')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->where('pi.qty', '<', 0)
            ->count();
        $zeroStockItemsCount = DB::table('product_inventories as pi')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->where('pi.qty', '=', 0)
            ->count();

        $lowStockCount = DB::table('inventory_product_inventories as i')
            ->leftJoin(
                DB::raw('(SELECT product_id, SUM(qty) AS total_qty FROM product_inventories GROUP BY product_id) as s'),
                'i.product_id',
                '=',
                's.product_id'
            )
            ->whereNotNull('i.threshold')
            ->where('i.threshold', '>', 0)
            ->whereRaw('COALESCE(s.total_qty, 0) < i.threshold')
            ->count();

        $topCriticalItems = DB::table('product_inventories as pi')
            ->join('product_products as p', 'p.id', '=', 'pi.product_id')
            ->join('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->where('pi.qty', '<=', 0)
            ->select(
                'p.name_ar',
                'p.name_en',
                'e.name as warehouse_name',
                'pi.qty'
            )
            ->orderBy('pi.qty')
            ->limit(10)
            ->get();

        $movementData = $this->buildMovementDataset($selectedWarehouseId, $startDate, $endDate);
        $monthLabels = $movementData['monthLabels'];
        $monthlyInbound = $movementData['monthlyInbound'];
        $monthlyOutbound = $movementData['monthlyOutbound'];
        $monthlyPrep = $movementData['monthlyPrep'];
        $monthlyTransfer = $movementData['monthlyTransfer'];
        $monthlyWaste = $movementData['monthlyWaste'];

        return view(
            'inventory::dashboard.dashboard',
            compact(
                'warehousesCount',
                'warehouses',
                'selectedWarehouseId',
                'movementType',
                'startDate',
                'endDate',
                'transferCount',
                'wasteCount',
                'prepCount',
                'totalStockQuantity',
                'negativeStockItemsCount',
                'zeroStockItemsCount',
                'lowStockCount',
                'topCriticalItems',
                'monthLabels',
                'monthlyInbound',
                'monthlyOutbound',
                'monthlyPrep',
                'monthlyTransfer',
                'monthlyWaste'
            )
        );
    }

    public function exportCriticalItemsCsv(Request $request)
    {
        $warehouseIds = Establishment::where('is_main', 0)->pluck('id');
        $selectedWarehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $effectiveWarehouseIds = $selectedWarehouseId
            ? $warehouseIds->contains($selectedWarehouseId) ? collect([$selectedWarehouseId]) : collect([])
            : $warehouseIds;

        $rows = DB::table('product_inventories as pi')
            ->join('product_products as p', 'p.id', '=', 'pi.product_id')
            ->join('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
            ->whereIn('pi.establishment_id', $effectiveWarehouseIds)
            ->where('pi.qty', '<=', 0)
            ->select('p.name_ar', 'p.name_en', 'e.name as warehouse_name', 'pi.qty')
            ->orderBy('pi.qty')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name AR', 'Name EN', 'Warehouse', 'Qty']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->name_ar ?? '', $row->name_en ?? '', $row->warehouse_name ?? '', (string) $row->qty]);
            }
            fclose($handle);
        }, 'critical-inventory-items.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportMovementCsv(Request $request)
    {
        [$selectedWarehouseId, $startDate, $endDate] = $this->resolveDashboardFilters($request);
        $movementType = $request->input('movement_type', 'all');
        $movementData = $this->buildMovementDataset($selectedWarehouseId, $startDate, $endDate);
        $months = $movementData['months'];
        $movementRows = $movementData['movementRows'];

        $rows = [];
        if ($movementType === 'prep' || $movementType === 'transfer' || $movementType === 'waste') {
            $wantedType = strtoupper($movementType);
            $rows[] = ['Month', 'Type', 'Count'];
            foreach ($months as $month) {
                $count = (int) $movementRows->where('month_key', $month)->where('type', $wantedType)->sum('total');
                $rows[] = [Carbon::createFromFormat('Y-m', $month)->format('Y-m'), $wantedType, (string) $count];
            }
        } else {
            $rows[] = ['Month', 'Inbound_PREP', 'Outbound_TRANSFER_WASTE'];
            foreach ($months as $month) {
                $prep = (int) $movementRows->where('month_key', $month)->where('type', 'PREP')->sum('total');
                $transfer = (int) $movementRows->where('month_key', $month)->where('type', 'TRANSFER')->sum('total');
                $waste = (int) $movementRows->where('month_key', $month)->where('type', 'WASTE')->sum('total');
                $rows[] = [Carbon::createFromFormat('Y-m', $month)->format('Y-m'), (string) $prep, (string) ($transfer + $waste)];
            }
        }

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'inventory-movement-trend.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportMovementPdf(Request $request)
    {
        [$selectedWarehouseId, $startDate, $endDate] = $this->resolveDashboardFilters($request);
        $movementType = $request->input('movement_type', 'all');
        $movementData = $this->buildMovementDataset($selectedWarehouseId, $startDate, $endDate);

        $reportRows = [];
        foreach ($movementData['months'] as $month) {
            $prep = (int) $movementData['movementRows']->where('month_key', $month)->where('type', 'PREP')->sum('total');
            $transfer = (int) $movementData['movementRows']->where('month_key', $month)->where('type', 'TRANSFER')->sum('total');
            $waste = (int) $movementData['movementRows']->where('month_key', $month)->where('type', 'WASTE')->sum('total');
            $reportRows[] = [
                'month' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
                'prep' => $prep,
                'transfer' => $transfer,
                'waste' => $waste,
                'inbound' => $prep,
                'outbound' => $transfer + $waste,
            ];
        }

        $warehouseName = null;
        if ($selectedWarehouseId) {
            $warehouseName = Establishment::query()->where('id', $selectedWarehouseId)->value('name');
        }
        $companyName = Establishment::query()
            ->where('is_main', 1)
            ->value('name') ?? config('app.name');
        $generatedBy = auth()->user()->name ?? 'System';
        $generatedAt = now();

        $html = view('inventory::dashboard.movement_pdf', [
            'rows' => $reportRows,
            'movementType' => $movementType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'warehouseName' => $warehouseName,
            'companyName' => $companyName,
            'generatedBy' => $generatedBy,
            'generatedAt' => $generatedAt,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'tempDir' => storage_path('temp/mpdf'),
        ]);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('inventory-movement-trend.pdf', 'D');
    }

    private function resolveDashboardFilters(Request $request): array
    {
        $warehouseIds = Establishment::where('is_main', 0)->pluck('id');
        $selectedWarehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        if ($selectedWarehouseId && ! $warehouseIds->contains($selectedWarehouseId)) {
            $selectedWarehouseId = null;
        }
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$selectedWarehouseId, $startDate, $endDate];
    }

    private function buildMovementDataset(?int $selectedWarehouseId, Carbon $startDate, Carbon $endDate): array
    {
        $warehouseIds = Establishment::where('is_main', 0)->pluck('id');
        $effectiveWarehouseIds = $selectedWarehouseId
            ? collect([$selectedWarehouseId])
            : $warehouseIds;
        $months = collect(range(5, 0))
            ->map(fn ($offset) => now()->subMonths($offset)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();
        $movementRows = Transaction::query()
            ->selectRaw("DATE_FORMAT(COALESCE(transaction_date, created_at), '%Y-%m') as month_key, type, COUNT(*) as total")
            ->where('status', 'approved')
            ->whereIn('type', ['TRANSFER', 'PREP', 'WASTE'])
            ->whereIn('establishment_id', $effectiveWarehouseIds)
            ->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$startDate, $endDate])
            ->groupBy('month_key', 'type')
            ->get();

        $monthLabels = [];
        $monthlyInbound = [];
        $monthlyOutbound = [];
        $monthlyPrep = [];
        $monthlyTransfer = [];
        $monthlyWaste = [];

        foreach ($months as $month) {
            $monthLabels[] = Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
            $prep = (int) $movementRows->where('month_key', $month)->where('type', 'PREP')->sum('total');
            $transfer = (int) $movementRows->where('month_key', $month)->where('type', 'TRANSFER')->sum('total');
            $waste = (int) $movementRows->where('month_key', $month)->where('type', 'WASTE')->sum('total');
            $monthlyPrep[] = $prep;
            $monthlyTransfer[] = $transfer;
            $monthlyWaste[] = $waste;
            $monthlyInbound[] = $prep;
            $monthlyOutbound[] = $transfer + $waste;
        }

        return [
            'months' => $months,
            'movementRows' => $movementRows,
            'monthLabels' => $monthLabels,
            'monthlyInbound' => $monthlyInbound,
            'monthlyOutbound' => $monthlyOutbound,
            'monthlyPrep' => $monthlyPrep,
            'monthlyTransfer' => $monthlyTransfer,
            'monthlyWaste' => $monthlyWaste,
        ];
    }
}
