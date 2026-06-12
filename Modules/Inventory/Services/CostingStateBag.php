<?php

namespace Modules\Inventory\Services;

/**
 * In-memory costing state for preview/simulation (average + FIFO/LIFO layers).
 */
class CostingStateBag
{
    /** @var array<string, array{qty: float, average_cost: float, stock_value: float}> */
    public array $aggregates = [];

    /** @var array<string, list<array{qty_remaining: float, unit_cost: float, layer_date: string|null, line_id: int|null}>> */
    public array $layers = [];

    public function key(int $productId, int $establishmentId): string
    {
        return $productId.':'.$establishmentId;
    }

    public function getAggregate(int $productId, int $establishmentId): array
    {
        $key = $this->key($productId, $establishmentId);

        return $this->aggregates[$key] ?? [
            'qty' => 0.0,
            'average_cost' => 0.0,
            'stock_value' => 0.0,
        ];
    }

    public function setAggregate(int $productId, int $establishmentId, float $qty, float $avg, float $value): void
    {
        $this->aggregates[$this->key($productId, $establishmentId)] = [
            'qty' => round($qty, 6),
            'average_cost' => round($avg, 6),
            'stock_value' => round($value, 6),
        ];
    }
}
