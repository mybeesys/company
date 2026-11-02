<?php

namespace Modules\Reservation\Support;

use Illuminate\Support\Collection;
use Modules\General\Models\TransactionSellLine;
use Modules\Reservation\Models\OrderTableItems;

/**
 * يحدد أسطر الطلب (رئيسي + كومبو + موديفاير) التي تُحدَّث معاً عند تجهيز صنف من المطبخ.
 */
final class KitchenItemStatusGrouper
{
    /**
     * @return array<int, int>
     */
    public static function tableLineGroupIds(OrderTableItems $item, ?Collection $allLines = null): array
    {
        $lines = $allLines ?? OrderTableItems::query()
            ->where('transaction_id', $item->transaction_id)
            ->with('product')
            ->get();
        $mainId = self::tableMainLineId($item, $lines);
        $main = $lines->firstWhere('id', $mainId) ?? $item;

        return $lines
            ->filter(static function (OrderTableItems $line) use ($mainId, $main): bool {
                if ((int) $line->id === $mainId) {
                    return true;
                }

                $parentId = $line->parent_id;
                if ($parentId === null || $parentId === '') {
                    return false;
                }

                if ((int) $parentId !== $mainId && (string) $parentId !== (string) $mainId) {
                    return false;
                }

                return KitchenOrderPayload::belongsToSameKitchenStation($main, $line);
            })
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * نفس تجميع formatPosSellLines — كل أسطر المجموعة تُحدَّث معاً.
     *
     * @param  Collection<int, TransactionSellLine>  $allLines
     * @return array<int, int>
     */
    public static function posLineGroupIds(Collection $allLines, int $targetLineId): array
    {
        if ($allLines->contains(fn (TransactionSellLine $line) => ! empty($line->parent_id))) {
            $target = $allLines->firstWhere('id', $targetLineId);
            if ($target instanceof TransactionSellLine) {
                return self::posLineGroupIdsByParent($allLines, $target);
            }
        }

        return self::posLineGroupIdsSequential($allLines, $targetLineId);
    }

    /**
     * @param  Collection<int, TransactionSellLine>  $allLines
     * @return array<int, int>
     */
    private static function posLineGroupIdsByParent(Collection $allLines, TransactionSellLine $item): array
    {
        $mainId = ! empty($item->parent_id) ? (int) $item->parent_id : (int) $item->id;
        $main = $allLines->firstWhere('id', $mainId) ?? $item;
        if (! empty($item->parent_id) && $main && ! KitchenOrderPayload::belongsToSameKitchenStation($main, $item)) {
            $mainId = (int) $item->id;
            $main = $item;
        }

        return $allLines
            ->filter(static function (TransactionSellLine $line) use ($mainId, $main): bool {
                if ((int) $line->id === $mainId) {
                    return true;
                }

                if ((string) $line->parent_id !== (string) $mainId) {
                    return false;
                }

                return KitchenOrderPayload::belongsToSameKitchenStation($main, $line);
            })
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TransactionSellLine>  $allLines
     * @return array<int, int>
     */
    private static function posLineGroupIdsSequential(Collection $allLines, int $targetLineId): array
    {
        $matchedGroup = [$targetLineId];
        $currentMain = null;
        /** @var array<int, TransactionSellLine> $currentChildren */
        $currentChildren = [];
        $currentGroupIds = [];

        $flush = static function () use (&$currentGroupIds, &$matchedGroup, $targetLineId, &$currentChildren, &$currentMain) {
            if ($currentGroupIds === []) {
                return;
            }

            if (in_array($targetLineId, $currentGroupIds, true)) {
                $matchedGroup = $currentGroupIds;
            }

            $currentMain = null;
            $currentGroupIds = [];
            $currentChildren = [];
        };

        foreach ($allLines->sortBy('id')->values() as $line) {
            $lineId = (int) $line->id;

            if (KitchenOrderPayload::isPosComboComponentLine($line)) {
                if ($currentMain && KitchenOrderPayload::belongsToSameKitchenStation($currentMain, $line)) {
                    $currentChildren[] = $line;
                    $currentGroupIds[] = $lineId;
                    continue;
                }

                $flush();
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            if ($currentMain === null) {
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            if (KitchenOrderPayload::legacyPosChildrenContainCombos($currentChildren)) {
                $flush();
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            if (! KitchenOrderPayload::shouldAttachAsLegacyPosChildLine($line, $currentMain, $currentChildren)) {
                $flush();
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            $currentChildren[] = $line;
            $currentGroupIds[] = $lineId;
        }

        $flush();

        return $matchedGroup;
    }

    private static function tableMainLineId(OrderTableItems $item, Collection $lines): int
    {
        if ($item->parent_id === null || $item->parent_id === '' || (int) $item->parent_id <= 0) {
            return (int) $item->id;
        }

        $parentId = (int) $item->parent_id;
        $parent = $lines->firstWhere('id', $parentId);
        if ($parent && ! KitchenOrderPayload::belongsToSameKitchenStation($parent, $item)) {
            return (int) $item->id;
        }

        return $parentId;
    }
}
