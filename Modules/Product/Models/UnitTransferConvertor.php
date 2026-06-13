<?php

namespace Modules\Product\Models;

use Exception;

class UnitTransferConvertor
{
    public static function loadUnitsForItem(string $itemType, int $itemId): array
    {
        if ($itemType === 'p') {
            return UnitTransfer::where('product_id', $itemId)->get()->toArray();
        }

        return UnitTransfer::where(function ($query) use ($itemId) {
            $query->where('product_id', $itemId)
                ->orWhere('modifier_id', $itemId);
        })->get()->toArray();
    }

    public static function getFactorToMainUnit(int $fromUnitId, ?array $units): ?float
    {
        if (! $units || ! $fromUnitId) {
            return 1.0;
        }

        $mapUnits = array_column($units, null, 'id');
        $mainUnit = null;
        foreach ($units as $unit) {
            if ($unit['unit2'] == null) {
                $mainUnit = $unit;
                break;
            }
        }

        if (! $mainUnit) {
            return 1.0;
        }

        if ((int) $fromUnitId === (int) $mainUnit['id']) {
            return 1.0;
        }

        $factor = 1.0;
        $currentId = $fromUnitId;
        $visited = [];

        while ($currentId != null && (int) $currentId !== (int) $mainUnit['id']) {
            if (in_array($currentId, $visited, true)) {
                return null;
            }
            $visited[] = $currentId;

            if (! isset($mapUnits[$currentId])) {
                return null;
            }

            $unit = $mapUnits[$currentId];
            $transfer = (float) ($unit['transfer'] ?? 0);
            if ($transfer <= 0) {
                break;
            }

            $factor *= $transfer;
            $currentId = $unit['unit2'];
        }

        return $factor;
    }

    public static function recipeLineCost(
        float $quantity,
        float $itemCost,
        ?int $unitTransferId,
        string $itemType,
        int $itemId
    ): float {
        if (! $unitTransferId) {
            return round($quantity * $itemCost, 4);
        }

        $units = self::loadUnitsForItem($itemType, $itemId);
        $factor = self::getFactorToMainUnit($unitTransferId, $units);

        if (! $factor || $factor <= 0) {
            return round($quantity * $itemCost, 4);
        }

        return round(($quantity / $factor) * $itemCost, 4);
    }

    public static function getMainUnit($type, $id, $units)
    {
        if ($units == null) {
            $units = self::loadUnitsForLegacyType($type, $id);
        }
        $mainUnit = array_filter($units, function ($unit) {
            return $unit['unit2'] == null; // Keep only even numbers
        });
        $mainUnit = reset($mainUnit);

        return $mainUnit;
    }

    public static function convertUnit($type, $id, $fromId, $toId, $quantity, $units)
    {
        if ($units == null) {
            $units = self::loadUnitsForLegacyType($type, $id);
        }
        if ($toId == null) {
            $mainUnit = self::getMainUnit($type, $id, $units);
            $toId = $mainUnit['id'];
        }
        if ($fromId === $toId) {
            return $quantity;
        }

        $mapUnits = array_column($units, null, 'id');
        $path = self::findPath($mapUnits, $fromId, $toId);
        if (! $path) {
            return null; // No conversion path found
        }

        foreach ($path as $step) {
            $transfer = $mapUnits[$step]['transfer'];
            if ($transfer !== null) {
                $quantity *= $transfer;
            } else {
                $quantity /= self::getReverseTransfer($mapUnits, $step);
            }
        }

        return $quantity;
    }

    private static function findPath($units, $fromId, $toId, $visited = [])
    {
        if ($fromId === $toId) {
            return [];
        }
        $visited[] = $fromId;
        try {
            $unit = $units[$fromId];
        } catch (Exception $e) {
            dd($e->getTrace());
        }
        if ($unit['unit2'] && ! in_array($unit['unit2'], $visited)) {
            $path = self::findPath($units, $unit['unit2'], $toId, $visited);
            if ($path !== null) {
                return array_merge([$fromId], $path);
            }
        }

        foreach ($units as $id => $u) {
            if ($u['unit2'] === $fromId && ! in_array($id, $visited)) {
                $path = self::findPath($units, $id, $toId, $visited);
                if ($path !== null) {
                    return array_merge([$id], $path);
                }
            }
        }

        return null;
    }

    private static function getReverseTransfer($units, $unitId)
    {
        foreach ($units as $id => $unit) {
            if ($unit['unit2'] === $unitId) {
                return $unit['transfer'];
            }
        }

        return 1;
    }

    private static function loadUnitsForLegacyType(string $type, int $id): array
    {
        if ($type === 'P' || $type === 'I') {
            return UnitTransfer::where('product_id', $id)->get()->toArray();
        }

        return UnitTransfer::where(function ($query) use ($id) {
            $query->where('product_id', $id)
                ->orWhere('modifier_id', $id);
        })->get()->toArray();
    }
}
