<?php

namespace Modules\Inventory\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\Transaction;

/**
 * Live warehouse stock and movement signals. Matches /inventory-dashboard math.
 */
class InventoryStockHealthService
{
    public const LIST_LIMIT = 8;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?int $warehouseId, Carbon $periodStart, Carbon $periodEnd, int $listLimit = self::LIST_LIMIT): array
    {
        $warehouseIds = $this->warehouseIds($warehouseId);
        $empty = $warehouseIds === [];

        $inventoryRows = $empty
            ? collect()
            : DB::table('product_inventories as pi')
                ->join('product_products as p', 'p.id', '=', 'pi.product_id')
                ->whereIn('pi.establishment_id', $warehouseIds)
                ->select('pi.establishment_id', 'pi.product_id', 'pi.qty', 'p.name_ar', 'p.name_en')
                ->get()
                ->groupBy('establishment_id');

        $warehouses = $empty
            ? collect()
            : Establishment::query()
                ->where('is_main', 0)
                ->when($warehouseId, fn ($q) => $q->where('id', $warehouseId))
                ->get(['id', 'name']);

        $warehouseExtremes = $warehouses->map(function ($warehouse) use ($inventoryRows) {
            $rows = $inventoryRows->get($warehouse->id, collect());
            $most = $rows->sortByDesc('qty')->first();
            $least = $rows->sortBy('qty')->first();

            return [
                'id' => (int) $warehouse->id,
                'name' => (string) $warehouse->name,
                'highest_name' => $most ? (string) ($most->name_ar ?: $most->name_en) : null,
                'highest_qty' => $most ? (float) $most->qty : 0.0,
                'lowest_name' => $least ? (string) ($least->name_ar ?: $least->name_en) : null,
                'lowest_qty' => $least ? (float) $least->qty : 0.0,
                'negative_count' => $rows->where('qty', '<', 0)->count(),
            ];
        })->values();

        $negativeCount = $empty ? 0 : (int) DB::table('product_inventories')
            ->whereIn('establishment_id', $warehouseIds)
            ->where('qty', '<', 0)
            ->count();
        $zeroCount = $empty ? 0 : (int) DB::table('product_inventories')
            ->whereIn('establishment_id', $warehouseIds)
            ->where('qty', '=', 0)
            ->count();

        $lowStockCount = 0;
        if (Schema::hasTable('inventory_product_inventories')) {
            $lowStockCount = (int) DB::table('inventory_product_inventories as i')
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
        }

        $criticalItems = $empty
            ? collect()
            : DB::table('product_inventories as pi')
                ->join('product_products as p', 'p.id', '=', 'pi.product_id')
                ->join('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
                ->whereIn('pi.establishment_id', $warehouseIds)
                ->where('pi.qty', '<=', 0)
                ->select(
                    'pi.product_id',
                    'pi.establishment_id',
                    'p.name_ar',
                    'p.name_en',
                    'e.name as warehouse_name',
                    'pi.qty'
                )
                ->orderBy('pi.qty')
                ->limit($listLimit)
                ->get();

        $wasteCount = $this->approvedOpsQuery($warehouseIds, $periodStart, $periodEnd)
            ->where('type', 'WASTE')
            ->count();

        return [
            'warehouse_ids' => $warehouseIds,
            'negative_count' => $negativeCount,
            'zero_count' => $zeroCount,
            'low_stock_count' => $lowStockCount,
            'waste_count' => $wasteCount,
            'warehouse_extremes' => $warehouseExtremes->all(),
            'critical_items' => $criticalItems,
            'movement' => $this->movementLastSixMonths($warehouseIds),
        ];
    }

    /**
     * @param  list<int>  $warehouseIds
     */
    public function stockRows(array $warehouseIds, string $mode, int $limit): Collection
    {
        if ($warehouseIds === []) {
            return collect();
        }

        $q = DB::table('product_inventories as pi')
            ->leftJoin('product_products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('est_establishments as e', 'e.id', '=', 'pi.establishment_id')
            ->whereIn('pi.establishment_id', $warehouseIds)
            ->select(
                'pi.product_id',
                'pi.establishment_id',
                'p.name_ar',
                'p.name_en',
                'e.name as warehouse_name',
                'pi.qty'
            );

        if ($mode === 'negative') {
            $q->where('pi.qty', '<', 0)->orderBy('pi.qty');
        } elseif ($mode === 'zero') {
            $q->where('pi.qty', '=', 0)->orderBy('p.name_ar');
        } else {
            $q->where('pi.qty', '<=', 0)->orderBy('pi.qty');
        }

        return $q->limit($limit)->get();
    }

    /**
     * @param  list<int>  $warehouseIds
     */
    public function wasteRows(array $warehouseIds, Carbon $start, Carbon $end, int $limit): Collection
    {
        return $this->approvedOpsQuery($warehouseIds, $start, $end)
            ->where('type', 'WASTE')
            ->orderByDesc(DB::raw('COALESCE(transaction_date, created_at)'))
            ->limit($limit)
            ->get(['id', 'ref_no', 'transaction_date', 'created_at', 'establishment_id']);
    }

    /**
     * @return list<int>
     */
    public function warehouseIds(?int $warehouseId): array
    {
        $ids = Establishment::query()->where('is_main', 0)->pluck('id');
        if ($warehouseId) {
            $ids = $ids->contains($warehouseId) ? collect([$warehouseId]) : collect();
        }

        return array_values(array_map('intval', $ids->all()));
    }

    /**
     * @param  list<int>  $warehouseIds
     * @return \Illuminate\Database\Eloquent\Builder<\Modules\General\Models\Transaction>
     */
    protected function approvedOpsQuery(array $warehouseIds, Carbon $start, Carbon $end)
    {
        $q = Transaction::query()
            ->where('status', 'approved')
            ->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$start, $end]);
        if ($warehouseIds !== []) {
            $q->whereIn('establishment_id', $warehouseIds);
        } else {
            $q->whereRaw('1 = 0');
        }

        return $q;
    }

    /**
     * @param  list<int>  $warehouseIds
     * @return array{months: list<string>, inbound: list<int>, outbound: list<int>, waste: list<int>}
     */
    protected function movementLastSixMonths(array $warehouseIds): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => Carbon::now()->subMonths($i)->format('Y-m'))->values();
        $start = Carbon::now()->subMonths(5)->startOfMonth();
        $end = Carbon::now()->endOfDay();

        $rows = collect();
        if ($warehouseIds !== []) {
            $rows = Transaction::query()
                ->selectRaw("DATE_FORMAT(COALESCE(transaction_date, created_at), '%Y-%m') as month_key, type, COUNT(*) as total")
                ->where('status', 'approved')
                ->whereIn('type', ['TRANSFER', 'PREP', 'WASTE'])
                ->whereIn('establishment_id', $warehouseIds)
                ->whereBetween(DB::raw('COALESCE(transaction_date, created_at)'), [$start, $end])
                ->groupBy('month_key', 'type')
                ->get();
        }

        $inbound = [];
        $outbound = [];
        $waste = [];
        foreach ($months as $month) {
            $prep = (int) $rows->where('month_key', $month)->where('type', 'PREP')->sum('total');
            $transfer = (int) $rows->where('month_key', $month)->where('type', 'TRANSFER')->sum('total');
            $wasteCount = (int) $rows->where('month_key', $month)->where('type', 'WASTE')->sum('total');
            $inbound[] = $prep;
            $outbound[] = $transfer + $wasteCount;
            $waste[] = $wasteCount;
        }

        return [
            'months' => $months->all(),
            'inbound' => $inbound,
            'outbound' => $outbound,
            'waste' => $waste,
        ];
    }
}
