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
        $mainId = self::tableMainLineId($item);
        $lines = $allLines ?? OrderTableItems::query()
            ->where('transaction_id', $item->transaction_id)
            ->get();

        return $lines
            ->filter(static function (OrderTableItems $line) use ($mainId): bool {
                if ((int) $line->id === $mainId) {
                    return true;
                }

                $parentId = $line->parent_id;

                return $parentId !== null
                    && $parentId !== ''
                    && ((int) $parentId === $mainId || (string) $parentId === (string) $mainId);
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

        return $allLines
            ->filter(static function (TransactionSellLine $line) use ($mainId): bool {
                if ((int) $line->id === $mainId) {
                    return true;
                }

                return (string) $line->parent_id === (string) $mainId;
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
        $currentGroupIds = [];
        $hasCombosInGroup = false;
        $nonComboChildCount = 0;

        $flush = static function () use (&$currentGroupIds, &$matchedGroup, $targetLineId, &$hasCombosInGroup, &$nonComboChildCount) {
            if ($currentGroupIds === []) {
                return;
            }

            if (in_array($targetLineId, $currentGroupIds, true)) {
                $matchedGroup = $currentGroupIds;
            }

            $hasCombosInGroup = false;
            $nonComboChildCount = 0;
        };

        foreach ($allLines->sortBy('id')->values() as $line) {
            $lineId = (int) $line->id;

            if (KitchenOrderPayload::isPosComboComponentLine($line)) {
                if ($currentMain) {
                    $currentGroupIds[] = $lineId;
                    $hasCombosInGroup = true;
                }

                continue;
            }

            if ($currentMain === null) {
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            if ($hasCombosInGroup) {
                $flush();
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            if ($nonComboChildCount === 0 && KitchenOrderPayload::looksLikePosMainProductLine($line, $currentMain)) {
                $flush();
                $currentMain = $line;
                $currentGroupIds = [$lineId];

                continue;
            }

            $currentGroupIds[] = $lineId;
            $nonComboChildCount++;
        }

        $flush();

        return $matchedGroup;
    }

    private static function tableMainLineId(OrderTableItems $item): int
    {
        if ($item->parent_id !== null && $item->parent_id !== '' && (int) $item->parent_id > 0) {
            return (int) $item->parent_id;
        }

        return (int) $item->id;
    }
}
